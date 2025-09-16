<?php

namespace App\Http\Controllers;

use App\Http\Resources\RipInvoice\RipInvoicePaginateResource;
use App\Repositories\RipInvoiceRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class RipInvoiceController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        private RipInvoiceRepository $ripInvoiceRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->ripInvoiceRepository->paginate($request->all());
            $tableData = RipInvoicePaginateResource::collection($data);

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


    public function downloadJson($id)
    {
        // Buscar el registro en el repositorio
        $ripInvoice = $this->ripInvoiceRepository->find($id);

        // Verificar si existe el registro
        if (!$ripInvoice) {
            return response()->json(['message' => 'Factura no encontrado.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $ripInvoice->path_json);

        // Verificar si existe el archivo JSON
        if (!$ripInvoice->path_json || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo JSON no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($ripInvoice->path_json);

        // Retornar la respuesta con el archivo JSON para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function downloadExcel($id)
    {
        // Buscar el registro en el repositorio
        $ripInvoice = $this->ripInvoiceRepository->find($id);

        // Verificar si existe el registro
        if (!$ripInvoice) {
            return response()->json(['message' => 'Factura no encontrada.'], 404);
        }

        // Construir la ruta completa del archivo
        $filePath = storage_path('app/public/' . $ripInvoice->path_excel);

        // Verificar si existe el archivo Excel
        if (!$ripInvoice->path_excel || !file_exists($filePath)) {
            return response()->json(['message' => 'Archivo Excel no encontrado.'], 404);
        }

        // Obtener el nombre del archivo desde la ruta
        $fileName = basename($ripInvoice->path_excel);

        // Retornar la respuesta con el archivo Excel para descarga
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
