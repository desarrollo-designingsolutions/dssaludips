<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Events\RipValidationStatusUpdated;
use App\Exports\RipXlsExport;
use App\Helpers\Constants;
use App\Models\RipInvoice;
use App\Repositories\RipInvoiceRepository;
use App\Services\Rips\RipsMinistryApiClient;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ValidateRipInvoiceJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $invoiceId;
    public $batchId;


    public function __construct($invoiceId, $batchId = null)
    {
        $this->invoiceId = $invoiceId;
        $this->batchId = $batchId;
    }

    public function handle(RipsMinistryApiClient $ripsClient, RipInvoiceRepository $ripInvoiceRepository)
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Notificar inicio - se envía al canal específico de esta factura
        event(new RipValidationStatusUpdated(
            $this->invoiceId,
            RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006,
            null,
            $this->batchId,
        ));
        $invoice = $ripInvoiceRepository->changeState($this->invoiceId, RipInvoiceStatusEnum::RIP_INVOICE_STATUS_006, "status");


        try {
            $result = $ripsClient->validateInvoice($this->invoiceId);

            $invoice = $ripInvoiceRepository->find($this->invoiceId);
            $this->changeJsonValues($invoice);

            if ($result["status_code"] != 200) {
                $status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_007;
            } else {
                // Actualiza estado final y notifica
                $status = RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001;
            }

            $ripInvoiceRepository->changeState($this->invoiceId, $status, "status");
            event(new RipValidationStatusUpdated(
                $this->invoiceId,
                $status,
                null,
                $this->batchId,
            ));
        } catch (\Exception $e) {
            Log::error("Error en job ValidateRipInvoiceJob para factura {$this->invoiceId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Modifica el JSON de una factura seteando a null los campos indicados por PathFuente en validation_metadata,
     * y genera un Excel con el resultado.
     *
     * @param bool $persistJson Si true, guarda el JSON modificado en path_json
     */
    public static function changeJsonValues($invoice)
    {
        // Cargar path_json desde disco
        if (!Storage::disk(Constants::DISK_FILES)->exists($invoice->path_json)) {
            Log::warning("No se encontró path_json para RipInvoice ID: {$invoice->id}");
            return;
        }

        $json = json_decode(Storage::disk(Constants::DISK_FILES)->get($invoice->path_json), true);
        if (!$json) {
            Log::warning("Error al decodificar path_json para RipInvoice ID: {$invoice->id}");
            return;
        }

        // Cargar validation_metadata
        $validation_metadata = json_decode($invoice->validation_metadata, true);
        $resultadosValidacion = $validation_metadata['ResultadosValidacion'] ?? [];

        Log::info("Resultados de validación para RipInvoice ID: {$invoice->id}", $resultadosValidacion);

        // Extraer PathFuente únicos (solo RECHAZADO)
        $pathFuentes = array_unique(array_filter(array_map(function ($item) {
            return (isset($item['PathFuente']))
                ? $item['PathFuente']
                : null;
        }, $resultadosValidacion)));

        Log::info("PathFuente a procesar para RipInvoice ID: {$invoice->id}", $pathFuentes);

        if (empty($pathFuentes)) {
            Log::info("No hay PathFuente para procesar en RipInvoice ID: {$invoice->id}");
            return;
        }

        // Modificar JSON seteando campos a null
        foreach ($pathFuentes as $path) {
            Log::debug("JSON antes de modificar:", $json);
            self::setNullByPath($json, $path);
        }

        // Generar Excel
        $type = $invoice->rip->type->value ?? 'unknown';
        $rip = $invoice->rip;
        $numFactura = $json['numFactura'] ?? 'unknown';
        $nameFile = "{$numFactura}.xlsx";
        $routeXls = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/invoices/{$numFactura}/{$nameFile}";

        Excel::store(new RipXlsExport([$json]), $routeXls, Constants::DISK_FILES, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Setea un campo a null basado en un PathFuente como "Rips.usuarios[0].servicios.procedimientos[0].codDiagnosticoPrincipal".
     *
     * @param array &$array JSON de la factura
     * @param string $path PathFuente de la API
     */
    private static function setNullByPath(&$array, $path)
    {
        // Quitar prefijo "rips." si existe, de forma case-insensitive
        if (stripos($path, 'rips.') === 0) {
            $path = substr($path, 5);
        }

        Log::info("Path a procesar: {$path}");

        $parts = [];
        preg_match_all('/([^\.\[\]]+|\[\d+\])/', $path, $matches);
        $parts = $matches[0];

        Log::debug("Partes del path:", $parts);
        Log::debug("Estructura actual del array:", $array);

        $current = &$array;

        foreach ($parts as $i => $part) {
            Log::debug("Procesando parte {$i}: {$part}");

            if (preg_match('/^\[(\d+)\]$/', $part, $indexMatch)) {
                $index = (int)$indexMatch[1];
                Log::debug("Buscando índice [{$index}] en array");

                if (!isset($current[$index])) {
                    Log::warning("Índice [{$index}] no existe");
                    return;
                }
                $current = &$current[$index];
            } else {
                Log::debug("Buscando clave '{$part}' en: ", [json_encode(array_keys($current))]);

                if (!isset($current[$part])) {
                    Log::warning("Clave '{$part}' no encontrada");
                    return;
                }
                $current = &$current[$part];
            }

            Log::debug("Valor actual después de parte {$i}:", [$current]);
        }

        Log::info("Campo encontrado, valor actual: ", [json_encode($current)]);
        $current = null;
        Log::info("Campo seteado a null exitosamente");
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception->getMessage());
        // event(new RipValidationStatusUpdated(
        //     $this->invoiceId,
        //     'failed',
        //     null,
        //     $exception->getMessage(),
        //     $this->batchId
        // ));
    }
}
