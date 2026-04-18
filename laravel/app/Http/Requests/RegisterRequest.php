<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Covers POST /register.php
 * Preserves two distinct historical messages: 'Champs requis manquants' and 'Email invalide'.
 */
final class RegisterRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim((string) ($this->input('username') ?? '')),
            'email'    => trim((string) ($this->input('email') ?? '')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => 'Champs requis manquants',
            'username.string'   => 'Champs requis manquants',
            'email.required'    => 'Champs requis manquants',
            'email.email'       => 'Email invalide',
            'password.required' => 'Champs requis manquants',
            'password.string'   => 'Champs requis manquants',
        ];
    }
}
