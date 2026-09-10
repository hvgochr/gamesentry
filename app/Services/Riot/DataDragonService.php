<?php

namespace App\Services\Riot;

use RuntimeException;

class DataDragonService
{
    private const string BaseUrl = 'https://ddragon.leagueoflegends.com';

    private function version(): string
    {
        $version = config('services.riot.data_dragon_version');

        if (! is_string($version) || $version === '') {
            throw new RuntimeException('The Riot Data Dragon version is missing.');
        }

        return $version;
    }

    public function championImageUrl(string $champion): string
    {
        return sprintf(
            '%s/cdn/%s/img/champion/%s.png',
            self::BaseUrl,
            rawurlencode($this->version()),
            rawurlencode($champion),
        );
    }

    public function profileIconUrl(int $profileIconId): string
    {
        return sprintf(
            '%s/cdn/%s/img/profileicon/%d.png',
            self::BaseUrl,
            rawurlencode($this->version()),
            $profileIconId,
        );
    }
}
