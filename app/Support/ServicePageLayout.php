<?php

namespace App\Support;

class ServicePageLayout
{
    public const STYLES = [
        'bento_featured' => [
            'label' => 'Featured Bento',
            'description' => 'One large hero card followed by a clean uniform grid — ideal for long service lists.',
            'icon' => 'star',
        ],
        'bento' => [
            'label' => 'Classic Bento',
            'description' => 'A large 2x2 feature card every four cards, surrounded by standard cards. Repeats cleanly no matter how many services you have.',
            'icon' => 'grid_view',
        ],
        'bento_wide' => [
            'label' => 'Wide Bento',
            'description' => 'Wide feature cards that alternate evenly with standard cards, filling every row perfectly at any scale.',
            'icon' => 'view_quilt',
        ],
        'bento_mosaic' => [
            'label' => 'Mosaic Bento',
            'description' => 'A large square card paired with two stacked cards, repeating in a balanced six-card cycle.',
            'icon' => 'dashboard_customize',
        ],
        'standard' => [
            'label' => 'Standard Grid',
            'description' => 'Uniform equal-size cards in a clean three-column grid that scales to any number of services.',
            'icon' => 'grid_on',
        ],
        'cards_2' => [
            'label' => 'Visiting Card',
            'description' => 'Elegant visiting-card style cards displayed two per row for a refined, editorial look.',
            'icon' => 'view_column',
        ],
        'list' => [
            'label' => 'List Layout',
            'description' => 'Full-width horizontal rows with the image on the left and details on the right.',
            'icon' => 'view_agenda',
        ],
    ];

    public static function normalize(?string $style): string
    {
        return array_key_exists((string) $style, self::STYLES)
            ? (string) $style
            : 'bento';
    }

    public static function gridClass(string $style): string
    {
        $bentoGrid = 'grid w-full grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3 md:auto-rows-[430px] xl:grid-flow-dense';

        return match (self::normalize($style)) {
            'list' => 'flex flex-col gap-6',
            'cards_2' => 'grid w-full grid-cols-1 gap-6 md:grid-cols-2',
            'bento', 'bento_wide', 'bento_mosaic', 'bento_featured' => $bentoGrid,
            default => 'grid w-full grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3',
        };
    }

    public static function cardClass(string $style, int $index): string
    {
        return match (self::normalize($style)) {
            'bento' => $index % 4 === 0
                ? 'md:col-span-2 xl:col-span-2 xl:row-span-2'
                : '',
            'bento_wide' => $index % 3 === 0
                ? 'xl:col-span-2'
                : '',
            'bento_mosaic' => $index % 6 === 0
                ? 'xl:col-span-2 xl:row-span-2'
                : '',
            'bento_featured' => $index === 0
                ? 'md:col-span-2 xl:col-span-2 xl:row-span-2'
                : '',
            default => '',
        };
    }

    public static function minHeightClass(string $style): string
    {
        return self::normalize($style) === 'list' ? '' : 'min-h-107.5';
    }

    public static function isList(string $style): bool
    {
        return self::normalize($style) === 'list';
    }

    public static function isCards(string $style): bool
    {
        return self::normalize($style) === 'cards_2';
    }
}
