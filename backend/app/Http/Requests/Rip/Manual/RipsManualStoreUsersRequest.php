<?php

namespace App\Http\Requests\Rip\Manual;

use App\Rules\UniqueInArray;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RipsManualStoreUsersRequest extends FormRequest
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
            'usersData' => 'required|array',
            'usersData.*.numDocumentoIdentificacion' => ['required', 'distinct'],
            'numDocumentoIdObligado' => 'required',
            'ripInvoice_id' => 'required',
            'company_id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'usersData.required' => 'El campo usersData es obligatorio.',
            'usersData.array' => 'El campo usersData debe ser un arreglo.',
            'usersData.*.numDocumentoIdentificacion.required' => 'El número de documento es obligatorio.',
            'usersData.*.numDocumentoIdentificacion.distinct' => 'El número de documento debe ser único dentro del listado.',
            'numDocumentoIdObligado.required' => 'El campo es obligatorio',
            'ripInvoice_id.required' => 'El campo es obligatorio',
            'company_id.required' => 'El campo es obligatorio',
        ];
    }


    protected function prepareForValidation(): void
    {
        // transforma campos sueltos (si los envían a ese nivel)
        $merge = [];

        // transforma cada elemento del array usersData si existe
        if ($this->has('usersData') && is_array($this->usersData)) {
            $users = collect($this->usersData)->map(function ($u) {
                // solo procesar si viene array/objeto; en caso contrario devolver tal cual
                if (is_array($u) || is_object($u)) {
                    $u = (array) $u;

                    if (isset($u['codMunicipioResidencia'])) {
                        $u['codMunicipioResidencia'] = getValueSelectInfinite($u['codMunicipioResidencia']);
                    }
                    if (isset($u['codPaisOrigen'])) {
                        $u['codPaisOrigen'] = getValueSelectInfinite($u['codPaisOrigen']);
                    }
                    if (isset($u['codPaisResidencia'])) {
                        $u['codPaisResidencia'] = getValueSelectInfinite($u['codPaisResidencia']);
                    }
                    if (isset($u['codSexo'])) {
                        $u['codSexo'] = getValueSelectInfinite($u['codSexo']);
                    }
                    if (isset($u['codZonaTerritorialResidencia'])) {
                        $u['codZonaTerritorialResidencia'] = getValueSelectInfinite($u['codZonaTerritorialResidencia']);
                    }
                    if (isset($u['tipoDocumentoIdentificacion'])) {
                        $u['tipoDocumentoIdentificacion'] = getValueSelectInfinite($u['tipoDocumentoIdentificacion']);
                    }
                    if (isset($u['tipoUsuario'])) {
                        $u['tipoUsuario'] = getValueSelectInfinite($u['tipoUsuario']);
                    }
                }
                return $u;
            })->toArray();

            $merge['usersData'] = $users;
        }

        $this->merge($merge);
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
