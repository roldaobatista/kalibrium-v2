<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_identifier' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-_:]+$/'],
            'device_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
