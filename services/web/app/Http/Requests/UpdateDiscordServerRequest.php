<?php

namespace App\Http\Requests;

use App\Models\DiscordServer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscordServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var DiscordServer|null $discordServer */
        $discordServer = $this->route('discordServer');

        return [
            'name' => ['required', 'string', 'max:120'],
            'discord_guild_id' => [
                'required',
                'string',
                'max:40',
                Rule::unique('discord_servers')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id))
                    ->ignore($discordServer?->id),
            ],
            'alert_channel_name' => ['nullable', 'string', 'max:120'],
            'alert_channel_id' => ['nullable', 'string', 'max:40'],
            'roast_enabled' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'discord_guild_id' => trim((string) $this->input('discord_guild_id')),
            'alert_channel_name' => $this->filled('alert_channel_name')
                ? trim((string) $this->input('alert_channel_name'))
                : null,
            'alert_channel_id' => $this->filled('alert_channel_id')
                ? trim((string) $this->input('alert_channel_id'))
                : null,
            'roast_enabled' => $this->boolean('roast_enabled'),
        ]);
    }
}
