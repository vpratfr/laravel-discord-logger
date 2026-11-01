<?php


namespace MarvinLabs\DiscordLogger\Tests;


use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function can_provide_discord_webhook_instance()
    {
        $discord = $this->app->makeWith(DiscordWebHook::class, ['url' => 'http://example.com']);

        $this->assertNotNull($discord);
        $this->assertEquals('http://example.com', $discord->getUrl());
    }
}
