<?php

namespace App\Helpers\Rips;

use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Facades\Redis;

class CTFileValidator
{
    /**
     * Valida el archivo CT y sus columnas.
     *
     * @param  string  $fileName  Nombre del archivo
     * @param  array   $rowData   Datos de la fila del txt a validar
     * @param  int     $rowNumber Numero de la fila del txt a validar
     * @param  string  $batchId   ID del lote
     */
    public static function validate(string $fileName, array $rowData, int $rowNumber, string $batchId): void
    {
        // Limpiar todos los elementos de $rowData
        $rowData = CleanRowData::execute($rowData);

        $titleColumn = [
            'columna 1: Código del prestador de servicios de salud',
            'columna 2: Fecha de remisión',
            'columna 3: Código del archivo',
            'columna 4: Total de registros',
        ];

        $codigoArchivos = json_decode(Redis::get("rip_batch:{$batchId}:validationCt_codigoArchivos") ?? '[]', true);

        $redis = Redis::connection('redis_6380');
        $tempDir = $redis->get("rip_batch:{$batchId}:tempZip");


        // 1. Validar codigo_prestador (columna 1)
        if (!ctype_digit($rowData[0])) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_CT_ERROR_001['message'],
                ErrorCodes::FILE_CT_ERROR_001['code'],
                $rowData[0],
                $fileName
            );
        }
        if (strlen($rowData[0]) !== 12) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[0],
                ErrorCodes::FILE_CT_ERROR_002['message'],
                ErrorCodes::FILE_CT_ERROR_002['code'],
                $rowData[0],
                $fileName
            );
        }

        // 2. Validar fecha (columna 2)
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rowData[1]) || !self::isValidDate($rowData[1])) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_CT_ERROR_003['message'],
                ErrorCodes::FILE_CT_ERROR_003['code'],
                $rowData[1],
                $fileName
            );
        } elseif (self::isDateAfterToday($rowData[1])) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[1],
                ErrorCodes::FILE_CT_ERROR_004['message'],
                ErrorCodes::FILE_CT_ERROR_004['code'],
                $rowData[1],
                $fileName
            );
        }

        // 3. Validar codigo_archivo (columna 3)
        $prefix = strtoupper(substr($rowData[2], 0, 2));
        $allowedPrefixes = ['AC', 'AF', 'AH', 'AM', 'AN', 'AP', 'AT', 'AU', 'US', 'CT'];
        if (!in_array($prefix, $allowedPrefixes)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_CT_ERROR_005['message'],
                ErrorCodes::FILE_CT_ERROR_005['code'],
                $rowData[2],
                $fileName
            );
        }

        if (in_array($rowData[2], $codigoArchivos)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[2],
                ErrorCodes::FILE_CT_ERROR_006['message'],
                ErrorCodes::FILE_CT_ERROR_006['code'],
                $rowData[2],
                $fileName
            );
        } else {
            $codigoArchivos[] = $rowData[2];
            $redis = Redis::connection('redis_6380');
            $redis->set("rip_batch:{$batchId}:validationCt_codigoArchivos", json_encode($codigoArchivos));
            $redis->expire("rip_batch:{$batchId}:validationCt_codigoArchivos", 86400);
        }

        // 4. Validar total_registros (columna 4)
        $cleanValue = trim((string) $rowData[3]);
        if (!ctype_digit($cleanValue) || empty($cleanValue)) {
            ErrorCollector::addError(
                $batchId,
                $rowNumber,
                $titleColumn[3],
                ErrorCodes::FILE_CT_ERROR_007['message'],
                ErrorCodes::FILE_CT_ERROR_007['code'],
                $rowData[3],
                $fileName
            );
        } else {
            $fileToFind = $rowData[2];
            $expectedCount = (int) $rowData[3];
            $actualCount = self::countFileRows($tempDir, $fileToFind);
            if ($actualCount === null) {
                ErrorCollector::addError(
                    $batchId,
                    $rowNumber,
                    $titleColumn[3],
                    ErrorCodes::getMessage('FILE_CT_ERROR_008', $fileToFind),
                    ErrorCodes::FILE_CT_ERROR_008['code'],
                    $rowData[2],
                    $fileName
                );
            } elseif ($actualCount !== $expectedCount) {
                ErrorCollector::addError(
                    $batchId,
                    $rowNumber,
                    $titleColumn[3],
                    ErrorCodes::getMessage('FILE_CT_ERROR_009', $expectedCount, $actualCount, $fileToFind),
                    ErrorCodes::FILE_CT_ERROR_009['code'],
                    $rowData[3],
                    $fileName
                );
            }
        }
    }

    /**
     * Verifica si una fecha en formato dd/mm/aaaa es válida.
     */
    private static function isValidDate(string $date): bool
    {
        $parts = explode('/', $date);
        if (count($parts) !== 3) {
            return false;
        }

        return checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]);
    }

    /**
     * Verifica si una fecha es posterior a la actual.
     */
    private static function isDateAfterToday(string $date): bool
    {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $date);

        return $dateTime > new \DateTime('today');
    }

    /**
     * Abre un archivo de texto con diferentes extensiones.
     */
    private static function openTextFile(string $filePath)
    {
        $extensions = ['.txt', '.TXT'];
        $handle = null;

        foreach ($extensions as $ext) {
            $fullPath = $filePath . $ext;
            if (file_exists($fullPath)) {
                $handle = fopen($fullPath, 'r');
                if ($handle !== false) {
                    break;
                }
            }
        }

        return $handle;
    }

    /**
     * Cuenta las filas de un archivo basado en su código.
     */
    private static function countFileRows(string $tempDir, string $codigoArchivo): ?int
    {
        $filePath = "$tempDir/$codigoArchivo";
        if (empty($filePath)) {
            return null;
        }

        $handle = self::openTextFile($filePath);
        if (!$handle) {
            return null;
        }

        $count = 0;
        while (fgetcsv($handle, 0, ',') !== false) {
            $count++;
        }

        fclose($handle);

        return $count;
    }
}
