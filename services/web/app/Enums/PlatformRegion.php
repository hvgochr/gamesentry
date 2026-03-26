<?php

namespace App\Enums;

enum PlatformRegion: string
{
    case BR = 'br';
    case BR1 = 'br1';
    case EUNE = 'eune';
    case EUN1 = 'eun1';
    case EUW = 'euw';
    case EUW1 = 'euw1';
    case JP = 'jp';
    case JP1 = 'jp1';
    case KR = 'kr';
    case LA1 = 'la1';
    case LA2 = 'la2';
    case LAN = 'lan';
    case LAS = 'las';
    case ME1 = 'me1';
    case NA = 'na';
    case NA1 = 'na1';
    case OC1 = 'oc1';
    case OCE = 'oce';
    case RU = 'ru';
    case RU1 = 'ru1';
    case SG2 = 'sg2';
    case TR = 'tr';
    case TR1 = 'tr1';
    case TW2 = 'tw2';
    case VN2 = 'vn2';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function routingRegion(): RoutingRegion
    {
        return match ($this) {
            self::BR,
            self::BR1,
            self::LA1,
            self::LA2,
            self::LAN,
            self::LAS,
            self::NA,
            self::NA1 => RoutingRegion::Americas,
            self::JP,
            self::JP1,
            self::KR => RoutingRegion::Asia,
            self::EUNE,
            self::EUN1,
            self::EUW,
            self::EUW1,
            self::ME1,
            self::RU,
            self::RU1,
            self::TR,
            self::TR1 => RoutingRegion::Europe,
            self::OC1,
            self::OCE,
            self::SG2,
            self::TW2,
            self::VN2 => RoutingRegion::Sea,
        };
    }

    /**
     * @return array<int, array{value: string, label: string, routingRegion: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $region): array => [
                'value' => $region->value,
                'label' => $region->label(),
                'routingRegion' => $region->routingRegion()->value,
            ],
            self::cases(),
        );
    }
}
