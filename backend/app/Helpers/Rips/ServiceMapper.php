<?php

namespace App\Helpers\Rips;

use App\Http\Resources\CatalogoCum\CatalogoCumSelectResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// modelos y resources que usabas en getManualInfoServices
use App\Models\CupsRips;
use App\Models\ModalidadAtencion;
use App\Models\GrupoServicio;
use App\Models\Servicio;
use App\Models\RipsFinalidadConsultaVersion2;
use App\Models\RipsCausaExternaVersion2;
use App\Models\Cie10;
use App\Models\RipsTipoDiagnosticoPrincipalVersion2;
use App\Models\ConceptoRecaudo;

use App\Http\Resources\Cie10\Cie10SelectInfiniteResource;
use App\Http\Resources\ConceptoRecaudo\ConceptoRecaudoSelectResource;
use App\Http\Resources\CondicionyDestinoUsuarioEgreso\CondicionyDestinoUsuarioEgresoSelectInfiniteResource;
use App\Http\Resources\CupsRips\CupsRipsSelectInfiniteResource;
use App\Http\Resources\Dci\DciSelectResource;
use App\Http\Resources\Ffm\FfmSelectResource;
use App\Http\Resources\GrupoServicio\GrupoServicioSelectInfiniteResource;
use App\Http\Resources\Ium\IumSelectResource;
use App\Http\Resources\ModalidadAtencion\ModalidadAtencionSelectInfiniteResource;
use App\Http\Resources\RipsCausaExternaVersion2\RipsCausaExternaVersion2SelectInfiniteResource;
use App\Http\Resources\RipsFinalidadConsultaVersion2\RipsFinalidadConsultaVersion2SelectInfiniteResource;
use App\Http\Resources\RipsTipoDiagnosticoPrincipalVersion2\RipsTipoDiagnosticoPrincipalVersion2SelectInfiniteResource;
use App\Http\Resources\Servicio\ServicioSelectInfiniteResource;
use App\Http\Resources\Sexo\SexoSelectResource;
use App\Http\Resources\TipoMedicamentoPosVersion2\TipoMedicamentoPosVersion2SelectInfiniteResource;
use App\Http\Resources\TipoOtrosServicios\TipoOtrosServiciosSelectResource;
use App\Http\Resources\Umm\UmmSelectInfiniteResource;
use App\Http\Resources\Upr\UprSelectResource;
use App\Http\Resources\ViaIngresoUsuario\ViaIngresoUsuarioSelectInfiniteResource;
use App\Models\CatalogoCum;
use App\Models\CondicionyDestinoUsuarioEgreso;
use App\Models\Dci;
use App\Models\Ffm;
use App\Models\Ium;
use App\Models\Sexo;
use App\Models\TipoMedicamentoPosVersion2;
use App\Models\TipoOtrosServicios;
use App\Models\Umm;
use App\Models\Upr;
use App\Models\ViaIngresoUsuario;

class ServiceMapper
{
    /**
     * Config mínima: serviceKey (lowercase) => table name
     * Si tus tablas tienen otro nombre, cámbialo aquí.
     */
    private static array $tableConfig = [
        'consultas' => 'rip_service_queries',
        'procedimientos' => 'rip_service_procedures',
        'medicamentos' => 'rip_service_medicines',
        'urgencias' => 'rip_service_urgencies',
        'otrosservicios' => 'rip_service_other_services',
        'hospitalizacion' => 'rip_service_hospitalizations',
        'reciennacidos' => 'rip_service_newly_borns',
    ];

    /**
     * Devuelve la tabla asociada al tipo de servicio (normaliza)
     */
    public static function tableFor(string $serviceType): ?string
    {
        $k = mb_strtolower($serviceType);
        // normalizaciones simples
        $k = str_replace([' ', '_', '-'], '', $k);

        return self::$tableConfig[$k] ?? null;
    }

    /**
     * Extrae un 'codigo' / 'value' / 'title' o el propio valor
     */
    private static function extractCode($val)
    {
        if (is_null($val)) return null;
        if (is_object($val)) $val = (array)$val;
        if (is_array($val)) {
            if (!empty($val['codigo'])) return $val['codigo'];
            if (!empty($val['value'])) return $val['value'];
            if (!empty($val['title'])) return $val['title'];
            foreach ($val as $v) if ($v !== null && $v !== '') return $v;
            return null;
        }
        return $val;
    }

