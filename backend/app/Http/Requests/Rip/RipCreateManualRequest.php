<?php

namespace App\Http\Requests\Rip;

use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RipCreateManualRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'service_vendor_id' => 'required',
            'user_id' => 'required',
            'company_id' => 'required',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'service_vendor_id.required' => 'El campo es obligatorio.',
            'user_id.required' => 'El campo es obligatorio.',
            'company_id.required' => 'El campo es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('service_vendor_id')) {
            $merge['service_vendor_id'] = getValueSelectInfinite($this->service_vendor_id);
        }

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
