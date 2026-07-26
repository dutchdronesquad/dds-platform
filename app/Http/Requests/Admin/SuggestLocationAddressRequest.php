<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SuggestLocationAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::CreateLocations->value) === true
            || $this->user()?->can(Permission::UpdateLocations->value) === true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:4', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['q' => 'zoekterm'];
    }
}
