<?php

namespace App\Repositories;

use App\Enums\Rip\RipStatusEnum;
use App\Enums\Rip\RipTypeEnum;
use App\Helpers\Constants;
use App\Models\Rip;
use App\QueryBuilder\Filters\QueryFilters;
use App\QueryBuilder\Sort\DynamicConcatSort;
use App\QueryBuilder\Sort\DynamicJoinConcatSort;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class RipRepository extends BaseRepository
{
    public function __construct(Rip $modelo)
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
                            $subQuery->orWhere('numInvoices', 'like', "%$value%");
                            $subQuery->orWhere('successfulInvoices', 'like', "%$value%");
                            $subQuery->orWhere('failedInvoices', 'like', "%$value%");

                            QueryFilters::filterByText($subQuery, $value, 'type', [
                                RipTypeEnum::RIP_TYPE_001->description() => RipTypeEnum::RIP_TYPE_001,
                            ]);

                            QueryFilters::filterByText($subQuery, $value, 'status', [
                                RipStatusEnum::RIP_STATUS_001->description() => RipStatusEnum::RIP_STATUS_001,
                            ]);

                            QueryFilters::filterByDMYtoYMD($subQuery, $value, 'created_at');

                        });
                    }),
                ])
                ->allowedSorts([
                    'numInvoices',
                    'successfulInvoices',
                    'failedInvoices',
                    'type',
                    'status',
                    'created_at',
                    AllowedSort::custom('user_full_name', new DynamicJoinConcatSort(
                        concat: "users.name, ' ', users.surname",
                        relatedTable: 'users',
                        foreignKey: 'user_id'
                    )),
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['company_id'])) {
                        $query->where('company_id', $request['company_id']);
                    }
                });

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
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
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
