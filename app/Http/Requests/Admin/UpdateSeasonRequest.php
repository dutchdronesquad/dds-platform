<?php

namespace App\Http\Requests\Admin;

use App\Models\Season;

class UpdateSeasonRequest extends StoreSeasonRequest
{
    public function authorize(): bool
    {
        $season = $this->route('season');

        return $season instanceof Season
            && $this->user()?->can('update', $season) === true;
    }
}
