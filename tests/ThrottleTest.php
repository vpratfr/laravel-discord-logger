<?php

namespace MarvinLabs\DiscordLogger\Tests;

use Illuminate\Support\Facades\Cache;
use LogicException;
use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use MarvinLabs\DiscordLogger\Logger;
use MarvinLabs\DiscordLogger\Tests\Support\FakeDiscordWebHook;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ThrottleTest extends TestCase
{
    /** @var \MarvinLabs\DiscordLogger\Tests\Support\FakeDiscordWebHook */
    private $discordFake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('cache.default', 'array');

        $this->discordFake = new FakeDiscordWebHook('http://example.com');
        $this->app->bind(DiscordWebHook::class, function () {
            return $this->discordFake;
        });
    }

    private function monolog(array $config = []): \Monolog\Logger
    {
        return ($this->app->make(Logger::class))(array_merge(['level' => 'DEBUG', 'url' => 'http://example.com'], $config));
    }

    #[Test]
    public function repeated_messages_are_sent_when_throttle_is_disabled()
    {
        $monolog = $this->monolog();

        $monolog->error('Same error');
        $monolog->error('Same error');

        $this->discordFake->assertSendCount(2);
    }

    #[Test]
    public function repeated_messages_are_sent_once_when_throttle_is_enabled()
    {
        $monolog = $this->monolog(['throttle' => true]);

        $monolog->error('Same error');
        $monolog->error('Same error');
        $monolog->error('Another error');
        $monolog->warning('Same error');

        $this->discordFake->assertSendCount(3);
    }

    #[Test]
    public function same_exception_location_is_throttled_regardless_of_message()
    {
        $monolog = $this->monolog(['throttle' => true]);

        $sentPerRecord = null;
        foreach (['first', 'second'] as $message) {
            $monolog->error($message, ['exception' => new RuntimeException($message)]);
            $sentPerRecord ??= $this->discordFake->getSendCount();
        }
        $this->discordFake->assertSendCount($sentPerRecord);

        $monolog->error('third', ['exception' => new LogicException('third')]);
        $this->discordFake->assertSendCount(2 * $sentPerRecord);
    }

    #[Test]
    public function message_is_sent_again_once_throttle_expires()
    {
        $monolog = $this->monolog(['throttle' => true, 'throttle_seconds' => 60]);

        $monolog->error('Same error');
        $this->travel(61)->seconds();
        $monolog->error('Same error');

        $this->discordFake->assertSendCount(2);
    }

    #[Test]
    public function throttle_uses_the_configured_cache_store()
    {
        $this->app['config']->set('cache.stores.throttle', ['driver' => 'array']);
        $monolog = $this->monolog(['throttle' => true, 'throttle_cache_store' => 'throttle']);

        $monolog->error('Same error');
        Cache::store('array')->flush();
        $monolog->error('Same error');

        $this->discordFake->assertSendCount(1);
    }
}
