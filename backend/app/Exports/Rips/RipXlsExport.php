<?php

namespace App\Exports\Rips;

use App\Helpers\Constants;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Protection;

use Maatwebsite\Excel\Events\AfterSheet;

// use Maatwebsite\Excel\Concerns\WithCustomStartCell;
// use Maatwebsite\Excel\Events\AfterSheet;
// use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class RipXlsExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $invoices; // ahora es un array de ['data' => json, 'obsByField' => []]

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function title(): string
    {
        return 'ListErrores';
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->invoices as $wrap) {
            // Extraer JSON de la factura y su mapa de observaciones
            $invoice = $wrap['data'] ?? [];
            $obsMap  = $wrap['obsByField'] ?? [];

            $requiredFields = ['tipoNota', 'numNota'];

            if (isset($invoice['numFactura'])) {

                // Verificar si faltan campos requeridos (observación por campo si existe)
                $this->formData($data, $requiredFields, $invoice, $invoice['numFactura'], null, null, null, null, /* observation */ null);

                // USUARIOS
                if (isset($invoice['usuarios']) && count($invoice['usuarios']) > 0) {
                    foreach ($invoice['usuarios'] as $keyU => $user) {

                        // Todas las llaves del usuario
                        $requiredFields = array_keys($user);

                        // Observación por campo cuando corresponda
                        $this->formData($data, $requiredFields, $user, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, null, null, $obsMap);

                        // CONSULTAS
                        if (!empty($user['servicios']['consultas'])) {
                            foreach ($user['servicios']['consultas'] as $keyC => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyC + 1, 'consultas', $obsMap);
                            }
                        }

                        // PROCEDIMIENTOS
                        if (!empty($user['servicios']['procedimientos'])) {
                            foreach ($user['servicios']['procedimientos'] as $keyP => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyP + 1, 'procedimientos', $obsMap);
                            }
                        }

                        // MEDICAMENTOS
                        if (!empty($user['servicios']['medicamentos'])) {
                            foreach ($user['servicios']['medicamentos'] as $keyM => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyM + 1, 'medicamentos', $obsMap);
                            }
                        }

                        // URGENCIAS
                        if (!empty($user['servicios']['urgencias'])) {
                            foreach ($user['servicios']['urgencias'] as $keyUrg => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyUrg + 1, 'urgencias', $obsMap);
                            }
                        }

                        // OTROS SERVICIOS
                        if (!empty($user['servicios']['otrosServicios'])) {
                            foreach ($user['servicios']['otrosServicios'] as $keyOS => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyOS + 1, 'otrosServicios', $obsMap);
                            }
                        }

                        // HOSPITALIZACION
                        if (!empty($user['servicios']['hospitalizacion'])) {
                            foreach ($user['servicios']['hospitalizacion'] as $keyH => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyH + 1, 'hospitalizacion', $obsMap);
                            }
                        }

                        // RECIEN NACIDOS
                        if (!empty($user['servicios']['recienNacidos'])) {
                            foreach ($user['servicios']['recienNacidos'] as $keyRN => $value) {
                                $requiredFields = array_keys($value);
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'] ?? null, $keyRN + 1, 'recienNacidos', $obsMap);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'num_factura',
            'id_usuario',
            'num_identificacion',
            'id_servicio',
            'servicio',
            'campo',
            'valor',
            'observaciones',
        ];
    }

    /**
     * @param array  $array           Salida (por referencia)
     * @param array  $requiredFields  Campos a revisar en $value
     * @param array  $value           Nodo actual (invoice/user/servicio)
     * @param mixed  $element1        numFactura
     * @param mixed  $key1            idx usuario
     * @param mixed  $element2        doc usuario
     * @param mixed  $key2            idx servicio
     * @param string $campo           tipo de servicio (consultas, procedimientos, etc.)
     * @param array|null $obsMap      Mapa campo => observación (puede ser null)
     */
    public function formData(
        &$array,
        $requiredFields,
        $value,
        $element1,
        $key1 = null,
        $element2 = null,
        $key2 = null,
        $campo = null,
        $obsMap = null
    ) {
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $value)) continue;

            if ($value[$field] == Constants::EXCEL_GENERATION_KEY) {
                $observation = is_array($obsMap) ? ($obsMap[$field] ?? null) : null;

                $array[] = [
                    $element1,            // num_factura
                    $key1,                // id_usuario (idx)
                    $element2,            // num_identificacion
                    $key2,                // id_servicio (idx)
                    $campo,               // servicio (consultas, procedimientos, etc.)
                    $field,               // campo
                    null,                 // valor (no lo usas aquí; deja null para indicar 'marcado')
                    $observation,         // observaciones
                ];
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $highestColumn = $sheet->getHighestColumn();
                $highestRow    = $sheet->getHighestRow();

                // 1) Aplicar formato TEXTO a todas las celdas con datos
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

                // 2) Proteger la hoja
                $sheet->getProtection()->setSheet(true);
                // $sheet->getProtection()->setPassword('opcional');

                // 3) Bloquear TODO el rango con datos
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);

                // 4) Desbloquear SOLO la columna G (de preferencia sin el encabezado)
                if ($highestRow >= 2) {
                    $sheet->getStyle('G2:G' . $highestRow)
                        ->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
                }
            },
        ];
    }
}
