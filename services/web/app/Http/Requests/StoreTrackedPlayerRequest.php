<?php

namespace App\Http\Requests;

use App\Models\TrackedPlayer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTrackedPlayerRequest extends FormRequest
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
        return [
            'discord_server_id' => [
                'required',
                'integer',
                Rule::exists('discord_servers', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id),
                ),
            ],
            'game' => ['required', 'string', Rule::in(array_keys(TrackedPlayer::GAME_LABELS))],
            'riot_name' => [
                'required',
                'string',
                'max:32',
                Rule::unique('tracked_players', 'riot_name')->where(
                    fn ($query) => $query
                        ->where('discord_server_id', (int) $this->input('discord_server_id'))
                        ->where('game', (string) $this->input('game'))
                        ->where('riot_tagline', Str::upper(trim((string) $this->input('riot_tagline')))),
                ),
            ],
            'riot_tagline' => ['required', 'string', 'max:10'],
            'region' => ['required', 'string', 'max:32'],
            'discord_user_id' => ['required', 'string', 'max:40'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'riot_name.unique' => 'This player is already being tracked for the selected server and game.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'discord_server_id' => $this->input('discord_server_id'),
            'game' => Str::lower(trim((string) $this->input('game'))),
            'riot_name' => trim((string) $this->input('riot_name')),
            'riot_tagline' => Str::upper(trim((string) $this->input('riot_tagline'))),
            'region' => Str::lower(trim((string) $this->input('region'))),
            'discord_user_id' => trim((string) $this->input('discord_user_id')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
