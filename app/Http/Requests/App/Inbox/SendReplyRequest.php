<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Inbox;

use Illuminate\Foundation\Http\FormRequest;

class SendReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
