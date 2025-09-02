<?php

namespace App\Http\Controllers;

use App\Repositories\RipRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class RipController extends Controller
{

    use HttpResponseTrait;

    public function __construct(
        private RipRepository $ripRepository,
    ) {
    }

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripRepository->paginate($request->all());
            $tableData = RipPaginateResource::collection($data);

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
