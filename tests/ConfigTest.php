<?php

namespace MarvinLabs\DiscordLogger\Tests;

use Monolog\Level;
use function config;
use PHPUnit\Framework\Attributes\Test;

class ConfigTest extends TestCase
{
    #[Test]
    public function colors_cover_all_log_levels()
    {
        $colors = config('discord-logger.colors');

        collect(Level::cases())
            ->map(static function (Level $level) {
                return $level->getName();
            })
            ->each(function ($level) use ($colors) {
                $this->assertArrayHasKey($level, $colors, "Color for level $level is not configured");
            });
    }

    #[Test]
    public function emojis_cover_all_log_levels()
    {
        $colors = config('discord-logger.emojis');

        collect(Level::cases())
            ->map(static function (Level $level) {
                return $level->getName();
            })
            ->each(function ($level) use ($colors) {
                $this->assertArrayHasKey($level, $colors, "Emoji for level $level is not configured");
            });
    }
}
