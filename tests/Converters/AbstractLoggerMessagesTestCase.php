<?php

namespace MarvinLabs\DiscordLogger\Tests\Converters;

use DateTime;
use MarvinLabs\DiscordLogger\Contracts\RecordToMessage;
use MarvinLabs\DiscordLogger\Tests\Support\MessageAssertions;
use MarvinLabs\DiscordLogger\Tests\TestCase;
use Monolog\Logger;
use Throwable;
use function json_encode;
use PHPUnit\Framework\Attributes\Test;

abstract class AbstractLoggerMessagesTestCase extends TestCase
{
    /** @var \MarvinLabs\DiscordLogger\Contracts\RecordToMessage */
    protected $converter;

    /** @var \Illuminate\Contracts\Config\Repository */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->app['config'];
        $this->converter = $this->getConverter();
    }

    abstract protected function getConverter(): RecordToMessage;

    #[Test]
    public function message_is_sent_with_expected_from_fields()
    {
        $this->config->set('discord-logger.from.name', 'John');
        $this->config->set('discord-logger.from.avatar_url', 'http://example.com/avatar.png');

        $message = $this->warning('This is a test')[0];

        MessageAssertions::assertMessagePartialMatch([
            'username'   => 'John',
            'avatar_url' => 'http://example.com/avatar.png',
        ], $message);
    }

    protected function warning(string $message, array $context = [], array $extras = []): array
    {
        return $this->converter->buildMessages(
            $this->fakeRecord(Logger::WARNING, $message, $context, $extras));
    }

    protected function exception(string $message, Throwable $exception): array
    {
        return $this->converter->buildMessages(
            $this->fakeRecord(Logger::CRITICAL, $message, ['exception' => $exception]));
    }

    protected function fakeRecord(string $level, string $message, array $context = [], array $extra = []): array
    {
        $timestamp = DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:13:14');
        $serializedContext = json_encode($context);
        $serializedExtras = json_encode($context);
        $levelName = Logger::getLevelName($level);
        $formatted = "[2000-01-01 12:13:14] Laravel.$levelName: $message $serializedContext $serializedExtras\n";

        return [
            'message'    => $message,
            'level'      => $level,
            'channel'    => 'Laravel',
            'level_name' => $levelName,
            'datetime'   => $timestamp,
            'formatted'  => $formatted,
            'extra'      => $extra,
            'context'    => $context,
        ];
    }
}
