<?php

namespace App\Http\Requests;

use App\Services\PdfConversionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertPdfRequest extends FormRequest
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
            'target' => ['required', Rule::in(PdfConversionService::SUPPORTED_TARGETS)],
        ];
    }
}
