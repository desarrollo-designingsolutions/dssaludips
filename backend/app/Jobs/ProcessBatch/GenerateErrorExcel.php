<?php

namespace App\Jobs\ProcessBatch;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\BellNotification;
use App\Helpers\Constants;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GenerateErrorExcel implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $processKey;
    protected string $userId;

    public function __construct(string $processKey, string $userId)
    {
        $this->processKey = $processKey;
        $this->userId     = $userId;
        $this->onQueue('download_files');
    }

    public function handle(): void
    {
        $rowsKey = $this->processKey . ':rows';
        $seenKey = $this->processKey . ':seen_rows';

        try {
            $metadata = Redis::hgetall($this->processKey);
            $fileName = $metadata['file_name'] ?? ('report_data_' . time() . '.xlsx');
            $userId   = $metadata['user_id'] ?? $this->userId;

            // 1) Traer filas crudas
            $rawRows = Redis::lrange($rowsKey, 0, -1);
            Log::info("{$this->processKey}: filas en Redis=" . count($rawRows));

            // 2) Decodificar a arrays asociativos
            $rows = [];
            foreach ($rawRows as $raw) {
                $obj = json_decode($raw, true);
                $rows[] = is_array($obj) ? $obj : [];
            }

            // 3) Excluir claves no deseadas (doble cinturón)
            $exclude = $this->getExcludedKeys();
            foreach ($rows as &$r) {
                foreach ($exclude as $badKey) unset($r[$badKey]);
            }
            unset($r);

            // 4) Construir header dinámico (unión por primera aparición)
            $headerIndex = [];
            $headers     = [];
            foreach ($rows as $r) {
                foreach ($r as $k => $_) {
                    if (in_array($k, $exclude, true)) continue;
                    if (!array_key_exists($k, $headerIndex)) {
                        $headerIndex[$k] = count($headers);
                        $headers[] = $k;
                    }
                }
            }

            // 5) Crear spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Datos');

            // Headers visibles
            if (!empty($headers)) {
                $sheet->fromArray([$headers], null, 'A1', true);
                $lastCol = Coordinate::stringFromColumnIndex(count($headers));
                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            }

            // 6) Escribir filas siguiendo el orden del header
            $rowIndex = 2;
            foreach ($rows as $r) {
                $line = [];
                foreach ($headers as $k) {
                    $line[] = $r[$k] ?? '';
                }
                $sheet->fromArray([$line], null, "A{$rowIndex}", true);
                $rowIndex++;
            }

            // 7) AutoSize
            $colCount = max(1, count($headers));
            for ($i = 1; $i <= $colCount; $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }

            // 8) Guardar en storage configurado
            $finalPath = 'processBatch_reportsErrors/' . $fileName;
            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
            (new Xlsx($spreadsheet))->save($tmpPath);

            $stream = fopen($tmpPath, 'r');
            Storage::disk(Constants::DISK_FILES)->put($finalPath, $stream);

            // 9) URL pública y notificación
            $absolutePath = env('SYSTEM_URL_BACK') . 'storage/' . $finalPath;

            if ($user = User::find($userId)) {
                $user->notify(new BellNotification([
                    'title'       => 'Reporte XLSX generado',
                    'subtitle'    => "El archivo {$fileName} está listo para descargar.",
                    'type'        => 'success',
                    'action_url'  => $absolutePath,
                    'openInNewTab'=> true,
                ]));
            }

            // 10) Limpiar Redis
            Redis::del($this->processKey, $rowsKey, $seenKey);
            Log::info("{$this->processKey}: XLSX OK (filas=" . (count($rows)) . ", cols=" . count($headers) . ").");

        } catch (\Throwable $e) {
            Log::error("{$this->processKey}: error GenerateErrorExcel: {$e->getMessage()}");
            Redis::del($this->processKey, $rowsKey, $seenKey);

            if ($user = User::find($this->userId)) {
                $user->notify(new BellNotification([
                    'title'    => 'Error al generar XLSX',
                    'subtitle' => $e->getMessage(),
                    'type'     => 'error'
                ]));
            }
            throw $e;
        }
    }

    private function getExcludedKeys(): array
    {
        $raw = env('EXPORT_EXCLUDE_KEYS', 'company_id');
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
