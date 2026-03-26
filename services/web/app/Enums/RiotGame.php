<?php

namespace App\Enums;

enum RiotGame: string
{
    case LeagueOfLegends = 'lol';
    case TeamfightTactics = 'tft';

    public function label(): string
    {
        return match ($this) {
            self::LeagueOfLegends => 'League of Legends',
            self::TeamfightTactics => 'Teamfight Tactics',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $game): array => [
                'value' => $game->value,
                'label' => $game->label(),
            ],
            self::cases(),
        );
    }
}
