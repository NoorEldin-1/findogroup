<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form_key' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'source_path' => ['required', 'string', 'max:2048', 'starts_with:/'],
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:10000'],
            'fields' => ['nullable', 'array', 'max:40'],
            'fields.*' => ['nullable', 'string', 'max:4000'],
            'website' => ['nullable', 'max:0'],
        ];
    }
}
