<?php

namespace App\Http\Requests\Discord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWatchedPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('watchedPlayer'));
    }

    public function rules(): array
    {
        return [
            'game' => ['required', Rule::in(['lol', 'tft'])],
            'routing_region' => ['required', Rule::in(['americas', 'asia', 'europe', 'sea'])],
            'game_name' => ['required', 'string', 'max:64'],
            'tag_line' => ['required', 'string', 'max:16'],
            'discord_user_id' => ['required', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
