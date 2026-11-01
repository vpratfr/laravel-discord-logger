<?php

namespace MarvinLabs\DiscordLogger\Tests\Converters;

use DateTime;
use Exception;
use MarvinLabs\DiscordLogger\Contracts\RecordToMessage;
use MarvinLabs\DiscordLogger\Converters\SimpleRecordConverter;
use MarvinLabs\DiscordLogger\Tests\Support\MessageAssertions;
use PHPUnit\Framework\Attributes\Test;

class SimpleLoggerMessagesTest extends AbstractLoggerMessagesTestCase
{
    protected function getConverter(): RecordToMessage
    {
        return new SimpleRecordConverter($this->config);
    }

    #[Test]
    public function sends_a_simple_message_for_log()
    {
        $this->config->set('discord-logger.emojis.WARNING', ':poop:');

        $message = $this->warning('This is a test')[0];

        MessageAssertions::assertMessagePartialMatch([
            'content' => ":poop: `[2000-01-01 12:13:14] Laravel.WARNING: This is a test [] []\n`",
        ], $message);
    }

    #[Test]
    public function does_not_include_emoji_if_disabled()
    {
        $this->config->set('discord-logger.emojis.WARNING', null);

        $message = $this->warning('This is a test')[0];

        MessageAssertions::assertMessagePartialMatch([
            'content' => "`[2000-01-01 12:13:14] Laravel.WARNING: This is a test [] []\n`",
        ], $message);
    }

    #[Test]
    public function includes_error_filename_and_line()
    {
        $this->config->set('discord-logger.stacktrace', 'inline');

        $exception = new Exception();
        $message =   $this->exception('This is a test', $exception)[0];

        $this->assertStringContainsString($exception->getFile(), $message->content);
        $this->assertStringContainsString($exception->getLine(), $message->content);
    }

    #[Test]
    public function includes_stacktrace_in_content_when_attachment_disabled()
    {
        $this->config->set('discord-logger.stacktrace', 'inline');

        $exception = new Exception();
        $message =   $this->exception('This is a test', $exception)[0];

        $this->assertStringContainsString('[2000-01-01 12:13:14] Laravel.CRITICAL: This is a test', $message->content);
        $this->assertStringContainsString("Stacktrace:\n" . strtok($exception->getTraceAsString(), "\n"), $message->content);
    }

    #[Test]
    public function includes_stacktrace_as_file_when_attachment_enabled()
    {
        $this->config->set('discord-logger.stacktrace', 'file');

        $exception = new Exception();
        $message =   $this->exception('This is a test', $exception)[0];

        $this->assertStringContainsString('[2000-01-01 12:13:14] Laravel.CRITICAL: This is a test', $message->content);

        MessageAssertions::assertMessagePartialMatch([
            'file' => ['filename' => '20000101121314_stacktrace.txt'],
        ], $message);
        $this->assertStringContainsString($exception->getTraceAsString(), $message->file['contents']);
    }
}
