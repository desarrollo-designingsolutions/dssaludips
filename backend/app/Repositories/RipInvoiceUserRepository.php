<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\RipInvoiceUser;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RipInvoiceUserRepository extends BaseRepository
{
    public function __construct(RipInvoiceUser $modelo)
    {
        parent::__construct($modelo);
    }

    public function paginate($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for($this->model->query())
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($subQuery) use ($value) {
                            $subQuery->orWhere('consecutivo', 'like', "%$value%");
                            $subQuery->orWhere('numDocumentoIdentificacion', 'like', "%$value%");
                            $subQuery->orWhere('fechaNacimiento', 'like', "%$value%");
                            $subQuery->orWhere('incapacidad', 'like', "%$value%");

                            $subQuery->orWhereHas('tipoDocumento', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('tipoUsuarioRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('paisResidencia', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('municipioResidencia', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('sexoRelation', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('zonaResidencia', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });

                            $subQuery->orWhereHas('paisOrigen', function ($q) use ($value) {
                                $q->where('nombre', 'like', "%{$value}%")
                                    ->orWhere('codigo', $value);
                            });
                        });
                    }),
                ])
                ->allowedSorts([
                    'consecutivo',
                    'tipoDocumentoIdentificacion',
                    'numDocumentoIdentificacion',
                    'tipoUsuario',
                    'fechaNacimiento',
                    'codSexo',
                    'codPaisResidencia',
                    'codMunicipioResidencia',
                    'codZonaTerritorialResidencia',
                    'incapacidad',
                    'codPaisOrigen',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['rip_invoice_id'])) {
                        $query->where('rip_invoice_id', $request['rip_invoice_id']);
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
