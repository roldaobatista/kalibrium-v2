<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:255'],
            'changes' => ['required', 'array', 'max:100'],
            'changes.*.local_id' => ['required', 'string', 'max:26'],
            'changes.*.entity_type' => ['required', 'string', 'in:note'],
            'changes.*.entity_id' => ['required', 'string', 'max:36'],
            'changes.*.action' => ['required', 'string', 'in:create,update,delete'],
            'changes.*.payload' => ['required', 'array'],
            'changes.*.payload.updated_at' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'changes.max' => 'Máximo de 100 mudanças por requisição.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Dados inválidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
