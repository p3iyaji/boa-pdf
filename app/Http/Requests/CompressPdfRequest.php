<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompressPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_id' => [
                'required',
                'integer',
                Rule::exists('documents', 'id')->where('user_id', $this->user()->id),
            ],
            'level' => ['nullable', Rule::in(config('pdf.compression_levels'))],
        ];
    }
}
