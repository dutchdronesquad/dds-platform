<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class UpdateEventRequest extends StoreEventRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'track_action' => ['sometimes', 'required', 'in:keep,replace,remove'],
            'track_connection_id' => ['exclude_unless:track_action,replace', 'required', 'integer', 'exists:track_draw_connections,id'],
            'track_project_id' => ['exclude_unless:track_action,replace', 'required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ];
    }

    /** @return array<string, mixed> */
    public function eventData(): array
    {
        return Arr::except(parent::eventData(), ['track_action', 'track_connection_id', 'track_project_id']);
    }

    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && $this->user()?->can('update', $event) === true;
    }
}
