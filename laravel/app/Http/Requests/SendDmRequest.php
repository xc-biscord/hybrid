<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Covers POST /send_dm.php
 */
final class SendDmRequest extends BaseApiFormRequest
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
            'conversation_id' => ['required', 'integer', 'min:1'],
            'content'         => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Conversation ou contenu manquant',
            'conversation_id.integer'  => 'Conversation ou contenu manquant',
            'conversation_id.min'      => 'Conversation ou contenu manquant',
            'content.required'         => 'Conversation ou contenu manquant',
            'content.string'           => 'Conversation ou contenu manquant',
        ];
    }
}
