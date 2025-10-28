<?php

namespace App\Http\Controllers;

use App\Helpers\Rips\ServiceMapper;
use App\Http\Resources\RipServiceHospitalization\RipServiceHospitalizationPaginateResource;
use App\Http\Resources\RipServiceMedicine\RipServiceMedicinePaginateResource;
use App\Http\Resources\RipServiceNewlyBorn\RipServiceNewlyBornPaginateResource;
use App\Http\Resources\RipServiceOtherService\RipServiceOtherServicePaginateResource;
use App\Http\Resources\RipServiceProcedure\RipServiceProcedurePaginateResource;
use App\Http\Resources\RipServiceQuery\RipServiceQueryPaginateResource;
use App\Http\Resources\RipServiceUrgency\RipServiceUrgencyPaginateResource;
use App\Repositories\RipInvoiceServiceRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;
use App\Repositories\RipInvoiceUserRepository;

class RipInvoiceServiceController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipInvoiceUserRepository $ripInvoiceUserRepository,
        private RipInvoiceServiceRepository $ripInvoiceServiceRepository,
    ) {}

    public function getInfoUser($ripInvoiceUser_id)
    {
        return $this->execute(function () use ($ripInvoiceUser_id) {
            $data = $this->ripInvoiceUserRepository->find($ripInvoiceUser_id);

            $userData = [
                'numFactura' => $data->ripInvoice?->invoice_number,
                'totalServicesCount' => $data->totalServiceCounts(),
            ];

            $services = ServiceMapper::getServicesForUser($data);

            $servicesCount = [];
            foreach ($services as $k => $arr) {
                $servicesCount[$k] = count($arr);
            }

            return [
                'code' => 200,
                'userData' => $userData,
                'servicesCount' => $servicesCount,
            ];
        });
    }

    public function paginateQueries(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateQueries($request->all());
            $tableData = RipServiceQueryPaginateResource::collection($data);

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

    public function paginateProcedures(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateProcedures($request->all());
            $tableData = RipServiceProcedurePaginateResource::collection($data);

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

    public function paginateUrgencies(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateUrgencies($request->all());
            $tableData = RipServiceUrgencyPaginateResource::collection($data);

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

    public function paginateHospitalizations(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateHospitalizations($request->all());
            $tableData = RipServiceHospitalizationPaginateResource::collection($data);

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

    public function paginateNewlyBorns(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateNewlyBorns($request->all());
            $tableData = RipServiceNewlyBornPaginateResource::collection($data);

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

    public function paginateMedicines(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateMedicines($request->all());
            $tableData = RipServiceMedicinePaginateResource::collection($data);

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

    public function paginateOtherServices(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceServiceRepository->paginateOtherServices($request->all());
            $tableData = RipServiceOtherServicePaginateResource::collection($data);

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
}
