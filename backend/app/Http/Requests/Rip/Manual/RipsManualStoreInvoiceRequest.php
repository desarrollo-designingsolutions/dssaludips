<?php

namespace App\Http\Requests\Rip\Manual;

use App\Rules\UniqueInArray;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RipsManualStoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'invoicesData' => 'required|array',
            'invoicesData.*.numFactura' => ['required', 'distinct'],
            'numDocumentoIdObligado' => 'required',
            'rip_id' => 'required',
            'company_id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'invoicesData.required' => 'El campo invoicesData es obligatorio.',
            'invoicesData.array' => 'El campo invoicesData debe ser un arreglo.',
            'invoicesData.*.numFactura.required' => 'El número de factura es obligatorio.',
            'invoicesData.*.numFactura.distinct' => 'El número de factura debe ser único dentro del listado.',
            'numDocumentoIdObligado.required' => 'El campo es obligatorio',
            'rip_id.required' => 'El campo es obligatorio',
            'company_id.required' => 'El campo es obligatorio',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => 'Se evidencia algunos errores',
            'errors' => $validator->errors(),
        ], 422));
    }
}
