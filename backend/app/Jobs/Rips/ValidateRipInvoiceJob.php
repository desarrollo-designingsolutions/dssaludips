<?php

namespace App\Jobs\Rips;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Events\RipValidationStatusUpdated;
use App\Exports\Rips\RipXlsExport;
use App\Helpers\Constants;
use App\Helpers\Rips\GenerateRipInfo;
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

            GenerateRipInfo::generateDataJsonAndExcel($invoice->rip_id);

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
        // 1) Cargar JSON fuente
        if (!Storage::disk(Constants::DISK_FILES)->exists($invoice->path_json)) {
            Log::warning("No se encontró path_json para RipInvoice ID: {$invoice->id}");
            return;
        }

        $json = json_decode(Storage::disk(Constants::DISK_FILES)->get($invoice->path_json), true);
        if (!$json || !is_array($json)) {
            Log::warning("Error al decodificar path_json para RipInvoice ID: {$invoice->id}");
            return;
        }

        // 2) Cargar metadata de validación
        $validation = json_decode($invoice->validation_metadata, true) ?: [];
        $rv = $validation['ResultadosValidacion'] ?? [];

        // 3) Construir mapa de observaciones: campo => "mensaje..."
        $obsByField = [];
        foreach ((array)$rv as $item) {
            $path = $item['PathFuente'] ?? null;
            if (!$path) continue;

            // Mensaje (ajusta llaves si tu API trae otros nombres)
            $msg = $item['Observaciones']
                ?? $item['Mensaje']
                ?? $item['Descripcion']
                ?? $item['Codigo']
                ?? 'Error de validación';

            // Tomar el último segmento del path como nombre de campo
            // Ej: usuarios[0].servicios.procedimientos[2].codDiagnosticoPrincipal -> codDiagnosticoPrincipal
            if (preg_match('/([A-Za-z0-9_]+)(?:\]?)$/', $path, $m)) {
                $field = $m[1];
                $obsByField[$field] = isset($obsByField[$field]) && $obsByField[$field]
                    ? ($obsByField[$field] . ' | ' . $msg)
                    : $msg;
            }
        }

        // 4) Marcar los campos en el JSON con la clave especial (para el export)
        $pathFuentes = array_unique(array_filter(array_map(
            fn($i) => $i['PathFuente'] ?? null,
            is_array($rv) ? $rv : []
        )));

        if (empty($pathFuentes)) {
            Log::info("No hay PathFuente para procesar en RipInvoice ID: {$invoice->id}");
            return;
        }

        foreach ($pathFuentes as $path) {
            self::setNullByPath($json, $path); // pone Constants::EXCEL_GENERATION_KEY en el campo
        }

        // 5) Generar Excel ENRIQUECIDO
        $rip       = $invoice->rip;
        $type      = $rip->type->value ?? 'unknown';
        $numFactura = $json['numFactura'] ?? 'unknown';
        $nameFile  = "{$numFactura}.xlsx";
        $routeXls  = "companies/company_{$rip->company_id}/rips/{$type}/rip_{$rip->id}/invoices/{$numFactura}/{$nameFile}";

        // ⚠️ Enviar el wrapper enriquecido que tu export ya sabe manejar
        $row = [
            'data'       => $json,
            'obsByField' => $obsByField,
        ];

        Excel::store(new RipXlsExport([$row]), $routeXls, Constants::DISK_FILES, \Maatwebsite\Excel\Excel::XLSX);

        // 6) Persistir ruta
        $invoice->path_excel = $routeXls;
        $invoice->save();
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

        $parts = [];
        preg_match_all('/([^\.\[\]]+|\[\d+\])/', $path, $matches);
        $parts = $matches[0];

        $current = &$array;

        foreach ($parts as $i => $part) {
            // Log::debug("Procesando parte {$i}: {$part}");

            if (preg_match('/^\[(\d+)\]$/', $part, $indexMatch)) {
                $index = (int)$indexMatch[1];
                // Log::debug("Buscando índice [{$index}] en array");

                if (!array_key_exists($index, $current)) {  // CAMBIO AQUÍ
                    // Log::warning("Índice [{$index}] no existe");
                    return;
                }
                $current = &$current[$index];
            } else {
                // Log::debug("Buscando clave '{$part}'");

                if (!array_key_exists($part, $current)) {  // CAMBIO AQUÍ
                    // Log::warning("Clave '{$part}' no encontrada");
                    return;
                }
                $current = &$current[$part];
            }

            // Log::debug("Navegado exitosamente");
        }

        // Log::info("Campo encontrado, seteando a null");
        $current = Constants::EXCEL_GENERATION_KEY; // Valor especial para solicitar el campo en el excel a descargar
        // Log::info("Campo seteado a null exitosamente");
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
