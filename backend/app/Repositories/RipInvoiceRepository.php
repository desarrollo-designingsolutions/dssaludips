<?php

namespace App\Repositories;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Helpers\Constants;
use App\Models\RipInvoice;
use App\QueryBuilder\Filters\QueryFilters;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RipInvoiceRepository extends BaseRepository
{
    public function __construct(RipInvoice $modelo)
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
                            $subQuery->orWhere('invoice_number', 'like', "%$value%");
                            $subQuery->orWhere('count_users', 'like', "%$value%");

                            $subQuery->orWhere(function ($subQuery2) use ($value) {
                                $normalizedValue = preg_replace('/[\$\s\.,]/', '', $value);
                                $subQuery2->orWhere('sumVr', 'like', "%$normalizedValue%");
                            });

                            QueryFilters::filterByText($subQuery, $value, 'status', [
                                RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001->description() => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_001,
                                RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002->description() => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_002,
                                RipInvoiceStatusEnum::RIP_INVOICE_STATUS_003->description() => RipInvoiceStatusEnum::RIP_INVOICE_STATUS_003,
                            ]);

                            QueryFilters::filterByText($subQuery, $value, 'status_xml', [
                                RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001->description() => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_001,
                                RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002->description() => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_002,
                                RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003->description() => RipInvoiceStatusXmlEnum::RIP_INVOICE_STATUS_XML_003,
                            ]);
                        });
                    }),
                ])
                ->allowedSorts([
                    'invoice_number',
                    'count_users',
                    'sumVr',
                    'status',
                    'status_xml',
                ])
                ->where(function ($query) use ($request) {
                    if (!empty($request['company_id'])) {
                        $query->where('company_id', $request['company_id']);
                    }
                    if (!empty($request['rip_id'])) {
                        $query->where('rip_id', $request['rip_id']);
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
