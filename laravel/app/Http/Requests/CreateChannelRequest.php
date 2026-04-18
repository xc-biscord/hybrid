<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Covers POST /create_channel.php
 */
final class CreateChannelRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'server_id' => ['required', 'integer', 'min:1'],
            'name'      => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'server_id.required' => 'Requête invalide',
            'server_id.integer'  => 'Requête invalide',
            'server_id.min'      => 'Requête invalide',
            'name.required'      => 'Requête invalide',
            'name.string'        => 'Requête invalide',
        ];
    }
}
