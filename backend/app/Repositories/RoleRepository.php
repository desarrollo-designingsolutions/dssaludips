<?php

namespace App\Repositories;

use App\Helpers\Constants;
use App\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $modelo)
    {
        parent::__construct($modelo);
    }

    public function filter($request = [])
    {
        $cacheKey = $this->cacheService->generateKey("{$this->model->getTable()}_paginate", $request, 'string');

        return $this->cacheService->remember($cacheKey, function () use ($request) {
            $query = QueryBuilder::for($this->model->query())
                ->select(['id', 'description'])
                ->allowedFilters([
                    AllowedFilter::callback('inputGeneral', function ($query, $value) {
                        $query->where(function ($q) use ($value) {
                            $q->orWhere('description', 'like', "%$value%");
                        });
                    }),
                ])->where(function ($query) use ($request) {
                    $query->where('viewable', '1');

                    if (! empty($request['company_id'])) {
                        $query->where('company_id', $request['company_id']);
                    }
                })->get();

            return $query;
        }, Constants::REDIS_TTL);
    }

    public function store(array $request)
    {
        $request = $this->clearNull($request);

        if (! empty($request['id'])) {
            $data = $this->model->find($request['id']);
        } else {
            $data = $this->model::newModelInstance();
            // $data["guard_name"] = "api";
        }

        foreach ($request as $key => $value) {
            $data[$key] = is_array($request[$key]) ? $request[$key]['value'] : $request[$key];
        }

        $data->save();

        return $data;
    }

    public function selectList($request = [], $with = [], $select = [], $fieldValue = 'id', $fieldTitle = 'description')
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['idsAllowed'])) {
                $query->whereIn('id', $request['idsAllowed']);
            }
            if (! empty($request['company_id'])) {
                $query->where('company_id', $request['company_id']);
            }
            $query->where('viewable', '1');
        })->get()->map(function ($value) use ($with, $select, $fieldValue, $fieldTitle) {
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

    public function searchOne($request = [], $with = [])
    {
        $data = $this->model->with($with)->where(function ($query) use ($request) {
            if (! empty($request['name'])) {
                $query->where('name', $request['name']);
            }
            if (! empty($request['module'])) {
                $query->where('module', $request['module']);
            }
        });

        $data = $data->first();

        return $data;
    }
}
