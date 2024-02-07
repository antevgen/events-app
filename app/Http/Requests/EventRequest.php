<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RecurrentFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'bail|required|string|max:50',
            'description' => 'bail|string|max:255',
            'start_at' => 'bail|required|date_format:'.\DateTime::ATOM,
            'end_at' => 'bail|required|date_format:'.\DateTime::ATOM.'|after:start_at',
            'recurrent' => 'bail|required|boolean',
            'frequency' => [
                'bail',
                'exclude_unless:recurrent,true',
                'required',
                Rule::enum(RecurrentFrequency::class),
            ],
            'repeat_until' => 'bail|exclude_unless:recurrent,true|required|date_format:'.\DateTime::ATOM.'|after:end_at',
        ];
    }
}
