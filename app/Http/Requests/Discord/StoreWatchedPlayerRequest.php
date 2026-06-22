<?php

namespace App\Http\Requests\Discord;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWatchedPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('discordServer'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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

    /**
     * @return array{
     *     game: string,
     *     routing_region: string,
     *     game_name: string,
     *     tag_line: string,
     *     discord_user_id: string,
     *     is_active: bool
     * }
     */
    public function validatedPayload(): array
    {
        return [
            'game' => $this->string('game')->toString(),
            'routing_region' => $this->string('routing_region')->toString(),
            'game_name' => $this->string('game_name')->toString(),
            'tag_line' => $this->string('tag_line')->toString(),
            'discord_user_id' => $this->string('discord_user_id')->toString(),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ];
    }
}
