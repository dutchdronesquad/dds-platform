<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;

class UpdateEventRequest extends StoreEventRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event
            && $this->user()?->can('update', $event) === true;
    }
}
