<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AIPromptRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'prompt' => 'required|string|max:1000',
            'type' => 'nullable|in:invoice,reminder,insights,parsing',
        ];
    }

    public function messages()
    {
        return [
            'prompt.required' => 'Please describe what you need',
        ];
    }
}