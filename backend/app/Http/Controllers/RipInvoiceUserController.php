<?php

namespace App\Http\Controllers;

use App\Http\Resources\RipInvoiceUser\RipInvoiceUserPaginateResource;
use App\Repositories\RipInvoiceRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use App\Repositories\RipInvoiceUserRepository;

class RipInvoiceUserController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipInvoiceUserRepository $ripInvoiceUserRepository,
        private RipInvoiceRepository $ripInvoiceRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceUserRepository->paginate($request->all());
            $tableData = RipInvoiceUserPaginateResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function getInfoInvoice($ripInvoice_id)
    {
        return $this->execute(function () use ($ripInvoice_id) {
            $data = $this->ripInvoiceRepository->find($ripInvoice_id);

            return [
                'code' => 200,
                'invoice' => [
                    'id' => $data->id,
                    'rip_id' => $data->rip_id,
                    'invoice_number' => $data->invoice_number,
                    'count_users' => $data->count_users,
                ],
            ];
        });
    }
}
