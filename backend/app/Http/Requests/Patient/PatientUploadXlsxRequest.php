<?php

namespace App\Http\Requests\Patient;

use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PatientUploadXlsxRequest extends FormRequest
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
            'user_id' => 'required',
            'archiveXlsx' => 'required|file|extensions:xlsx',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El campo es obligatorio',
            'archiveXlsx.extensions' => 'El campo es archvio solo permite archivo de tipo xlsx',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user_id ? formattedElement($this->user_id) : null,
        ]);
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
