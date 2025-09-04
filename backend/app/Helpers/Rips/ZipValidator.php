<?php

namespace App\Helpers\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ZipValidator
{
    protected static $allowedTypes = [
        'AC' => 17,
        'AF' => 17,
        'AH' => 19,
        'AM' => 14,
        'AN' => 14,
        'AP' => 15,
        'AT' => 11,
        'AU' => 17,
        'US' => 14,
        'CT' => 4,
    ];

    public static function validateAll(string $filePath, string $batchId): array
    {
        $fileName = basename($filePath);
        $isCritical = false;
        $errorMessages = [];

        if (!file_exists($filePath) || pathinfo($filePath, PATHINFO_EXTENSION) !== 'zip') {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_STR_001'),
                ErrorCodes::ZIP_STR_001['code'],
                null,
                $fileName
            );
            $isCritical = true;
            return ['isCritical' => $isCritical];
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_STR_001'),
                ErrorCodes::ZIP_STR_001['code'],
                null,
                $fileName
            );
            $isCritical = true;
            return ['isCritical' => $isCritical];
        }

        $fileCount = $zip->numFiles;
        $fileNames = [];
        $folderCount = 0;
        $counts = ['AF' => 0, 'US' => 0, 'CT' => 0, 'AC' => 0, 'AP' => 0, 'AM' => 0, 'AT' => 0, 'OTHER' => 0];
        $nonTxtExts = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/') {
                $folderCount++;
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== 'txt') {
                $nonTxtExts[] = $ext;
                $isCritical = true;
            } else {
                $fileNames[] = $name;
                $prefix = strtoupper(substr(basename($name), 0, 2));
                if (array_key_exists($prefix, $counts)) {
                    $counts[$prefix]++;
                } else {
                    $counts['OTHER']++;
                }
            }
        }

        if ($folderCount > 0) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_STR_002'),
                ErrorCodes::ZIP_STR_002['code'],
                $folderCount,
                $fileName
            );
            $isCritical = true;
        }

        if (!empty($nonTxtExts)) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_FMT_001'),
                ErrorCodes::ZIP_FMT_001['code'],
                implode(',', array_unique($nonTxtExts)),
                $fileName
            );
        }

        if (count($fileNames) < 4) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_STR_003'),
                ErrorCodes::ZIP_STR_003['code'],
                count($fileNames),
                $fileName
            );
        }

        if (count($fileNames) > 10) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_STR_004'),
                ErrorCodes::ZIP_STR_004['code'],
                count($fileNames),
                $fileName
            );
        }

        $hasACorSimilar = ($counts['AC'] >= 1 || $counts['AP'] >= 1 || $counts['AM'] >= 1 || $counts['AT'] >= 1);

        if ($counts['AF'] < 1) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_VAL_001'),
                ErrorCodes::ZIP_VAL_001['code'],
                null,
                $fileName
            );
        }

        if ($counts['US'] < 1) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_VAL_002'),
                ErrorCodes::ZIP_VAL_002['code'],
                null,
                $fileName
            );
        }

        if ($counts['CT'] < 1) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_VAL_003'),
                ErrorCodes::ZIP_VAL_003['code'],
                null,
                $fileName
            );
        }

        if (!$hasACorSimilar) {
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('ZIP_VAL_004'),
                ErrorCodes::ZIP_VAL_004['code'],
                null,
                $fileName
            );
        }

        $processedFiles = 0;
        foreach ($fileNames as $name) {
            $processedFiles++;
            $prefix = strtoupper(substr(basename($name), 0, 2));
            if (!array_key_exists($prefix, self::$allowedTypes)) continue;

            $contenido = $zip->getFromName($name);
            if (empty(trim($contenido))) {
                ErrorCollector::addError(
                    $batchId,
                    0,
                    null,
                    ErrorCodes::getMessage('TXT_STR_001'),
                    ErrorCodes::TXT_STR_001['code'],
                    null,
                    $name
                );
                continue;
            }

            $encoding = mb_detect_encoding($contenido, 'UTF-8, ISO-8859-1', true);
            if ($encoding !== 'UTF-8') {
                $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
            }

            $dataArray = FormatDataTxt::execute($contenido);
            ValidarLongitudElementos::execute($dataArray, $name, self::$allowedTypes[$prefix], $batchId);

            if ($processedFiles % 2 === 0 || $processedFiles === count($fileNames)) {
                event(new ImportProgressEvent(
                    $batchId,
                    $processedFiles,
                    'Validando archivo TXT',
                    count(ErrorCollector::getErrors($batchId)),
                    'active',
                    $name
                ));
            }
        }

        $zip->close();
        return ['isCritical' => $isCritical];
    }
}
