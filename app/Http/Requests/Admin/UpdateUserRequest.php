<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        if (! $this->has('roles')) {
            $this->merge(['roles' => []]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && $this->user()?->can('update', $user) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            ...$this->profileRules($user->id),
            'locale' => ['required', 'string', Rule::in(array_keys(config('localization.supported_locales', [])))],
            'roles' => ['present', 'array'],
            'roles.*' => ['string', 'distinct', Rule::enum(Role::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'naam',
            'email' => 'e-mailadres',
            'locale' => 'taalvoorkeur',
            'roles' => 'rollen',
            'roles.*' => 'rol',
        ];
    }

    /** @return array{name: string, email: string, locale: string, roles: list<string>} */
    public function userData(): array
    {
        $validatedRoles = $this->validated('roles');
        $roles = is_array($validatedRoles)
            ? array_values(array_filter($validatedRoles, is_string(...)))
            : [];

        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->lower()->toString(),
            'locale' => $this->string('locale')->toString(),
            'roles' => $roles,
        ];
    }
}