    /**
     * Construye el payload para la tabla correspondiente según tipo de servicio.
     * - $serviceType: 'consultas', 'procedimientos', ...
     * - $svc: array enviado por front
     * - $ripInvoiceUser: registro de rip_invoice_users (o null)
     * - $codPrestador: string con codigo IPS
     * - $invoiceModel: modelo invoice (opcional) para numFEV
     */
    public static function buildDbPayload(string $serviceType, array $svc, $ripInvoiceUser = null, ?string $codPrestador = null, $invoiceModel = null): array
    {
        $k = mb_strtolower($serviceType);
        $k = str_replace([' ', '_', '-'], '', $k);

        $base = [
            'rip_invoice_user_id' => $ripInvoiceUser->id ?? null,
            'codPrestador' => $codPrestador,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // CONSULTAS
        if ($k === 'consultas') {
            return array_merge($base, [
                'fechaInicioAtencion' => $svc['fechaInicioAtencion'] ?? null,
                'numAutorizacion' => $svc['numAutorizacion'] ?? null,
                'codConsulta' => self::extractCode($svc['codConsulta'] ?? null),
                'modalidadGrupoServicioTecSal' => self::extractCode($svc['modalidadGrupoServicioTecSal'] ?? null),
                'grupoServicios' => self::extractCode($svc['grupoServicios'] ?? null),
                'codServicio' => self::extractCode($svc['codServicio'] ?? null),
                'finalidadTecnologiaSalud' => self::extractCode($svc['finalidadTecnologiaSalud'] ?? null),
                'causaMotivoAtencion' => self::extractCode($svc['causaMotivoAtencion'] ?? null),
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'codDiagnosticoRelacionado1' => self::extractCode($svc['codDiagnosticoRelacionado1'] ?? null),
                'codDiagnosticoRelacionado2' => self::extractCode($svc['codDiagnosticoRelacionado2'] ?? null),
                'codDiagnosticoRelacionado3' => self::extractCode($svc['codDiagnosticoRelacionado3'] ?? null),
                'tipoDiagnosticoPrincipal' => self::extractCode($svc['tipoDiagnosticoPrincipal'] ?? null),
                'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
                'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                'vrServicio' => isset($svc['vrServicio']) ? (float) str_replace(',', '.', $svc['vrServicio']) : 0,
                'conceptoRecaudo' => self::extractCode($svc['conceptoRecaudo'] ?? null),
                'valorPagoModerador' => isset($svc['valorPagoModerador']) ? (float) str_replace(',', '.', $svc['valorPagoModerador']) : 0,
                'numFEVPagoModerador' => $invoiceModel->invoice_number ?? ($svc['numFEVPagoModerador'] ?? null),
            ]);
        }

        // PROCEDIMIENTOS
        if ($k === 'procedimientos') {
            return array_merge($base, [
                'fechaInicioAtencion' => $svc['fechaInicioAtencion'] ?? null,
                'idMIPRES' => $svc['idMIPRES'] ?? null,
                'numAutorizacion' => $svc['numAutorizacion'] ?? null,
                'codProcedimiento' => self::extractCode($svc['codProcedimiento'] ?? $svc['codProcedimiento'] ?? null),
                'viaIngresoServicioSalud' => self::extractCode($svc['viaIngresoServicioSalud'] ?? null),
                'modalidadGrupoServicioTecSal' => self::extractCode($svc['modalidadGrupoServicioTecSal'] ?? null),
                'grupoServicios' => self::extractCode($svc['grupoServicios'] ?? null),
                'codServicio' => self::extractCode($svc['codServicio'] ?? null),
                'finalidadTecnologiaSalud' => self::extractCode($svc['finalidadTecnologiaSalud'] ?? null),
                'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
                'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'codDiagnosticoRelacionado' => self::extractCode($svc['codDiagnosticoRelacionado'] ?? null),
                'codComplicacion' => self::extractCode($svc['codComplicacion'] ?? null),
                'vrServicio' => isset($svc['vrServicio']) ? (float) str_replace(',', '.', $svc['vrServicio']) : 0,
                'conceptoRecaudo' => self::extractCode($svc['conceptoRecaudo'] ?? null),
                'valorPagoModerador' => isset($svc['valorPagoModerador']) ? (float) str_replace(',', '.', $svc['valorPagoModerador']) : 0,
                'numFEVPagoModerador' => $invoiceModel->invoice_number ?? ($svc['numFEVPagoModerador'] ?? null),
            ]);
        }

        // URGENCIAS
        if ($k === 'urgencias') {
            return array_merge($base, [
                'fechaInicioAtencion' => $svc['fechaInicioAtencion'] ?? null,
                'causaMotivoAtencion' => self::extractCode($svc['causaMotivoAtencion'] ?? null),
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'codDiagnosticoPrincipalE' => self::extractCode($svc['codDiagnosticoPrincipalE'] ?? null),
                'codDiagnosticoRelacionadoE1' => self::extractCode($svc['codDiagnosticoRelacionadoE1'] ?? null),
                'codDiagnosticoRelacionadoE2' => self::extractCode($svc['codDiagnosticoRelacionadoE2'] ?? null),
                'codDiagnosticoRelacionadoE3' => self::extractCode($svc['codDiagnosticoRelacionadoE3'] ?? null),
                'condicionDestinoUsuarioEgreso' => self::extractCode($svc['condicionDestinoUsuarioEgreso'] ?? null),
                'codDiagnosticoCausaMuerte' => self::extractCode($svc['codDiagnosticoCausaMuerte'] ?? null),
                'fechaEgreso' => $svc['fechaEgreso'] ?? null,
            ]);
        }

        // HOSPITALIZACION
        if ($k === 'hospitalizacion' || $k === 'hospitalizaciOn') {
            return array_merge($base, [
                'viaIngresoServicioSalud' => self::extractCode($svc['viaIngresoServicioSalud'] ?? null),
                'fechaInicioAtencion' => $svc['fechaInicioAtencion'] ?? null,
                'numAutorizacion' => $svc['numAutorizacion'] ?? null,
                'causaMotivoAtencion' => self::extractCode($svc['causaMotivoAtencion'] ?? null),
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'codDiagnosticoPrincipalE' => self::extractCode($svc['codDiagnosticoPrincipalE'] ?? null),
                'codDiagnosticoRelacionadoE1' => self::extractCode($svc['codDiagnosticoRelacionadoE1'] ?? null),
                'codDiagnosticoRelacionadoE2' => self::extractCode($svc['codDiagnosticoRelacionadoE2'] ?? null),
                'codDiagnosticoRelacionadoE3' => self::extractCode($svc['codDiagnosticoRelacionadoE3'] ?? null),
                'codComplicacion' => self::extractCode($svc['codComplicacion'] ?? null),
                'condicionDestinoUsuarioEgreso' => self::extractCode($svc['condicionDestinoUsuarioEgreso'] ?? null),
                'codDiagnosticoCausaMuerte' => self::extractCode($svc['codDiagnosticoCausaMuerte'] ?? null),
                'fechaEgreso' => $svc['fechaEgreso'] ?? null,
            ]);
        }

        // RECIEN NACIDOS
        if ($k === 'reciennacidos') {
            return array_merge($base, [
                'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
                'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                'fechaNacimiento' => $svc['fechaNacimiento'] ?? null,
                'edadGestacional' => $svc['edadGestacional'] ?? null,
                'numConsultasCPrenatal' => $svc['numConsultasCPrenatal'] ?? null,
                'codSexoBiologico' => self::extractCode($svc['codSexoBiologico'] ?? null),
                'peso' => $svc['peso'] ?? null,
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'condicionDestinoUsuarioEgreso' => self::extractCode($svc['condicionDestinoUsuarioEgreso'] ?? null),
                'codDiagnosticoCausaMuerte' => self::extractCode($svc['codDiagnosticoCausaMuerte'] ?? null),
                'fechaEgreso' => $svc['fechaEgreso'] ?? null,
            ]);
        }

        // MEDICAMENTOS
        if ($k === 'medicamentos') {
            logMessage($svc['codTecnologiaSaludable_id']);
            return array_merge($base, [
                'numAutorizacion' => $svc['numAutorizacion'] ?? null,
                'idMIPRES' => $svc['idMIPRES'] ?? null,
                'fechaDispensAdmon' => $svc['fechaDispensAdmon'] ?? null,
                'codDiagnosticoPrincipal' => self::extractCode($svc['codDiagnosticoPrincipal'] ?? null),
                'codDiagnosticoRelacionado' => self::extractCode($svc['codDiagnosticoRelacionado'] ?? null),
                'tipoMedicamento' => self::extractCode($svc['tipoMedicamento'] ?? null),
                'codTecnologiaSaludable_type' => $svc['codTecnologiaSaludable_type'] ?? null,
                'codTecnologiaSaludable_id' => $svc['codTecnologiaSaludable_id']['value'] ?? null,
                'codTecnologiaSalud' => self::extractCode($svc['codTecnologiaSaludable_id'] ?? null),
                'nomTecnologiaSalud' => $svc['codTecnologiaSaludable_id'] ? $svc['codTecnologiaSaludable_id']['nombre'] : null,
                'concentracionMedicamento' => $svc['concentracionMedicamento'] ?? null,
                'unidadMedida' => self::extractCode($svc['unidadMedida'] ?? null),
                'formaFarmaceutica' => self::extractCode($svc['formaFarmaceutica'] ?? null),
                'unidadMinDispensa' => self::extractCode($svc['unidadMinDispensa'] ?? null),
                'cantidadMedicamento' => $svc['cantidadMedicamento'] ?? null,
                'diasTratamiento' => $svc['diasTratamiento'] ?? null,
                'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
                'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                'vrUnitMedicamento' => isset($svc['vrUnitMedicamento']) ? (float) str_replace(',', '.', $svc['vrUnitMedicamento']) : null,
                'vrServicio' => isset($svc['vrServicio']) ? (float) str_replace(',', '.', $svc['vrServicio']) : null,
                'conceptoRecaudo' => self::extractCode($svc['conceptoRecaudo'] ?? null),
                'valorPagoModerador' => isset($svc['valorPagoModerador']) ? (float) str_replace(',', '.', $svc['valorPagoModerador']) : null,
                'numFEVPagoModerador' => $invoiceModel->invoice_number ?? ($svc['numFEVPagoModerador'] ?? null),
            ]);
        }

        // OTROS SERVICIOS
        if ($k === 'otrosservicios') {
            return array_merge($base, [
                'numAutorizacion' => $svc['numAutorizacion'] ?? null,
                'idMIPRES' => $svc['idMIPRES'] ?? null,
                'fechaSuministroTecnologia' => $svc['fechaSuministroTecnologia'] ?? null,
                'tipoOS' => self::extractCode($svc['tipoOS'] ?? null),
                'codTecnologiaSalud' => self::extractCode($svc['codTecnologiaSalud'] ?? null),
                'nomTecnologiaSalud' => $svc['nomTecnologiaSalud'] ?? null,
                'cantidadOS' => $svc['cantidadOS'] ?? null,
                'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
                'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
                'vrUnitOS' => isset($svc['vrUnitOS']) ? (float) str_replace(',', '.', $svc['vrUnitOS']) : null,
                'vrServicio' => isset($svc['vrServicio']) ? (float) str_replace(',', '.', $svc['vrServicio']) : null,
                'conceptoRecaudo' => self::extractCode($svc['conceptoRecaudo'] ?? null),
                'valorPagoModerador' => isset($svc['valorPagoModerador']) ? (float) str_replace(',', '.', $svc['valorPagoModerador']) : null,
                'numFEVPagoModerador' => $invoiceModel->invoice_number ?? ($svc['numFEVPagoModerador'] ?? null),
            ]);
        }


        // FALLBACK: mapeo genérico (intenta cubrir otros tipos)
        return array_merge($base, [
            'fechaInicioAtencion' => $svc['fechaInicioAtencion'] ?? null,
            'numAutorizacion' => $svc['numAutorizacion'] ?? null,
            'codConsulta' => self::extractCode($svc['codConsulta'] ?? null),
            'codServicio' => self::extractCode($svc['codServicio'] ?? null),
            'tipoDocumentoIdentificacion' => $ripInvoiceUser->tipoDocumentoIdentificacion ?? ($svc['tipoDocumentoIdentificacion'] ?? null),
            'numDocumentoIdentificacion' => $ripInvoiceUser->numDocumentoIdentificacion ?? ($svc['numDocumentoIdentificacion'] ?? null),
            'vrServicio' => isset($svc['vrServicio']) ? (float) str_replace(',', '.', $svc['vrServicio']) : 0,
            'conceptoRecaudo' => self::extractCode($svc['conceptoRecaudo'] ?? null),
            'valorPagoModerador' => isset($svc['valorPagoModerador']) ? (float) str_replace(',', '.', $svc['valorPagoModerador']) : 0,
        ]);
    }

    /**
     * Lee servicios de un usuario (rip_invoice_user) y devuelve arrays mapeados
     * en la misma forma que espera tu frontend / getManualInfoServices.
     *
     * - $ripInvoiceUser: modelo o stdClass que tenga id (y preferible relaciones)
     * - $typeService: opcional, si se pasa filtra solo ese tipo (ej 'procedimientos' o 'Procedimientos')
     *
     * Resultado: array con keys por tipo ('consultas','procedimientos',...)
     */
    public static function getServicesForUser($ripInvoiceUser, ?string $typeService = null): array
    {
        $out = [];
        $requested = null;
        if (!empty($typeService)) {
            $requested = mb_strtolower(str_replace([' ', '_', '-'], '', $typeService));
        }

        // keys que soportamos (en el orden que prefieras)
        $serviceKeys = ['consultas', 'procedimientos', 'medicamentos', 'urgencias', 'otrosservicios', 'hospitalizacion', 'reciennacidos'];

        foreach ($serviceKeys as $sk) {
            if ($requested !== null && $requested !== $sk) {
                // si se solicitó un tipo específico y no coincide, omitimos
                $out[$sk] = [];
                continue;
            }

            $table = self::$tableConfig[$sk] ?? null;
            if (!$table) {
                $out[$sk] = [];
                continue;
            }

            // leer registros desde la tabla por rip_invoice_user_id
            $rows = DB::table($table)
                ->where('rip_invoice_user_id', $ripInvoiceUser->id)
                ->orderBy('consecutivo', 'asc')
                ->get();

            $mapped = [];
            foreach ($rows as $r) {
                $mapped[] = self::mapRecordToApi($sk, (array)$r);
            }

            $out[$sk] = $mapped;
        }

        // si se pidió solo un tipo, devuelve sólo ese key con contenido
        if ($requested !== null) {
            // normalizamos nombre clave (por ejemplo 'otrosservicios' -> 'otrosServicios' en frontend? Tú decides)
            // devolver con la misma clave en minúscula
            return [$requested => $out[$requested] ?? []];
        }

        return $out;
    }

    /**
     * Mapea un registro (array) de BD para salida API. Usa resources/models donde aplican.
     */
    private static function mapRecordToApi(string $serviceTypeKey, array $rec): array
    {
        $k = mb_strtolower($serviceTypeKey);
        $k = str_replace([' ', '_', '-'], '', $k);

        // CONSULTAS -> recurso con lookups
        if ($k === 'consultas') {
            $codConsulta = null;
            if (!empty($rec['codConsulta'])) {
                $codConsulta = CupsRips::where('codigo', $rec['codConsulta'])->first();
            }
            $modalidad = null;
            if (!empty($rec['modalidadGrupoServicioTecSal'])) {
                $modalidad = ModalidadAtencion::where('codigo', $rec['modalidadGrupoServicioTecSal'])->first();
            }
            $grupo = null;
            if (!empty($rec['grupoServicios'])) {
                $grupo = GrupoServicio::where('codigo', $rec['grupoServicios'])->first();
            }
            $servicio = null;
            if (!empty($rec['codServicio'])) {
                $servicio = Servicio::where('codigo', $rec['codServicio'])->first();
            }
            $finalidad = null;
            if (!empty($rec['finalidadTecnologiaSalud'])) {
                $finalidad = RipsFinalidadConsultaVersion2::where('codigo', $rec['finalidadTecnologiaSalud'])->first();
            }
            $causa = null;
            if (!empty($rec['causaMotivoAtencion'])) {
                $causa = RipsCausaExternaVersion2::where('codigo', $rec['causaMotivoAtencion'])->first();
            }
            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }
            $diagR1 = null;
            if (!empty($rec['codDiagnosticoRelacionado1'])) {
                $diagR1 = Cie10::where('codigo', $rec['codDiagnosticoRelacionado1'])->first();
            }
            $diagR2 = null;
            if (!empty($rec['codDiagnosticoRelacionado2'])) {
                $diagR2 = Cie10::where('codigo', $rec['codDiagnosticoRelacionado2'])->first();
            }
            $diagR3 = null;
            if (!empty($rec['codDiagnosticoRelacionado3'])) {
                $diagR3 = Cie10::where('codigo', $rec['codDiagnosticoRelacionado3'])->first();
            }
            $tipoDiag = null;
            if (!empty($rec['tipoDiagnosticoPrincipal'])) {
                $tipoDiag = RipsTipoDiagnosticoPrincipalVersion2::where('codigo', $rec['tipoDiagnosticoPrincipal'])->first();
            }
            $concepto = null;
            if (!empty($rec['conceptoRecaudo'])) {
                $concepto = ConceptoRecaudo::where('codigo', $rec['conceptoRecaudo'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'fechaInicioAtencion' => $rec['fechaInicioAtencion'] ?? null,
                'numAutorizacion' => $rec['numAutorizacion'] ?? null,
                'codConsulta' => !empty($rec['codConsulta']) && $codConsulta !== null ? new CupsRipsSelectInfiniteResource($codConsulta) : null,
                'modalidadGrupoServicioTecSal' => !empty($rec['modalidadGrupoServicioTecSal']) && $modalidad !== null ? new ModalidadAtencionSelectInfiniteResource($modalidad) : null,
                'grupoServicios' => !empty($rec['grupoServicios']) && $grupo !== null ? new GrupoServicioSelectInfiniteResource($grupo) : null,
                'codServicio' => !empty($rec['codServicio']) && $servicio !== null ? new ServicioSelectInfiniteResource($servicio) : null,
                'finalidadTecnologiaSalud' => !empty($rec['finalidadTecnologiaSalud']) && $finalidad !== null ? new RipsFinalidadConsultaVersion2SelectInfiniteResource($finalidad) : null,
                'causaMotivoAtencion' => !empty($rec['causaMotivoAtencion']) && $causa !== null ? new RipsCausaExternaVersion2SelectInfiniteResource($causa) : null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'codDiagnosticoRelacionado1' => !empty($rec['codDiagnosticoRelacionado1']) && $diagR1 !== null ? new Cie10SelectInfiniteResource($diagR1) : null,
                'codDiagnosticoRelacionado2' => !empty($rec['codDiagnosticoRelacionado2']) && $diagR2 !== null ? new Cie10SelectInfiniteResource($diagR2) : null,
                'codDiagnosticoRelacionado3' => !empty($rec['codDiagnosticoRelacionado3']) && $diagR3 !== null ? new Cie10SelectInfiniteResource($diagR3) : null,
                'tipoDiagnosticoPrincipal' => !empty($rec['tipoDiagnosticoPrincipal']) && $tipoDiag !== null ? new RipsTipoDiagnosticoPrincipalVersion2SelectInfiniteResource($tipoDiag) : null,
                'conceptoRecaudo' => !empty($rec['conceptoRecaudo']) && $concepto !== null ? new ConceptoRecaudoSelectResource($concepto) : null,
                'tipoDocumentoIdentificacion' => $rec['tipoDocumentoIdentificacion'] ?? null,
                'numDocumentoIdentificacion' => $rec['numDocumentoIdentificacion'] ?? null,
                'valorPagoModerador' => $rec['valorPagoModerador'] ?? null,
                'numFEVPagoModerador' => $rec['numFEVPagoModerador'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
                'vrServicio' => $rec['vrServicio'] ?? null,
            ];
        }

        // PROCEDIMIENTOS -> devolvemos campos relevantes (sin resources específicos por ahora)
        if ($k === 'procedimientos') {

            $codProcedimiento = null;
            if (!empty($rec['codProcedimiento'])) {
                $codProcedimiento = CupsRips::where('codigo', $rec['codProcedimiento'])->first();
            }
            $viaIngresoServicioSalud = null;
            if (!empty($rec['viaIngresoServicioSalud'])) {
                $viaIngresoServicioSalud = ViaIngresoUsuario::where('codigo', $rec['viaIngresoServicioSalud'])->first();
            }
            $modalidad = null;
            if (!empty($rec['modalidadGrupoServicioTecSal'])) {
                $modalidad = ModalidadAtencion::where('codigo', $rec['modalidadGrupoServicioTecSal'])->first();
            }
            $grupo = null;
            if (!empty($rec['grupoServicios'])) {
                $grupo = GrupoServicio::where('codigo', $rec['grupoServicios'])->first();
            }
            $servicio = null;
            if (!empty($rec['codServicio'])) {
                $servicio = Servicio::where('codigo', $rec['codServicio'])->first();
            }
            $finalidad = null;
            if (!empty($rec['finalidadTecnologiaSalud'])) {
                $finalidad = RipsFinalidadConsultaVersion2::where('codigo', $rec['finalidadTecnologiaSalud'])->first();
            }
            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }
            $diagR = null;
            if (!empty($rec['codDiagnosticoRelacionado'])) {
                $diagR = Cie10::where('codigo', $rec['codDiagnosticoRelacionado'])->first();
            }
            $complicacion = null;
            if (!empty($rec['codComplicacion'])) {
                $complicacion = Cie10::where('codigo', $rec['codComplicacion'])->first();
            }
            $concepto = null;
            if (!empty($rec['conceptoRecaudo'])) {
                $concepto = ConceptoRecaudo::where('codigo', $rec['conceptoRecaudo'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'fechaInicioAtencion' => $rec['fechaInicioAtencion'] ?? null,
                'idMIPRES' => $rec['idMIPRES'] ?? null,
                'numAutorizacion' => $rec['numAutorizacion'] ?? null,
                'codProcedimiento' => !empty($rec['codProcedimiento']) && $codProcedimiento !== null ? new CupsRipsSelectInfiniteResource($codProcedimiento) : null,
                'viaIngresoServicioSalud' => !empty($rec['viaIngresoServicioSalud']) && $viaIngresoServicioSalud !== null ? new ViaIngresoUsuarioSelectInfiniteResource($viaIngresoServicioSalud) : null,
                'modalidadGrupoServicioTecSal' => !empty($rec['modalidadGrupoServicioTecSal']) && $modalidad !== null ? new ModalidadAtencionSelectInfiniteResource($modalidad) : null,
                'grupoServicios' => !empty($rec['grupoServicios']) && $grupo !== null ? new GrupoServicioSelectInfiniteResource($grupo) : null,
                'codServicio' => !empty($rec['codServicio']) && $servicio !== null ? new ServicioSelectInfiniteResource($servicio) : null,
                'finalidadTecnologiaSalud' => !empty($rec['finalidadTecnologiaSalud']) && $finalidad !== null ? new RipsFinalidadConsultaVersion2SelectInfiniteResource($finalidad) : null,
                'tipoDocumentoIdentificacion' => $rec['tipoDocumentoIdentificacion'] ?? null,
                'numDocumentoIdentificacion' => $rec['numDocumentoIdentificacion'] ?? null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'codDiagnosticoRelacionado' => !empty($rec['codDiagnosticoRelacionado']) && $diagR !== null ? new Cie10SelectInfiniteResource($diagR) : null,
                'codComplicacion' => !empty($rec['codComplicacion']) && $complicacion !== null ? new Cie10SelectInfiniteResource($complicacion) : null,
                'vrServicio' => $rec['vrServicio'] ?? null,
                'conceptoRecaudo' => !empty($rec['conceptoRecaudo']) && $concepto !== null ? new ConceptoRecaudoSelectResource($concepto) : null,
                'valorPagoModerador' => $rec['valorPagoModerador'] ?? null,
                'numFEVPagoModerador' => $rec['numFEVPagoModerador'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }

        // URGENCIAS -> devolvemos campos relevantes usando resources similares a los otros tipos
        if ($k === 'urgencias') {
            $causa = null;
            if (!empty($rec['causaMotivoAtencion'])) {
                $causa = RipsCausaExternaVersion2::where('codigo', $rec['causaMotivoAtencion'])->first();
            }

            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }

            $diagPE = null;
            if (!empty($rec['codDiagnosticoPrincipalE'])) {
                $diagPE = Cie10::where('codigo', $rec['codDiagnosticoPrincipalE'])->first();
            }

            $diagRE1 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE1'])) {
                $diagRE1 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE1'])->first();
            }

            $diagRE2 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE2'])) {
                $diagRE2 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE2'])->first();
            }

            $diagRE3 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE3'])) {
                $diagRE3 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE3'])->first();
            }

            $diagCausaMuerte = null;
            if (!empty($rec['codDiagnosticoCausaMuerte'])) {
                $diagCausaMuerte = Cie10::where('codigo', $rec['codDiagnosticoCausaMuerte'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'fechaInicioAtencion' => $rec['fechaInicioAtencion'] ?? null,
                'causaMotivoAtencion' => !empty($rec['causaMotivoAtencion']) && $causa !== null ? new RipsCausaExternaVersion2SelectInfiniteResource($causa) : null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'codDiagnosticoPrincipalE' => !empty($rec['codDiagnosticoPrincipalE']) && $diagPE !== null ? new Cie10SelectInfiniteResource($diagPE) : null,
                'codDiagnosticoRelacionadoE1' => !empty($rec['codDiagnosticoRelacionadoE1']) && $diagRE1 !== null ? new Cie10SelectInfiniteResource($diagRE1) : null,
                'codDiagnosticoRelacionadoE2' => !empty($rec['codDiagnosticoRelacionadoE2']) && $diagRE2 !== null ? new Cie10SelectInfiniteResource($diagRE2) : null,
                'codDiagnosticoRelacionadoE3' => !empty($rec['codDiagnosticoRelacionadoE3']) && $diagRE3 !== null ? new Cie10SelectInfiniteResource($diagRE3) : null,
                'condicionDestinoUsuarioEgreso' => $rec['condicionDestinoUsuarioEgreso'] ?? null,
                'codDiagnosticoCausaMuerte' => !empty($rec['codDiagnosticoCausaMuerte']) && $diagCausaMuerte !== null ? new Cie10SelectInfiniteResource($diagCausaMuerte) : null,
                'fechaEgreso' => $rec['fechaEgreso'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }

        // HOSPITALIZACION -> recursos / lookups
        if ($k === 'hospitalizacion') {
            $viaIngreso = null;
            if (!empty($rec['viaIngresoServicioSalud'])) {
                $viaIngreso = ViaIngresoUsuario::where('codigo', $rec['viaIngresoServicioSalud'])->first();
            }
            $causa = null;
            if (!empty($rec['causaMotivoAtencion'])) {
                $causa = RipsCausaExternaVersion2::where('codigo', $rec['causaMotivoAtencion'])->first();
            }
            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }
            $diagPE = null;
            if (!empty($rec['codDiagnosticoPrincipalE'])) {
                $diagPE = Cie10::where('codigo', $rec['codDiagnosticoPrincipalE'])->first();
            }
            $diagR1 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE1'])) {
                $diagR1 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE1'])->first();
            }
            $diagR2 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE2'])) {
                $diagR2 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE2'])->first();
            }
            $diagR3 = null;
            if (!empty($rec['codDiagnosticoRelacionadoE3'])) {
                $diagR3 = Cie10::where('codigo', $rec['codDiagnosticoRelacionadoE3'])->first();
            }
            $complicacion = null;
            if (!empty($rec['codComplicacion'])) {
                $complicacion = Cie10::where('codigo', $rec['codComplicacion'])->first();
            }
            $condicion = null;
            if (!empty($rec['condicionDestinoUsuarioEgreso'])) {
                $condicion = CondicionyDestinoUsuarioEgreso::where('codigo', $rec['condicionDestinoUsuarioEgreso'])->first();
            }
            $diagMuerte = null;
            if (!empty($rec['codDiagnosticoCausaMuerte'])) {
                $diagMuerte = Cie10::where('codigo', $rec['codDiagnosticoCausaMuerte'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'viaIngresoServicioSalud' => !empty($rec['viaIngresoServicioSalud']) && $viaIngreso !== null ? new ViaIngresoUsuarioSelectInfiniteResource($viaIngreso) : null,
                'fechaInicioAtencion' => $rec['fechaInicioAtencion'] ?? null,
                'numAutorizacion' => $rec['numAutorizacion'] ?? null,
                'causaMotivoAtencion' => !empty($rec['causaMotivoAtencion']) && $causa !== null ? new RipsCausaExternaVersion2SelectInfiniteResource($causa) : null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'codDiagnosticoPrincipalE' => !empty($rec['codDiagnosticoPrincipalE']) && $diagPE !== null ? new Cie10SelectInfiniteResource($diagPE) : null,
                'codDiagnosticoRelacionadoE1' => !empty($rec['codDiagnosticoRelacionadoE1']) && $diagR1 !== null ? new Cie10SelectInfiniteResource($diagR1) : null,
                'codDiagnosticoRelacionadoE2' => !empty($rec['codDiagnosticoRelacionadoE2']) && $diagR2 !== null ? new Cie10SelectInfiniteResource($diagR2) : null,
                'codDiagnosticoRelacionadoE3' => !empty($rec['codDiagnosticoRelacionadoE3']) && $diagR3 !== null ? new Cie10SelectInfiniteResource($diagR3) : null,
                'codComplicacion' => !empty($rec['codComplicacion']) && $complicacion !== null ? new Cie10SelectInfiniteResource($complicacion) : null,
                'condicionDestinoUsuarioEgreso' => !empty($rec['condicionDestinoUsuarioEgreso']) && $condicion !== null ? new CondicionyDestinoUsuarioEgresoSelectInfiniteResource($condicion) : null,
                'codDiagnosticoCausaMuerte' => !empty($rec['codDiagnosticoCausaMuerte']) && $diagMuerte !== null ? new Cie10SelectInfiniteResource($diagMuerte) : null,
                'fechaEgreso' => $rec['fechaEgreso'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }

        // RECIEN NACIDOS -> recursos / lookups
        if ($k === 'reciennacidos') {
            $sexo = null;
            if (!empty($rec['codSexoBiologico'])) {
                $sexo = Sexo::where('codigo', $rec['codSexoBiologico'])->first();
            }
            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }
            $condicion = null;
            if (!empty($rec['condicionDestinoUsuarioEgreso'])) {
                $condicion = CondicionyDestinoUsuarioEgreso::where('codigo', $rec['condicionDestinoUsuarioEgreso'])->first();
            }
            $diagM = null;
            if (!empty($rec['codDiagnosticoCausaMuerte'])) {
                $diagM = Cie10::where('codigo', $rec['codDiagnosticoCausaMuerte'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'tipoDocumentoIdentificacion' => $rec['tipoDocumentoIdentificacion'] ?? null,
                'numDocumentoIdentificacion' => $rec['numDocumentoIdentificacion'] ?? null,
                'fechaNacimiento' => $rec['fechaNacimiento'] ?? null,
                'edadGestacional' => $rec['edadGestacional'] ?? null,
                'numConsultasCPrenatal' => $rec['numConsultasCPrenatal'] ?? null,
                'codSexoBiologico' => !empty($rec['codSexoBiologico']) && $sexo !== null ? new SexoSelectResource($sexo) : null,
                'peso' => $rec['peso'] ?? null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'condicionDestinoUsuarioEgreso' => !empty($rec['condicionDestinoUsuarioEgreso']) && $condicion !== null ? new CondicionyDestinoUsuarioEgresoSelectInfiniteResource($condicion) : null,
                'codDiagnosticoCausaMuerte' => !empty($rec['codDiagnosticoCausaMuerte']) && $diagM !== null ? new Cie10SelectInfiniteResource($diagM) : null,
                'fechaEgreso' => $rec['fechaEgreso'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }

        // MEDICAMENTOS -> recursos / lookups (intento usar modelos/resources si existen)
        if ($k === 'medicamentos') {
            // intento buscar CIE10 para diagnósticos
            $diagP = null;
            if (!empty($rec['codDiagnosticoPrincipal'])) {
                $diagP = Cie10::where('codigo', $rec['codDiagnosticoPrincipal'])->first();
            }
            $diagR = null;
            if (!empty($rec['codDiagnosticoRelacionado'])) {
                $diagR = Cie10::where('codigo', $rec['codDiagnosticoRelacionado'])->first();
            }

            // intento buscar ConceptoRecaudo si existe
            $concepto = null;
            if (!empty($rec['conceptoRecaudo'])) {
                $concepto = ConceptoRecaudo::where('codigo', $rec['conceptoRecaudo'])->first();
            }

            // intentos seguros para otros selects (si tus modelos existen).
            $tipoMed = null;
            if (!empty($rec['tipoMedicamento'])) {
                $tipoMed = TipoMedicamentoPosVersion2::where('codigo', $rec['tipoMedicamento'])->first();
            }

            $umm = null;
            if (!empty($rec['unidadMedida'])) {
                $umm = Umm::where('codigo', $rec['unidadMedida'])->first();
            }
            $ffm = null;
            if (!empty($rec['formaFarmaceutica'])) {
                $ffm = Ffm::where('codigo', $rec['formaFarmaceutica'])->first();
            }
            $upr = null;
            if (!empty($rec['unidadMinDispensa'])) {
                $upr = Upr::where('codigo', $rec['unidadMinDispensa'])->first();
            }
            $codTecnologia = null;
            if (!empty($rec['codTecnologiaSaludable_type']) && !empty($rec['codTecnologiaSaludable_id'])) {
                if ($rec['codTecnologiaSaludable_type'] === 'CatalogoCum') {
                    $codTecnologia = CatalogoCum::where('id', $rec['codTecnologiaSaludable_id'])->first();
                    $codTecnologia = new CatalogoCumSelectResource($codTecnologia);
                }
                if ($rec['codTecnologiaSaludable_type'] === 'Ium') {
                    $codTecnologia = Ium::where('id', $rec['codTecnologiaSaludable_id'])->first();
                    $codTecnologia = new IumSelectResource($codTecnologia);
                }
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'numAutorizacion' => $rec['numAutorizacion'] ?? null,
                'idMIPRES' => $rec['idMIPRES'] ?? null,
                'fechaDispensAdmon' => $rec['fechaDispensAdmon'] ?? null,
                'codDiagnosticoPrincipal' => !empty($rec['codDiagnosticoPrincipal']) && $diagP !== null ? new Cie10SelectInfiniteResource($diagP) : null,
                'codDiagnosticoRelacionado' => !empty($rec['codDiagnosticoRelacionado']) && $diagR !== null ? new Cie10SelectInfiniteResource($diagR) : null,
                'tipoMedicamento' => !empty($rec['tipoMedicamento']) && $tipoMed !== null ? new TipoMedicamentoPosVersion2SelectInfiniteResource($tipoMed) : null,

                'codTecnologiaSaludable_type' => $rec['codTecnologiaSaludable_type'] ?? null,
                'codTecnologiaSaludable_id' => $codTecnologia ?? null,
                'codTecnologiaSalud' => $rec['codTecnologiaSalud'] ?? null,

                'nomTecnologiaSalud' => $rec['nomTecnologiaSalud'] ?? null,
                'concentracionMedicamento' => $rec['concentracionMedicamento'] ?? null,
                'unidadMedida' => !empty($rec['unidadMedida']) && $umm !== null ? new UmmSelectInfiniteResource($umm) : null,
                'formaFarmaceutica' => !empty($rec['formaFarmaceutica']) && $ffm !== null ? new FfmSelectResource($ffm) : null,
                'unidadMinDispensa' => !empty($rec['unidadMinDispensa']) && $upr !== null ? new UprSelectResource($upr) : null,
                'cantidadMedicamento' => $rec['cantidadMedicamento'] ?? null,
                'diasTratamiento' => $rec['diasTratamiento'] ?? null,
                'tipoDocumentoIdentificacion' => $rec['tipoDocumentoIdentificacion'] ?? null,
                'numDocumentoIdentificacion' => $rec['numDocumentoIdentificacion'] ?? null,
                'vrUnitMedicamento' => $rec['vrUnitMedicamento'] ?? null,
                'vrServicio' => $rec['vrServicio'] ?? null,
                'conceptoRecaudo' => !empty($rec['conceptoRecaudo']) && $concepto !== null ? new ConceptoRecaudoSelectResource($concepto) : null,
                'valorPagoModerador' => $rec['valorPagoModerador'] ?? null,
                'numFEVPagoModerador' => $rec['numFEVPagoModerador'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }

        // OTROS SERVICIOS -> recursos / lookups
        if ($k === 'otrosservicios') {
            // intento buscar cups/codes para codTecnologiaSalud (CupsRips)
            $tipoOs = null;
            if (!empty($rec['tipoOS'])) {
                $tipoOs = TipoOtrosServicios::where('codigo', $rec['tipoOS'])->first();
            }

            $codTecnologiaSalud = null;
            if (!empty($rec['codTecnologiaSalud'])) {
                $codTecnologiaSalud = CupsRips::where('codigo', $rec['codTecnologiaSalud'])->first();
            }

            $concepto = null;
            if (!empty($rec['conceptoRecaudo'])) {
                $concepto = ConceptoRecaudo::where('codigo', $rec['conceptoRecaudo'])->first();
            }

            return [
                'id' => $rec['id'] ?? null,
                'codPrestador' => $rec['codPrestador'] ?? null,
                'numAutorizacion' => $rec['numAutorizacion'] ?? null,
                'idMIPRES' => $rec['idMIPRES'] ?? null,
                'fechaSuministroTecnologia' => $rec['fechaSuministroTecnologia'] ?? null,
                'tipoOS' => !empty($rec['tipoOS']) && $tipoOs !== null ? new TipoOtrosServiciosSelectResource($tipoOs) : null,
                'codTecnologiaSalud' => !empty($rec['codTecnologiaSalud']) && $codTecnologiaSalud !== null ? new CupsRipsSelectInfiniteResource($codTecnologiaSalud) : null,
                'nomTecnologiaSalud' => $rec['nomTecnologiaSalud'] ?? null,
                'cantidadOS' => $rec['cantidadOS'] ?? null,
                'tipoDocumentoIdentificacion' => $rec['tipoDocumentoIdentificacion'] ?? null,
                'numDocumentoIdentificacion' => $rec['numDocumentoIdentificacion'] ?? null,
                'vrUnitOS' => $rec['vrUnitOS'] ?? null,
                'vrServicio' => $rec['vrServicio'] ?? null,
                'conceptoRecaudo' => !empty($rec['conceptoRecaudo']) && $concepto !== null ? new ConceptoRecaudoSelectResource($concepto) : null,
                'valorPagoModerador' => $rec['valorPagoModerador'] ?? null,
                'numFEVPagoModerador' => $rec['numFEVPagoModerador'] ?? null,
                'consecutivo' => $rec['consecutivo'] ?? null,
            ];
        }



        // para otros tipos devolvemos el array tal cual (o selecciona campos que te interesen)
        // esto evita ifs enormes; extiende si necesitas resources concretos.
        $basic = $rec;
        // quitar campos internos si quieres
        unset($basic['created_at'], $basic['updated_at']);
        return $basic;
    }

    private static function normalizeValue($val)
    {
        if (is_null($val)) return null;
        if (is_string($val)) {
            $v = trim($val);
            // si es número con coma o punto convertimos a float si corresponde
            if (is_numeric(str_replace(',', '.', $v))) {
                // evitar pérdida si es identificador que contenga ceros a la izquierda?
                // si quieres mantener como string, quítale esta conversión.
                return (float) str_replace(',', '.', $v);
            }
            return $v === '' ? null : $v;
        }
        return $val;
    }

    /**
     * Devuelve el array listo para guardarse en el JSON (ordenado y sin campos internos).
     *
     * @param array $dbPayload  Array con los campos (puede ser el payload que insertaste/actualizaste)
     * @param string $serviceType  'consultas'|'procedimientos'|etc.
     * @return array  Array listo para insertar en el JSON (no contiene rip_invoice_user_id ni created_at/updated_at)
     */
    public static function formatForJson(array $dbPayload, string $serviceType): array
    {
        $k = mb_strtolower(str_replace([' ', '_', '-'], '', $serviceType));

        // campos internos a eliminar si vienen
        $blacklist = ['rip_invoice_user_id', 'created_at', 'updated_at', 'deleted_at', 'id'];

        // helper para obtener valor normalizado si existe en payload
        $g = function ($key) use ($dbPayload) {
            return array_key_exists($key, $dbPayload) ? $dbPayload[$key] : null;
        };

        // CONSULTAS (orden EXACTO requerido)
        if ($k === 'consultas') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'fechaInicioAtencion' => $g('fechaInicioAtencion'),
                'numAutorizacion' => $g('numAutorizacion'),
                'codConsulta' => $g('codConsulta'),
                'modalidadGrupoServicioTecSal' => $g('modalidadGrupoServicioTecSal'),
                'grupoServicios' => $g('grupoServicios'),
                'codServicio' => $g('codServicio'),
                'finalidadTecnologiaSalud' => $g('finalidadTecnologiaSalud'),
                'causaMotivoAtencion' => $g('causaMotivoAtencion'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'codDiagnosticoRelacionado1' => $g('codDiagnosticoRelacionado1'),
                'codDiagnosticoRelacionado2' => $g('codDiagnosticoRelacionado2'),
                'codDiagnosticoRelacionado3' => $g('codDiagnosticoRelacionado3'),
                'tipoDiagnosticoPrincipal' => $g('tipoDiagnosticoPrincipal'),
                'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
                'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
                'vrServicio' => $g('vrServicio'),
                'conceptoRecaudo' => $g('conceptoRecaudo'),
                'valorPagoModerador' => $g('valorPagoModerador'),
                'numFEVPagoModerador' => $g('numFEVPagoModerador'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // PROCEDIMIENTOS (orden EXACTO requerido)
        if ($k === 'procedimientos') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'fechaInicioAtencion' => $g('fechaInicioAtencion'),
                'idMIPRES' => $g('idMIPRES'),
                'numAutorizacion' => $g('numAutorizacion'),
                'codProcedimiento' => $g('codProcedimiento'),
                'viaIngresoServicioSalud' => $g('viaIngresoServicioSalud'),
                'modalidadGrupoServicioTecSal' => $g('modalidadGrupoServicioTecSal'),
                'grupoServicios' => $g('grupoServicios'),
                'codServicio' => $g('codServicio'),
                'finalidadTecnologiaSalud' => $g('finalidadTecnologiaSalud'),
                'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
                'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'codDiagnosticoRelacionado' => $g('codDiagnosticoRelacionado'),
                'codComplicacion' => $g('codComplicacion'),
                'vrServicio' => $g('vrServicio'),
                'conceptoRecaudo' => $g('conceptoRecaudo'),
                'valorPagoModerador' => $g('valorPagoModerador'),
                'numFEVPagoModerador' => $g('numFEVPagoModerador'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // URGENCIAS (orden EXACTO requerido)
        if ($k === 'urgencias') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'fechaInicioAtencion' => $g('fechaInicioAtencion'),
                'causaMotivoAtencion' => $g('causaMotivoAtencion'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'codDiagnosticoPrincipalE' => $g('codDiagnosticoPrincipalE'),
                'codDiagnosticoRelacionadoE1' => $g('codDiagnosticoRelacionadoE1'),
                'codDiagnosticoRelacionadoE2' => $g('codDiagnosticoRelacionadoE2'),
                'codDiagnosticoRelacionadoE3' => $g('codDiagnosticoRelacionadoE3'),
                'condicionDestinoUsuarioEgreso' => $g('condicionDestinoUsuarioEgreso'),
                'codDiagnosticoCausaMuerte' => $g('codDiagnosticoCausaMuerte'),
                'fechaEgreso' => $g('fechaEgreso'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // HOSPITALIZACION (orden EXACTO requerido según formatValueAH)
        if ($k === 'hospitalizacion') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'viaIngresoServicioSalud' => $g('viaIngresoServicioSalud'),
                'fechaInicioAtencion' => $g('fechaInicioAtencion'),
                'numAutorizacion' => $g('numAutorizacion'),
                'causaMotivoAtencion' => $g('causaMotivoAtencion'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'codDiagnosticoPrincipalE' => $g('codDiagnosticoPrincipalE'),
                'codDiagnosticoRelacionadoE1' => $g('codDiagnosticoRelacionadoE1'),
                'codDiagnosticoRelacionadoE2' => $g('codDiagnosticoRelacionadoE2'),
                'codDiagnosticoRelacionadoE3' => $g('codDiagnosticoRelacionadoE3'),
                'codComplicacion' => $g('codComplicacion'),
                'condicionDestinoUsuarioEgreso' => $g('condicionDestinoUsuarioEgreso'),
                'codDiagnosticoCausaMuerte' => $g('codDiagnosticoCausaMuerte'),
                'fechaEgreso' => $g('fechaEgreso'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // RECIEN NACIDOS (orden según tu formatValue)
        if ($k === 'reciennacidos') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
                'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
                'fechaNacimiento' => $g('fechaNacimiento'),
                'edadGestacional' => $g('edadGestacional'),
                'numConsultasCPrenatal' => $g('numConsultasCPrenatal'),
                'codSexoBiologico' => $g('codSexoBiologico'),
                'peso' => $g('peso'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'condicionDestinoUsuarioEgreso' => $g('condicionDestinoUsuarioEgreso'),
                'codDiagnosticoCausaMuerte' => $g('codDiagnosticoCausaMuerte'),
                'fechaEgreso' => $g('fechaEgreso'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // MEDICAMENTOS (orden EXACTO requerido)
        if ($k === 'medicamentos') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'numAutorizacion' => $g('numAutorizacion'),
                'idMIPRES' => $g('idMIPRES'),
                'fechaDispensAdmon' => $g('fechaDispensAdmon'),
                'codDiagnosticoPrincipal' => $g('codDiagnosticoPrincipal'),
                'codDiagnosticoRelacionado' => $g('codDiagnosticoRelacionado'),
                'tipoMedicamento' => $g('tipoMedicamento'),
                'codTecnologiaSalud' => $g('codTecnologiaSalud'),
                'nomTecnologiaSalud' => $g('nomTecnologiaSalud'),
                'concentracionMedicamento' => $g('concentracionMedicamento'),
                'unidadMedida' => $g('unidadMedida'),
                'formaFarmaceutica' => $g('formaFarmaceutica'),
                'unidadMinDispensa' => $g('unidadMinDispensa'),
                'cantidadMedicamento' => $g('cantidadMedicamento'),
                'diasTratamiento' => $g('diasTratamiento'),
                'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
                'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
                'vrUnitMedicamento' => $g('vrUnitMedicamento'),
                'vrServicio' => $g('vrServicio'),
                'conceptoRecaudo' => $g('conceptoRecaudo'),
                'valorPagoModerador' => $g('valorPagoModerador'),
                'numFEVPagoModerador' => $g('numFEVPagoModerador'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }

        // OTROS SERVICIOS (orden EXACTO requerido)
        if ($k === 'otrosservicios') {
            $out = [
                'codPrestador' => $g('codPrestador'),
                'numAutorizacion' => $g('numAutorizacion'),
                'idMIPRES' => $g('idMIPRES'),
                'fechaSuministroTecnologia' => $g('fechaSuministroTecnologia'),
                'tipoOS' => $g('tipoOS'),
                'codTecnologiaSalud' => $g('codTecnologiaSalud'),
                'nomTecnologiaSalud' => $g('nomTecnologiaSalud'),
                'cantidadOS' => $g('cantidadOS'),
                'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
                'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
                'vrUnitOS' => $g('vrUnitOS'),
                'vrServicio' => $g('vrServicio'),
                'conceptoRecaudo' => $g('conceptoRecaudo'),
                'valorPagoModerador' => $g('valorPagoModerador'),
                'numFEVPagoModerador' => $g('numFEVPagoModerador'),
                'consecutivo' => array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null,
            ];

            return $out;
        }



        // FALLBACK ordenado por campos comunes cuando no esté definido explícitamente
        $out = [
            'codPrestador' => $g('codPrestador'),
            'fechaInicioAtencion' => $g('fechaInicioAtencion'),
            'numAutorizacion' => $g('numAutorizacion'),
            'codConsulta' => $g('codConsulta'),
            'codServicio' => $g('codServicio'),
            'tipoDocumentoIdentificacion' => $g('tipoDocumentoIdentificacion'),
            'numDocumentoIdentificacion' => $g('numDocumentoIdentificacion'),
            'vrServicio' => $g('vrServicio'),
            'conceptoRecaudo' => $g('conceptoRecaudo'),
            'valorPagoModerador' => $g('valorPagoModerador'),
            'numFEVPagoModerador' => $g('numFEVPagoModerador'),
        ];

        // añadir cualquier otro campo del payload que no sean internos y que no repitamos
        foreach ($dbPayload as $rk => $rv) {
            if (in_array($rk, $blacklist)) continue;
            if (array_key_exists($rk, $out)) continue;
            if ($rk === 'consecutivo') continue; // lo agregamos al final
            // normalizamos y añadimos
            $out[$rk] = self::normalizeValue($rv);
        }

        // siempre dejar consecutivo al final
        $out['consecutivo'] = array_key_exists('consecutivo', $dbPayload) ? (int)$dbPayload['consecutivo'] : null;

        return $out;
    }

    public static function reorderServices(array $servicios, ?array $order = null): array
    {
        // orden por defecto si no se provee
        $defaultOrder = [
            'consultas',
            'procedimientos',
            'urgencias',
            'hospitalizacion',
            'reciennacidos',
            'medicamentos',
            'otrosservicios'
        ];
        $order = $order ?? $defaultOrder;

        $out = [];

        foreach ($order as $key) {
            if (!isset($servicios[$key])) continue;
            // aseguramos que sea array y reindexamos
            if (!is_array($servicios[$key]) || count($servicios[$key]) === 0) {
                continue; // omitimos keys vacías
            }
            // reindex inner array (1..N)
            $out[$key] = array_values($servicios[$key]);
        }

        // Además incluir cualquier key no listada en $order (al final), pero sólo si tiene elementos
        foreach ($servicios as $k => $v) {
            if (in_array($k, $order)) continue;
            if (!is_array($v) || count($v) === 0) continue;
            $out[$k] = array_values($v);
        }

        return $out;
    }
}
