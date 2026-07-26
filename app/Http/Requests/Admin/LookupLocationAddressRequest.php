<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupLocationAddressRequest extends FormRequest
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
            'id' => ['required', 'string', 'max:255'],
            'source' => ['required', Rule::in(['pdok', 'photon'])],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'id' => 'adres',
            'source' => 'bron',
        ];
    }
}
