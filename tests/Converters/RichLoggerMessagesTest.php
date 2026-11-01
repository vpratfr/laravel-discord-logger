<?php

namespace MarvinLabs\DiscordLogger\Tests\Converters;

use Exception;
use MarvinLabs\DiscordLogger\Contracts\RecordToMessage;
use MarvinLabs\DiscordLogger\Converters\RichRecordConverter;
use MarvinLabs\DiscordLogger\Tests\Support\MessageAssertions;
use function json_encode;
use const JSON_PRETTY_PRINT;
use PHPUnit\Framework\Attributes\Test;

class RichLoggerMessagesTest extends AbstractLoggerMessagesTestCase
{
    protected function getConverter(): RecordToMessage
    {
        return new RichRecordConverter($this->config);
    }

    #[Test]
    public function sends_a_simple_message_for_log()
    {
        $this->config->set('discord-logger.colors.WARNING', 0x123456);
        $this->config->set('discord-logger.emojis.WARNING', ':poop:');

        $message = $this->warning('This is a test', ['foo' => 'bar'], [1, 2, 3, 'four'])[0];

        MessageAssertions::assertMessagePartialMatch([
            'embeds' => [
                0 => ['title'       => ':poop: `[2000-01-01 12:13:14] Laravel.WARNING`',
                      'description' => ':black_small_square: `This is a test`',
                      'color'       => 0x123456],
                1 => ['description' => "**Context**\n`" . json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT) . '`',
                      'color'       => 0x123456,],
                2 => ['description' => "**Extra**\n`" . json_encode([1, 2, 3, 'four'], JSON_PRETTY_PRINT) . '`',
                      'color'       => 0x123456,],
            ],
        ], $message);
    }

    #[Test]
    public function includes_error_filename_and_line()
    {
        $this->config->set('discord-logger.stacktrace', 'inline');

        $exception = new Exception();
        $message =   $this->exception('This is a test', $exception)[0];

        $this->assertStringContainsString($exception->getFile(), $message->embeds[1]->description);
        $this->assertStringContainsString($exception->getLine(), $message->embeds[1]->description);
    }

    #[Test]
    public function includes_stacktrace_in_content_when_attachment_disabled()
    {
        $this->config->set('discord-logger.stacktrace', 'inline');

        $exception = new Exception();
        $message =   $this->exception('This is a test', $exception)[0];

        $this->assertStringContainsString('[2000-01-01 12:13:14] Laravel.CRITICAL', $message->embeds[0]->title);
        $this->assertStringContainsString('This is a test', $message->embeds[0]->description);

        $this->assertStringContainsString("Stacktrace:\n" . strtok($exception->getTraceAsString(), "\n"), $message->embeds[1]->description);
    }

    #[Test]
    public function includes_stacktrace_as_file_when_attachment_enabled()
    {
        $this->config->set('discord-logger.emojis.CRITICAL', null);
        $this->config->set('discord-logger.stacktrace', 'file');

        $exception = new Exception();
        $messages = $this->exception('This is a test', $exception);

        MessageAssertions::assertMessagePartialMatch([
            'embeds' => [
                0 => ['title'       => '`[2000-01-01 12:13:14] Laravel.CRITICAL`',
                      'description' => '`This is a test`'],
            ],
        ], $messages[0]);

        MessageAssertions::assertMessagePartialMatch([
            'file' => ['filename' => '20000101121314_stacktrace.txt'],
        ], $messages[1]);
        $this->assertStringContainsString($exception->getTraceAsString(), $messages[1]->file['contents']);
    }

    #[Test]
    public function includes_message_as_file_when_longer_than_2000_characters()
    {
        $this->config->set('discord-logger.emojis.CRITICAL', null);

        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ut mi eu magna tempus auctor ac at ante. Quisque pulvinar, justo pretium ultricies auctor, risus eros mattis velit, quis rutrum tortor orci et urna. Sed suscipit nibh vel accumsan hendrerit. Etiam non elit nec diam auctor interdum. Morbi quam ligula, pharetra non felis sit amet, pulvinar venenatis ex. Sed suscipit, urna quis varius congue, urna lorem fermentum ipsum, in consectetur elit sapien non tortor. Nulla facilisi. Sed maximus metus quam, a venenatis sapien aliquet eu. Pellentesque lacinia urna non porta ornare. Donec elementum faucibus nibh at vulputate. Nulla consectetur facilisis ligula ut tempus. Mauris molestie risus in neque commodo, ac iaculis nibh lobortis. Nullam non condimentum dolor, ut tempus arcu. Duis eget lacus sit amet neque rutrum sagittis. Vestibulum suscipit aliquam ipsum ut accumsan. Phasellus dictum vulputate velit sit amet interdum. Nullam sed cursus dolor, eu bibendum ipsum. Sed facilisis, lectus non finibus porta, erat lorem ornare neque, nec pretium ipsum sem id risus. In consectetur auctor ullamcorper. Sed quis arcu vel metus mattis sollicitudin sit amet et risus. Sed metus felis, aliquam at consectetur ac, iaculis ut neque. Phasellus quis nisl justo. Nulla lorem velit, bibendum ornare urna ut, pulvinar tempus dui.  Vestibulum non pellentesque arcu. Fusce pretium massa elit, at congue ante porttitor sed. Aliquam erat volutpat. Nulla quam lectus, volutpat quis felis non, pharetra porttitor erat. Aliquam nunc felis, mattis pulvinar varius eu, fermentum et ligula. Proin tempor purus tempus rhoncus bibendum.Vestibulum magna tortor, vehicula eget neque id, aliquet pretium eros. Nulla ut justo rutrum mauris aliquam accumsan. Vestibulum non fringilla purus, eu faucibus lacus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus blandit risus libero, non ornare massa consequat in. Curabitur nec lectus nec purus pretium placerat. Nam interdum, nibh eu faucibus elementum, tortor justo hendrerit ante, quis fermentum dolor nulla a dolor. Donec auctor ut tortor sed faucibus. Maecenas et tellus a ex malesuada commodo. Aliquam mi arcu, dictum mattis egestas et, ultricies ut eros. Pellentesque eu elit felis. Aenean ut vestibulum libero, ut consequat turpis.';
        $embedDescription = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ut mi eu magna tempus auctor ac at ante. Quisque pulvinar, justo pretium ultricies auctor, risus eros mattis velit, quis rutrum tortor orci et urna. Sed suscipit nibh vel accumsan hendrerit. Etiam non elit nec diam auctor interdum. Morbi quam ligula, pharetra non felis sit amet, pulvinar venenatis ex. Sed suscipit, urna quis varius congue, urna lorem fermentum ipsum, in consectetur elit sapien non tortor. Nulla facilisi. Sed maximus metus quam, a venenatis sapien aliquet eu. Pellentesque lacinia urna non porta ornare. Donec elementum faucibus nibh at vulputate. Nulla consectetur facilisis ligula ut tempus. Mauris molestie risus in neque commodo, ac iaculis nibh lobortis. Nullam non condimentum dolor, ut tempus arcu. Duis eget lacus sit amet neque rutrum sagittis. Vestibulum suscipit aliquam ipsum ut accumsan. Phasellus dictum vulputate velit sit amet interdum. Nullam sed cursus dolor, eu bibendum ipsum. Sed facilisis, lectus non finibus porta, erat lorem ornare neque, nec pretium ipsum sem id risus. In consectetur auctor ullamcorper. Sed quis arcu vel metus mattis sollicitudin sit amet et risus. Sed metus felis, aliquam at consectetur ac, iaculis ut neque. Phasellus quis nisl justo. Nulla lorem velit, bibendum ornare urna ut, pulvinar tempus dui.  Vestibulum non pellentesque arcu. Fusce pretium massa elit, at congue ante porttitor sed. Aliquam erat volutpat. Nulla quam lectus, volutpat quis felis non, pharetra porttitor erat. Aliquam nunc felis, mattis pulvinar varius eu, fermentum et ligula. Proin tempor purus tempus rhoncus bibendum.Vestibulum magna tortor, vehicula eget neque id, aliquet pretium eros. Nulla ut justo rutrum mauris aliquam accumsan. Vestibulum non fringilla purus, eu faucibus lacus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus blandit risus libero, non ornare massa consequat in. Curabitur nec lectus nec purus pretium placerat. Nam interdum,...';
        $messages = $this->warning($description);

        MessageAssertions::assertMessagePartialMatch([
            'embeds' => [
                0 => ['title'       => ':flushed: `[2000-01-01 12:13:14] Laravel.WARNING`',
                      'description' => ':black_small_square: `' . $embedDescription],
            ],
        ], $messages[0]);

        MessageAssertions::assertMessagePartialMatch([
            'file' => ['filename' => '20000101121314_stacktrace.txt'],
        ], $messages[1]);

        $this->assertStringContainsString($description, $messages[1]->file['contents']);
    }

    #[Test]
    public function includes_only_message_when_less_than_2000_characters()
    {
        $this->config->set('discord-logger.emojis.CRITICAL', null);

        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ut mi eu magna tempus auctor ac at ante. Quisque pulvinar, justo pretium ultricies auctor, risus eros mattis velit, quis rutrum tortor orci et urna. Sed suscipit nibh vel accumsan hendrerit. Etiam non elit nec diam auctor interdum. Morbi quam ligula, pharetra non felis sit amet, pulvinar venenatis ex. Sed suscipit, urna quis varius congue, urna lorem fermentum ipsum, in consectetur elit sapien non tortor. Nulla facilisi. Sed maximus metus quam, a venenatis sapien aliquet eu. Pellentesque lacinia urna non porta ornare. Donec elementum faucibus nibh at vulputate. Nulla consectetur facilisis ligula ut tempus. Mauris molestie risus in neque commodo, ac iaculis nibh lobortis. Nullam non condimentum dolor, ut tempus arcu. Duis eget lacus sit amet neque rutrum sagittis. Vestibulum suscipit aliquam ipsum ut accumsan. Phasellus dictum vulputate velit sit amet interdum. Nullam sed cursus dolor, eu bibendum ipsum. Sed facilisis, lectus non finibus porta, erat lorem ornare neque, nec pretium ipsum sem id risus. In consectetur auctor ullamcorper. Sed quis arcu vel metus mattis sollicitudin sit amet et risus. Sed metus felis, aliquam at consectetur ac, iaculis ut neque. Phasellus quis nisl justo. Nulla lorem velit, bibendum ornare urna ut, pulvinar tempus dui.  Vestibulum non pellentesque arcu. Fusce pretium massa elit, at congue ante porttitor sed. Aliquam erat volutpat. Nulla quam lectus, volutpat quis felis non, pharetra porttitor erat. Aliquam nunc felis, mattis pulvinar varius eu, fermentum et ligula. Proin tempor purus tempus rhoncus bibendum.Vestibulum magna tortor, vehicula eget neque id, aliquet pretium eros. Nulla ut justo rutrum mauris aliquam accumsan. Vestibulum non fringilla purus, eu faucibus lacus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus blandit risus libero, non ornare massa consequat in. Curabitur nec lectus nec purus pretium placerat.';
        $messages = $this->warning($description);

        MessageAssertions::assertMessagePartialMatch([
            'embeds' => [
                0 => ['title'       => ':flushed: `[2000-01-01 12:13:14] Laravel.WARNING`',
                      'description' => ':black_small_square: `' . $description . '`'],
            ],
        ], $messages[0]);

        $this->assertArrayNotHasKey(1, $messages);
    }
}
