<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Covers POST /create_server.php
 * Accepts both legacy 'nom' and canonical 'name' fields.
 */
final class CreateServerRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('nom') && !$this->has('name')) {
            $this->merge(['name' => $this->input('nom')]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nom de serveur requis',
            'name.string'   => 'Nom de serveur requis',
        ];
    }
}
