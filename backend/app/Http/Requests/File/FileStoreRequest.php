<?php

namespace App\Http\Requests\File;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FileStoreRequest extends FormRequest
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
        $rules = [
            'filename' => 'required',

            'user_id' => 'required',
            'company_id' => 'required',
            'fileable_type' => 'required',
            'fileable_id' => 'required',
        ];

        if ($this->id && $this->file) {
            $rules['file'] = 'required|file|max:30720|extensions:jpg,jpeg,png,doc,docx,xls,xlsx,pdf,ppt,pptx'; // Ejemplo: permitir JPEG, PNG, PDF, DOC, DOCX; tamaño máximo 30MB
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'user_id.required' => 'El campo es obligatorio',
            'company_id.required' => 'El campo es obligatorio',
            'filename.required' => 'El campo es obligatorio',
            'fileable_type.required' => 'El campo es obligatorio',
            'fileable_id.required' => 'El campo es obligatorio',

            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El archivo debe ser un archivo válido.',
            'file.extensions' => 'El archivo debe ser una imagen (JPEG, PNG) o documento (PDF, DOC, DOCX,PDF,XLS,XLSX).',
            'file.max' => 'El tamaño máximo del archivo es 30MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([]);
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
