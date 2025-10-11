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

class RipXlsExport implements FromArray, WithHeadings, WithEvents,ShouldAutoSize
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function title(): string
    {
        return 'ListErrores'; // Cambia aquí al nombre que desees
    }

    public function array(): array
    {
        $data = [];

        // Agregar cada factura como una fila en el array de datos
        foreach ($this->invoices as $keyI => $invoice) {

            $requiredFields = ['tipoNota', 'numNota'];

            if (isset($invoice['numFactura'])) {

                // Verificar si faltan campos requeridos
                $this->formData($data, $requiredFields, $invoice, $invoice['numFactura']);

                //USUARIOS
                if (isset($invoice['usuarios']) && count($invoice['usuarios']) > 0) {
                    foreach ($invoice['usuarios'] as $keyU => $user) {

                        // Obtener todas las llaves de $user
                        $requiredFields = array_keys($user);

                        // Verificar si faltan campos requeridos
                        $this->formData($data, $requiredFields, $user, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion']);

                        //CONSULTAS
                        if (isset($user['servicios']['consultas']) && count($user['servicios']['consultas']) > 0) {
                            foreach ($user['servicios']['consultas'] as $keyC => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyC + 1, 'consultas');
                            }
                        }

                        //PROCEDIMIENTOS
                        if (isset($user['servicios']['procedimientos']) && count($user['servicios']['procedimientos']) > 0) {
                            // dd($user["servicios"]["procedimientos"]);
                            foreach ($user['servicios']['procedimientos'] as $keyP => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyP + 1, 'procedimientos');
                            }
                        }

                        //MEDICAMENTOS
                        if (isset($user['servicios']['medicamentos']) && count($user['servicios']['medicamentos']) > 0) {
                            foreach ($user['servicios']['medicamentos'] as $keyM => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyM + 1, 'medicamentos');
                            }
                        }

                        //URGENCIAS
                        if (isset($user['servicios']['urgencias']) && count($user['servicios']['urgencias']) > 0) {
                            foreach ($user['servicios']['urgencias'] as $keyU => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyU + 1, 'urgencias');
                            }
                        }

                        //OTROS SERVICIOS
                        if (isset($user['servicios']['otrosServicios']) && count($user['servicios']['otrosServicios']) > 0) {
                            foreach ($user['servicios']['otrosServicios'] as $keyOS => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyOS + 1, 'otrosServicios');
                            }
                        }

                        //HOSPITALIZACION
                        if (isset($user['servicios']['hospitalizacion']) && count($user['servicios']['hospitalizacion']) > 0) {
                            foreach ($user['servicios']['hospitalizacion'] as $keyH => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyH + 1, 'hospitalizacion');
                            }
                        }

                        //RECIEN NACIDOS
                        if (isset($user['servicios']['recienNacidos']) && count($user['servicios']['recienNacidos']) > 0) {
                            foreach ($user['servicios']['recienNacidos'] as $keyRN => $value) {

                                // Obtener todas las llaves
                                $requiredFields = array_keys($value);

                                // Verificar si faltan campos requeridos
                                $this->formData($data, $requiredFields, $value, $invoice['numFactura'], $keyU + 1, $user['numDocumentoIdentificacion'], $keyRN + 1, 'recienNacidos');
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
        ];
    }

    public function formData(&$array, $requiredFields, $value, $element1, $key1 = null, $element2 = null, $key2 = null, $campo = null)
    {
        foreach ($requiredFields as $field) {
            if ($value[$field] == Constants::EXCEL_GENERATION_KEY) {
                $array[] = [
                    $element1,
                    $key1,
                    $element2,
                    $key2,
                    $campo,
                    $field,
                    null,
                ];
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            // AfterSheet::class => function (AfterSheet $event) {
            //     $sheet = $event->sheet;

            //     // Obtener el rango de celdas con datos
            //     $highestColumn = $sheet->getHighestColumn();
            //     $highestRow = $sheet->getHighestRow();

            //     // Proteger la hoja
            //     $sheet->getProtection()->setSheet(true);
            //     // $sheet->getProtection()->setPassword('tu_contraseña'); // Opcional


            //     // Permitir redimensionar columnas (evita que el bloqueo general impida ajustar anchos)
            // $sheet->getProtection()->setFormatColumns(false);

            // // Opcional: Permitir redimensionar filas si lo necesitas
            // // $sheet->getProtection()->setFormatRows(false);


            //     // Desbloquear todas las celdas
            //     $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

            //     // Establecer el rango de bloqueo para columnas
            //     $protectedColumns = 'A:F';

            //     $sheet->getStyle($protectedColumns)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

            //     // Bloquear las columnas F en adelante
            //     $init = 'g1';
            //     $sheet->getStyle("$init:" . $highestColumn . '1')->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
            // },
        ];
    }
}
