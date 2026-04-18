<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Covers POST /send_message.php
 */
final class SendMessageRequest extends BaseApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => trim((string) $this->input('content'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'channel_id' => ['required', 'integer', 'min:1'],
            'content'    => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'channel_id.required' => 'Message vide ou channel manquant',
            'channel_id.integer'  => 'Message vide ou channel manquant',
            'channel_id.min'      => 'Message vide ou channel manquant',
            'content.required'    => 'Message vide ou channel manquant',
            'content.string'      => 'Message vide ou channel manquant',
        ];
    }
}
