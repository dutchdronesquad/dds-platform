<?php

namespace App\Http\Requests\Public;

use App\Enums\ContactTopic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'topic' => ['required', Rule::enum(ContactTopic::class)],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'consent' => ['accepted'],
            'source_context' => ['nullable', 'string', 'max:255'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Vul je naam in.',
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'topic.required' => 'Kies waar je vraag over gaat.',
            'topic.enum' => 'Kies een geldig onderwerp.',
            'message.required' => 'Schrijf kort waarmee we je kunnen helpen.',
            'message.min' => 'Beschrijf je vraag in minimaal 20 tekens.',
            'message.max' => 'Je bericht mag maximaal 5.000 tekens bevatten.',
            'consent.accepted' => 'Geef toestemming om je bericht te verwerken.',
            'website.prohibited' => 'Het formulier kon niet worden verzonden.',
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     topic: string,
     *     message: string,
     *     consent: bool,
     *     source_context: string|null
     * }
     */
    public function contactData(): array
    {
        $validated = $this->safe();

        return [
            'name' => $validated->string('name')->toString(),
            'email' => $validated->string('email')->toString(),
            'topic' => $validated->string('topic')->toString(),
            'message' => $validated->string('message')->toString(),
            'consent' => $validated->boolean('consent'),
            'source_context' => $validated->filled('source_context')
                ? $validated->string('source_context')->toString()
                : null,
        ];
    }
}
