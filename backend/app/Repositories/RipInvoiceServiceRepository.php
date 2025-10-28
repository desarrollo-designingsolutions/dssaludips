<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\RipInvoiceUser;
use App\Models\RipServiceProcedure;
use App\Models\RipServiceQuery;
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
                            $subQuery->orWhere('consecutivo', 'like', "%$value%");
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
                            $subQuery->orWhere('consecutivo', 'like', "%$value%");
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
