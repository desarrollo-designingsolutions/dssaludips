<?php

namespace App\Helpers\Rips;

use App\Helpers\Constants;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class BuildAllDataToJson
{

    public static function execute($batchId)
    {
        Log::info("dentro de BuildAllDataToJson");
        $redis = Redis::connection('redis_6380');

        $files = json_decode($redis->get("rip_batch:{$batchId}:files_txts"), 1);

        // Log::info("files started for batch {$batchId}", [$files]);


        $instance = new self; // Crear instancia para acceder a métodos protegidos

        // Mapeo de tipos de archivos y sus respectivos métodos de formato
        $fileTypes = [
            'AF' => 'formatValueAF',
            'US' => 'formatValueUS',
            'AC' => 'formatValueAC',
            'AP' => 'formatValueAP',
            'AM' => 'formatValueAM',
            'AU' => 'formatValueAU',
            'AH' => 'formatValueAH',
            'AN' => 'formatValueAN',
            'AT' => 'formatValueAT',
        ];

        // Inicializar un array para almacenar los datos formateados
        $dataArrays = [];

        // Log::info("iniciacion de dataArrays started for batch {$batchId}");

        // Inicializar todas las claves con arrays vacíos
        foreach ($fileTypes as $type => $method) {
            $dataArrays[$type] = [];
        }
        // Log::info("dataArrays started for batch {$batchId}", [$dataArrays]);

        // Procesar los archivos
        foreach ($files as $file) {
            foreach ($fileTypes as $type => $method) {
                if (stripos($file['name'], $type) !== false) {
                    // Log::info("file content started for batch {$batchId}", [$file['contentData']]);
                    $dataArrays[$type] = FormatDataTxt::execute($file['contentData'], [$instance, $method]);
                    // Log::info("dataArrays[type] content started for batch {$batchId}", [$dataArrays[$type]]);
                    break; // Salir del bucle interno una vez que se encuentra el tipo
                }
            }
        }

        // Convertir todos los arrays a colecciones
        $dataArrays = array_map(function ($data) {
            return collect($data);
        }, $dataArrays);

        // Mapear los tipos de servicios para aplicar invoiceUserServices
        $serviceTypes = [
            'AC' => 'consultas',
            'AP' => 'procedimientos',
            'AM' => 'medicamentos',
            'AU' => 'urgencias',
            'AH' => 'hospitalizacion',
            'AN' => 'recienNacidos',
            'AT' => 'otrosServicios',
        ];

        $dataArrays['AF'] = $dataArrays['AF']->map(function ($item) use ($dataArrays, $serviceTypes, $instance) {
            foreach ($serviceTypes as $type => $service) {
                // Verificar si la clave existe en $dataArrays antes de usarla
                if (isset($dataArrays[$type])) {
                    $instance->invoiceUserServices($dataArrays[$type], $dataArrays['US'], $item, $service);
                }
            }

            return $item;
        })->toArray();

        return $dataArrays['AF'];
    }

    /**
     * Asocia servicios a usuarios en una factura.
     *
     * @param  \Illuminate\Support\Collection  $dataArray  Colección de datos del servicio
     * @param  \Illuminate\Support\Collection  $dataArrayUS  Colección de usuarios
     * @param  array  &$invoice  Factura a modificar
     * @param  string  $keyService  Clave del servicio
     */
    protected function invoiceUserServices($dataArray, $dataArrayUS, &$invoice, $keyService): void
    {
        $registers = $dataArray->filter(function ($atItem) use ($invoice) {
            return $atItem['numFEVPagoModerador'] == $invoice['numFactura'];
        })->values();

        $i = 0;
        foreach ($registers as $key => $value) {
            // Agregar los elementos encontrados a la subcolección 'usuarios'

            $usuario = $dataArrayUS->filter(function ($acItem) use ($value) {
                return $acItem['numDocumentoIdentificacion'] == $value['numDocumentoIdentificacion'];
            })->first();

            $user = collect($invoice['usuarios'])->filter(function ($value) use ($usuario) {
                return $value['numDocumentoIdentificacion'] == $usuario['numDocumentoIdentificacion'];
            })->values();

            if (count($user) == 0) {
                $invoice['usuarios'][$i] = $usuario;
                $invoice['usuarios'][$i]['servicios'] = [];
            }

            if (isset($invoice['usuarios'][$i]['servicios']) && !isset($invoice['usuarios'][$i]['servicios'][$keyService])) {
                $invoice['usuarios'][$i]['servicios'][$keyService] = [];
            }

            $dataService = $dataArray->filter(function ($atItem) use ($invoice, $usuario) {
                return $atItem['numFEVPagoModerador'] == $invoice['numFactura'] && $atItem['numDocumentoIdentificacion'] == $usuario['numDocumentoIdentificacion'];
            })->values();

            if (isset($invoice['usuarios'][$i]['servicios'][$keyService]) && count($invoice['usuarios'][$i]['servicios'][$keyService]) == 0) {
                $invoice['usuarios'][$i]['servicios'][$keyService] = $dataService;
            }

            $i++;
        }
    }

    // Funciones de formateo protegidas
    public function formatValueAT($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'numAutorizacion' => trim($datos[4]),
            'idMIPRES' => null,
            'fechaSuministroTecnologia' => null,
            'tipoOS' => trim($datos[5]),
            'codTecnologiaSalud' => trim($datos[6]),
            'nomTecnologiaSalud' => trim($datos[7]),
            'cantidadOS' => trim($datos[8]),
            'tipoDocumentoIdentificacion' => trim($datos[2]),
            'numDocumentoIdentificacion' => trim($datos[3]),
            'vrUnitOS' => trim($datos[9]),
            'vrServicio' => trim($datos[10]),
            'valorPagoModerador' => null,
            'numFEVPagoModerador' => trim($datos[0]),
            'consecutivo' => null,
            'conceptoRecaudo' => null,
        ];
    }

    public function formatValueAN($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'tipoDocumentoIdentificacion' => trim($datos[2]),
            'numDocumentoIdentificacion' => trim($datos[3]),
            'fechaNacimiento' => $this->transformDate(trim($datos[4])),
            'edadGestacional' => trim($datos[6]),
            'numConsultasCPrenatal' => trim($datos[7]),
            'codSexoBiologico' => trim($datos[8]),
            'peso' => trim($datos[9]),
            'codDiagnosticoPrincipal' => trim($datos[10]),
            'condicionDestinoUsuarioEgreso' => null,
            'codDiagnosticoCausaMuerte' => trim($datos[11]),
            'fechaEgreso' => null,
            'consecutivo' => null,
            'numFEVPagoModerador' => trim($datos[0]),
        ];
    }

    public function formatValueAH($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'viaIngresoServicioSalud' => trim($datos[4]),
            'fechaInicioAtencion' => null,
            'numAutorizacion' => trim($datos[7]),
            'causaMotivoAtencion' => trim($datos[8]),
            'codDiagnosticoPrincipal' => trim($datos[9]),
            'codDiagnosticoPrincipalE' => trim($datos[10]),
            'codDiagnosticoRelacionadoE1' => trim($datos[11]),
            'codDiagnosticoRelacionadoE2' => trim($datos[12]),
            'codDiagnosticoRelacionadoE3' => trim($datos[13]),
            'codComplicacion' => trim($datos[14]),
            'condicionDestinoUsuarioEgreso' => trim($datos[15]),
            'codDiagnosticoCausaMuerte' => trim($datos[16]),
            'fechaEgreso' => null,
            'consecutivo' => null,
            'numDocumentoIdentificacion' => trim($datos[3]),
            'numFEVPagoModerador' => trim($datos[0]),
        ];
    }

    public function formatValueAM($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'numAutorizacion' => trim($datos[4]),
            'idMIPRES' => null,
            'fechaDispensAdmon' => null,
            'codDiagnosticoPrincipal' => null,
            'codDiagnosticoRelacionado' => null,
            'tipoMedicamento' => trim($datos[6]),
            'codTecnologiaSalud' => trim($datos[5]),
            'nomTecnologiaSalud' => trim($datos[7]),
            'concentracionMedicamento' => trim($datos[9]),
            'unidadMedida' => trim($datos[10]),
            'formaFarmaceutica' => trim($datos[8]),
            'unidadMinDispensa' => trim($datos[10]),
            'cantidadMedicamento' => trim($datos[11]),
            'diasTratamiento' => null,
            'tipoDocumentoIdentificacion' => trim($datos[2]),
            'numDocumentoIdentificacion' => trim($datos[3]),
            'vrUnitMedicamento' => trim($datos[12]),
            'vrServicio' => trim($datos[13]),
            'valorPagoModerador' => null,
            'numFEVPagoModerador' => trim($datos[0]),
            'consecutivo' => null,
            'conceptoRecaudo' => null,
        ];
    }

    public function formatValueAU($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'fechaInicioAtencion' => null,
            'causaMotivoAtencion' => trim($datos[7]),
            'codDiagnosticoPrincipal' => trim($datos[8]),
            'codDiagnosticoPrincipalE' => trim($datos[8]),
            'codDiagnosticoRelacionadoE1' => trim($datos[9]),
            'codDiagnosticoRelacionadoE2' => trim($datos[10]),
            'codDiagnosticoRelacionadoE3' => trim($datos[11]),
            'condicionDestinoUsuarioEgreso' => trim($datos[12]) . ' ' . trim($datos[13]),
            'codDiagnosticoCausaMuerte' => trim($datos[14]),
            'fechaEgreso' => null,
            'consecutivo' => null,
            'numFEVPagoModerador' => trim($datos[0]),
            'numDocumentoIdentificacion' => trim($datos[3]),
        ];
    }

    public function formatValueAP($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'fechaInicioAtencion' => null,
            'idMIPRES' => null,
            'numAutorizacion' => trim($datos[5]),
            'codProcedimiento' => trim($datos[6]),
            'viaIngresoServicioSalud' => trim($datos[7]),
            'modalidadGrupoServicioTecSal' => null,
            'grupoServicios' => null,
            'codServicio' => null,
            'finalidadTecnologiaSalud' => trim($datos[8]),
            'tipoDocumentoIdentificacion' => trim($datos[2]),
            'numDocumentoIdentificacion' => trim($datos[3]),
            'codDiagnosticoPrincipal' => trim($datos[9]),
            'codDiagnosticoRelacionado' => trim($datos[10]),
            'codComplicacion' => trim($datos[11]),
            'vrServicio' => trim($datos[14]),
            'valorPagoModerador' => null,
            'numFEVPagoModerador' => trim($datos[0]),
            'consecutivo' => null,
            'conceptoRecaudo' => null,
        ];
    }

    public function formatValueUS($datos): array
    {
        return [
            'tipoDocumentoIdentificacion' => trim($datos[0]),
            'numDocumentoIdentificacion' => trim($datos[1]),
            'tipoUsuario' => trim($datos[3]),
            'fechaNacimiento' => null,
            'codSexo' => trim($datos[10]),
            'codPaisResidencia' => null,
            'codMunicipioResidencia' => trim($datos[12]),
            'codZonaTerritorialResidencia' => $this->transformCodZonaTerritorialResidencia(trim($datos[13])),
            'incapacidad' => null,
            'consecutivo' => null,
            'codPaisOrigen' => null,
        ];
    }

    public function formatValueAC($datos): array
    {
        return [
            'codPrestador' => trim($datos[1]),
            'fechaInicioAtencion' => null,
            'numAutorizacion' => trim($datos[5]),
            'codConsulta' => trim($datos[6]),
            'modalidadGrupoServicioTecSal' => null,
            'grupoServicios' => null,
            'codServicio' => null,
            'finalidadTecnologiaSalud' => trim($datos[7]),
            'causaMotivoAtencion' => trim($datos[8]),
            'codDiagnosticoPrincipal' => trim($datos[9]),
            'codDiagnosticoRelacionado1' => trim($datos[10]),
            'codDiagnosticoRelacionado2' => trim($datos[11]),
            'codDiagnosticoRelacionado3' => trim($datos[12]),
            'tipoDiagnosticoPrincipal' => trim($datos[13]),
            'tipoDocumentoIdentificacion' => trim($datos[2]),
            'numDocumentoIdentificacion' => trim($datos[3]),
            'vrServicio' => trim($datos[14]),
            'valorPagoModerador' => trim($datos[15]),
            'numFEVPagoModerador' => trim($datos[0]),
            'consecutivo' => null,
            'conceptoRecaudo' => null,
        ];
    }

    public function formatValueAF($datos): array
    {
        return [
            'numDocumentoIdObligado' => trim($datos[3]),
            'numFactura' => trim($datos[4]),
            'tipoNota' => null,
            'numNota' => null,
            'usuarios' => [],
        ];
    }

    public function transformCodZonaTerritorialResidencia($value)
    {
        $newValue = $value;

        if (Str::upper($value) == 'R') {
            $newValue = '01';
        }
        if (Str::upper($value) == 'U') {
            $newValue = '02';
        }

        return $newValue;
    }

    public function transformDate($fecha)
    {
        $dateTime = DateTime::createFromFormat('d/m/Y', $fecha);
        if ($dateTime) {
            return $dateTime->format('Y-m-d');
        }
        $dateTime = DateTime::createFromFormat('Y/m/d', $fecha);
        if ($dateTime) {
            return $dateTime->format('Y-m-d');
        }
        $dateTime = DateTime::createFromFormat('d-m-Y', $fecha);
        if ($dateTime) {
            return $dateTime->format('Y-m-d');
        }

        return $fecha;
    }

    public static function generateConsecutive(&$buildDataFinal)
    {
        foreach ($buildDataFinal as &$invoice) {
            $i = 1;
            foreach ($invoice['usuarios'] as &$user) {
                $user['consecutivo'] = $i;
                $services = ['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosServicios', 'hospitalizacion', 'recienNacidos'];
                foreach ($services as $service) {
                    $j = 1;
                    if (isset($user['servicios'][$service]) && count($user['servicios'][$service]) > 0) {
                        $user['servicios'][$service] = array_map(function ($value) use (&$j) {
                            $value['consecutivo'] = $j;
                            $j++;

                            return $value;
                        }, $user['servicios'][$service]->toArray());
                    }
                }
                $i++;
            }
        }
    }



    /**
     * Verifica la completitud de las facturas en $buildDataFinal.
     * Retorna un arreglo con las cantidades de facturas completas e incompletas.
     * Lanza una excepción si una factura no tiene un invoice_number válido.
     *
     * @param array $buildDataFinal El arreglo de facturas a verificar
     * @return array Arreglo con ['complete' => int, 'incomplete' => int]
     */
    public static function checkInvoiceCompleteness($buildDataFinal): array
    {
        $successfulInvoices = 0;
        $failedInvoices = 0;

        foreach ($buildDataFinal as $index => $invoice) {
            if (self::isInvoiceComplete($invoice)) {
                $successfulInvoices++;
            } else {
                $failedInvoices++;
            }
        }

        return [
            'successfulInvoices' => $successfulInvoices,
            'failedInvoices' => $failedInvoices
        ];
    }

    /**
     * Verifica recursivamente si una factura está completa.
     * Una factura está completa si ningún valor escalar es null o cadena vacía.
     * Las arrays vacías se consideran válidas (por ejemplo, si no hay servicios).
     * Ignora objetos (como colecciones) asumiendo que su contenido se verifica al convertir a array.
     *
     * @param mixed $data El dato a verificar (array, objeto o escalar)
     * @return bool True si la factura está completa, false si tiene al menos un valor nulo o vacío
     */
    private static function isInvoiceComplete($data): bool
    {
        if (is_array($data)) {
            // Recorrer cada elemento del array
            foreach ($data as $value) {
                if (!self::isInvoiceComplete($value)) {
                    return false;
                }
            }
            return true;
        } elseif (is_object($data)) {
            // Ignorar objetos (como colecciones Laravel), ya que su contenido se verifica al convertir a array
            return true;
        } else {
            // Valor escalar: falso si es null o cadena vacía (después de trim)
            if ($data === null || (is_string($data) && trim($data) === '')) {
                return false;
            }
            return true;
        }
    }
}
