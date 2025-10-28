<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\RipInvoiceUser;
use App\Models\RipServiceHospitalization;
use App\Models\RipServiceMedicine;
use App\Models\RipServiceNewlyBorn;
use App\Models\RipServiceOtherService;
use App\Models\RipServiceProcedure;
use App\Models\RipServiceQuery;
use App\Models\RipServiceUrgency;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RipInvoiceServiceRepository extends BaseRepository
{
    public function __construct(RipInvoiceUser $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginateQueries($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateQueries", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceQuery::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('fechaInicioAtencion', 'like', "%$value%");
                            $subQuery->orWhere('numAutorizacion', 'like', "%$value%");

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('valorPagoModerador', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrServicio', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhereHas('consulta', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('modalidadGrupoServicioTecSalRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('grupoServiciosRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('servicio', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('finalidadTecnologiaSaludRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('causaMotivoAtencionRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionado1', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionado2', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionado3', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('tipoDiagnosticoPrincipalRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('conceptoRecaudoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'fechaInicioAtencion',
                    'numAutorizacion',
                    'codConsulta',
                    'modalidadGrupoServicioTecSal',
                    'grupoServicios',
                    'codServicio',
                    'finalidadTecnologiaSalud',
                    'causaMotivoAtencion',
                    'codDiagnosticoPrincipal',
                    'codDiagnosticoRelacionado1',
                    'codDiagnosticoRelacionado2',
                    'codDiagnosticoRelacionado3',
                    'tipoDiagnosticoPrincipal',
                    'conceptoRecaudo',
                    'valorPagoModerador',
                    'vrServicio',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateProcedures($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateProcedures", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceProcedure::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('fechaInicioAtencion', 'like', "%$value%");
                            $subQuery->orWhere('idMIPRES', 'like', "%$value%");
                            $subQuery->orWhere('numAutorizacion', 'like', "%$value%");

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('valorPagoModerador', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrServicio', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhereHas('procedimiento', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('viaIngresoServicioSaludRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('modalidadGrupoServicioTecSalRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('grupoServiciosRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('servicio', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('finalidadTecnologiaSaludRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionado', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('complicacion', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('conceptoRecaudoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'fechaInicioAtencion',
                    'idMIPRES',
                    'numAutorizacion',
                    'codProcedimiento',
                    'viaIngresoServicioSalud',
                    'modalidadGrupoServicioTecSal',
                    'grupoServicios',
                    'codServicio',
                    'finalidadTecnologiaSalud',
                    'codDiagnosticoPrincipal',
                    'codDiagnosticoRelacionado',
                    'codComplicacion',
                    'valorPagoModerador',
                    'vrServicio',
                    'conceptoRecaudo',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateUrgencies($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateUrgencies", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceUrgency::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('fechaInicioAtencion', 'like', "%$value%");
                            $subQuery->orWhere('condicionDestinoUsuarioEgreso', 'like', "%$value%");
                            $subQuery->orWhere('fechaEgreso', 'like', "%$value%");

                            $subQuery->orWhereHas('causaMotivoAtencionRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipalE', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE1', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE2', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE3', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoCausaMuerte', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'fechaInicioAtencion',
                    'causaMotivoAtencion',
                    'codDiagnosticoPrincipal',
                    'codDiagnosticoPrincipalE',
                    'codDiagnosticoRelacionadoE1',
                    'codDiagnosticoRelacionadoE2',
                    'codDiagnosticoRelacionadoE3',
                    'condicionDestinoUsuarioEgreso',
                    'codDiagnosticoCausaMuerte',
                    'fechaEgreso',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateHospitalizations($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateHospitalizations", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceHospitalization::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('fechaInicioAtencion', 'like', "%$value%");
                            $subQuery->orWhere('fechaEgreso', 'like', "%$value%");
                            $subQuery->orWhere('numAutorizacion', 'like', "%$value%");

                            $subQuery->orWhereHas('viaIngresoServicioSaludRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('causaMotivoAtencionRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipalE', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE1', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE2', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionadoE3', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('complicacion', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('condicionDestinoUsuarioEgresoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoCausaMuerte', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'fechaInicioAtencion',
                    'fechaEgreso',
                    'numAutorizacion',
                    'viaIngresoServicioSalud',
                    'causaMotivoAtencion',
                    'codDiagnosticoPrincipal',
                    'codDiagnosticoPrincipalE',
                    'codDiagnosticoRelacionadoE1',
                    'codDiagnosticoRelacionadoE2',
                    'codDiagnosticoRelacionadoE3',
                    'codComplicacion',
                    'condicionDestinoUsuarioEgreso',
                    'codDiagnosticoCausaMuerte',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateNewlyBorns($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateNewlyBorns", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceNewlyBorn::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('fechaNacimiento', 'like', "%$value%");
                            $subQuery->orWhere('edadGestacional', 'like', "%$value%");
                            $subQuery->orWhere('numConsultasCPrenatal', 'like', "%$value%");
                            $subQuery->orWhere('peso', 'like', "%$value%");
                            $subQuery->orWhere('condicionDestino', 'like', "%$value%");
                            $subQuery->orWhere('fechaEgreso', 'like', "%$value%");

                            $subQuery->orWhereHas('codSexoBiologico', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('codDiagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('condicionDestinoUsuarioEgreso', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('codDiagnosticoCausaMuerte', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });
                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'fechaNacimiento',
                    'edadGestacional',
                    'numConsultasCPrenatal',
                    'codSexoBiologico',
                    'peso',
                    'codDiagnosticoPrincipal',
                    'condicionDestino',
                    'condicionDestinoUsuarioEgreso',
                    'codDiagnosticoCausaMuerte',
                    'fechaEgreso',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateMedicines($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateMedicines", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceMedicine::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('numAutorizacion', 'like', "%$value%");
                            $subQuery->orWhere('idMIPRES', 'like', "%$value%");
                            $subQuery->orWhere('fechaDispensAdmon', 'like', "%$value%");
                            $subQuery->orWhere('diasTratamiento', 'like', "%$value%");
                            $subQuery->orWhere('cantidadMedicamento', 'like', "%$value%");
                            $subQuery->orWhere('concentracionMedicamento', 'like', "%$value%");

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrUnitMedicamento', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('valorPagoModerador', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrServicio', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhereHas('diagnosticoPrincipal', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('diagnosticoRelacionado', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('conceptoRecaudoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('tipoMedicamentoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('nomTecnologiaSaludRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('unidadMedidaRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('formaFarmaceuticaRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('unidadMinDispensaRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });
                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'numAutorizacion',
                    'idMIPRES',
                    'fechaDispensAdmon',
                    'diasTratamiento',
                    'cantidadMedicamento',
                    'concentracionMedicamento',
                    'codTecnologiaSalud',
                    'codDiagnosticoPrincipal',
                    'codDiagnosticoRelacionado',
                    'tipoMedicamento',
                    'nomTecnologiaSalud',
                    'unidadMedida',
                    'formaFarmaceutica',
                    'unidadMinDispensa',
                    'conceptoRecaudo',
                    'vrUnitMedicamento',
                    'valorPagoModerador',
                    'vrServicio',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function paginateOtherServices($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginateOtherServices", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for(RipServiceOtherService::query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            // $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('numAutorizacion', 'like', "%$value%");
                            $subQuery->orWhere('idMIPRES', 'like', "%$value%");
                            $subQuery->orWhere('fechaSuministroTecnologia', 'like', "%$value%");
                            $subQuery->orWhere('nomTecnologiaSalud', 'like', "%$value%");
                            $subQuery->orWhere('cantidadOS', 'like', "%$value%");

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrUnitOS', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('valorPagoModerador', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('vrServicio', 'like', "%$normalizedValue%");
                            });

                            $subQuery->orWhereHas('tipoOSRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('tecnologiaSalud', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('conceptoRecaudoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });
                        });
                    }),
                ])
                ->allowedSorts([
                    // 'consecutivo',
                    'numAutorizacion',
                    'idMIPRES',
                    'fechaSuministroTecnologia',
                    'tipoOS',
                    'codTecnologiaSalud',
                    'nomTecnologiaSalud',
                    'cantidadOS',
                    'vrUnitOS',
                    'valorPagoModerador',
                    'vrServicio',
                    'conceptoRecaudo',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_user_id'])) {
                        $query->where('rip_invoice_user_id', $request['rip_invoice_user_id']);
                    }
                })
                ->orderBy('consecutivo');

            if (empty($request['typeData'])) {
                $query = $query->paginate(request()->perPage ?? Constants::ITEMS_PER_PAGE);
            } else {
                $query = $query->get();
            }

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function store(array $request, $id = null)
    {
        $request = $this->clearNull($request);

        // Determinar el ID a utilizar para buscar o crear el modelo
        $idToUse = ($id === null || $id === 'null') && ! empty($request['id']) && $request['id'] !== 'null' ? $request['id'] : $id;

        if (! empty($idToUse)) {
            $data = $this->model->find($idToUse);
        } else {
            $data = $this->model::newModelInstance();
        }

        foreach ($request as $key => $value) {
            $data[$key] = is_array($request[$key]) ? $request[$key]['value'] : $request[$key];
        }

        $data->save();

        return $data;
    }

    public function selectList($request = [], $with = [], $select = [], $fieldValue = 'id', $fieldTitle = 'name', $limit = null)
    {
        $query = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }
        });

        $query->where(function ($query) use ($request) {
            if (! empty($request['string'])) {
                $value = strval($request['string']);
                $query->orWhere('name', 'like', '%' . $value . '%');
            }
        });
        // Aplica el límite si está definido
        if ($limit !== null) {
            $query->limit($limit);
        }

        $data = $query->get()->map(function ($value) use ($with, $select, $fieldValue, $fieldTitle) {
            $data = [
                'value' => $value->$fieldValue,
                'title' => $value->$fieldTitle,
            ];

            if (count($select) > 0) {
                foreach ($select as $s) {
                    $data[$s] = $value->$s;
                }
            }
            if (count($with) > 0) {
                foreach ($with as $s) {
                    $data[$s] = $value->$s;
                }
            }

            return $data;
        });

        return $data;
    }
}
