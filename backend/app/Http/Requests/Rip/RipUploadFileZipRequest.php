<?php

namespace App\Http\Requests\Rip;

use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RipUploadFileZipRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'file' => 'required|file|mimes:zip|extensions:zip|max:51200', // Valida MIME (application/zip), extensión .zip, y tamaño máx 50MB
            'user_id' => 'required',
            'company_id' => 'required',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo es obligatorio',
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El archivo proporcionado no es válido.',
            'file.mimes' => 'El archivo debe ser de tipo ZIP.',
            'file.extensions' => 'El archivo debe tener extensión .zip.',
            'file.max' => 'El archivo no debe exceder 50MB.',
            'user_id.required' => 'El campo es obligatorio.',
            'company_id.required' => 'El campo es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        $this->merge($merge);
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => Constants::ERROR_MESSAGE_VALIDATION_BACK,
            'errors' => $validator->errors(),
        ], 422));
    }
}
