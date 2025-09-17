<?php

namespace App\Helpers\Rips;

use App\Events\ImportProgressEvent;
use App\Helpers\Common\ErrorCollector;
use App\Models\RipInvoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Saloon\XmlWrangler\XmlReader;

class XmlValidator
{

    public static function validateAll(string $batchId, string $filepath)
    {

        $xmlData = [];

        $success = self::validationFileXml($batchId, $filepath);

        $xmlData = $success['xmlData'];

        $attachedDocument = $xmlData['AttachedDocument'];
        $validation = $attachedDocument['cac:ParentDocumentLineReference']['cac:DocumentReference']['cac:ResultOfVerification'];
        self::validationResultCode($batchId, $validation['cbc:ValidationResultCode']);

        $redis = Redis::connection('redis_6380');
        $metadata = $redis->hgetall("batch:{$batchId}:metadata");
        $rip_invoice = RipInvoice::select(['id', 'invoice_number', 'rip_id'])->with('rip:id,nit')->find($metadata['invoice_id']);
        $numFactDB = $rip_invoice['invoice_number'] ?? null;
        $nitRipsDB = $rip_invoice?->rip?->nit ?? null;

        $numFactXml = $attachedDocument['cbc:ID'];
        self::RVC004($batchId, $numFactXml, $numFactDB);

        $nitSenderVendorXml = $attachedDocument['cac:SenderParty']['cac:PartyTaxScheme']['cbc:CompanyID'];
        self::validationNitSenderVendor($batchId, $nitRipsDB, $nitSenderVendorXml);
    }

    protected static function validationFileXml($batchId, $archiveXml)
    {
        $xmlData = [];

        try {
            $reader = XmlReader::fromFile($archiveXml);
            $xmlData = $reader->values(); // Array of values.
        } catch (\Throwable $th) {

            // Validaciones iniciales del XML
            ErrorCollector::addError(
                $batchId,
                0,
                null,
                ErrorCodes::getMessage('FILE_XML_ERROR_001'),
                ErrorCodes::FILE_XML_ERROR_001['code'],
                null,
                basename($archiveXml)
            );
        }

        return [
            'xmlData' => $xmlData,
        ];
    }

    protected static function validationResultCode($batchId, $value)
    {
        if ($value != '02') {

            ErrorCollector::addError(
                $batchId,
                0,
                'AttachedDocument -> cac:ParentDocumentLineReference -> cac:DocumentReference -> cac:ResultOfVerification -> cbc:ValidationResultCode',
                ErrorCodes::getMessage('FILE_XML_ERROR_002'),
                ErrorCodes::FILE_XML_ERROR_002['code'],
                null,
                $value
            );
        }
    }

    protected static function RVC004($batchId, $numFactXml, $numFactDB)
    {
        if ($numFactDB != $numFactXml) {
            ErrorCollector::addError(
                $batchId,
                0,
                'AttachedDocument -> cbc:ID',
                ErrorCodes::getMessage('FILE_XML_ERROR_003'),
                ErrorCodes::FILE_XML_ERROR_003['code'],
                null,
                $numFactXml
            );
        }
    }

    protected static function validationNitSenderVendor($batchId, $nitRipsDB, $nitSenderVendorXml)
    {
        if ($nitRipsDB != $nitSenderVendorXml) {

            ErrorCollector::addError(
                $batchId,
                0,
                'AttachedDocument -> cac:SenderParty -> cac:PartyTaxScheme -> cbc:CompanyID -> cbc:ID',
                ErrorCodes::getMessage('FILE_XML_ERROR_004'),
                ErrorCodes::FILE_XML_ERROR_004['code'],
                null,
                $nitSenderVendorXml
            );
        }
    }
}
