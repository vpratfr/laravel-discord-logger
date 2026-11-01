<?php

namespace MarvinLabs\DiscordLogger\Tests\Discord;

use Illuminate\Support\Str;
use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use MarvinLabs\DiscordLogger\Discord\Embed;
use MarvinLabs\DiscordLogger\Discord\Message;
use MarvinLabs\DiscordLogger\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MessageTest extends TestCase
{
    #[Test]
    public function content_is_truncated_to_2000_characters()
    {
        $longString = Str::random(DiscordWebHook::MAX_CONTENT_LENGTH + 500);

        $message = Message::make()->content($longString);

        $this->assertLessThanOrEqual(DiscordWebHook::MAX_CONTENT_LENGTH, strlen($message->content));
    }

    #[Test]
    public function can_convert_to_array()
    {
        $embed = Embed::make()
            ->title('my title', 'main.url')
            ->author('John', 'avatar.url', 'author-icon.url')
            ->description('my description')
            ->color(0x123456)
            ->image('image.url')
            ->thumbnail('thumbnail.url')
            ->field('first-field', 'foo', true)
            ->field('second-field', 'bar', false)
            ->footer('my footer', 'footer-icon.url')
            ->timestamp('2000-01-01T12:13:14.000Z');

        $message = Message::make()
            ->content('my content')
            ->from('John', 'avatar.url')
            ->tts()
            ->embed($embed)
            ->file('file content', 'example.txt');

        $this->assertEquals(
            ['content'    => 'my content',
             'username'   => 'John',
             'avatar_url' => 'avatar.url',
             'tts'        => 'true',
             'file'       => ['name'     => 'file',
                              'contents' => 'file content',
                              'filename' => 'example.txt',],
             'embeds'     => [$embed->toArray(),],
            ],
            $message->toArray());
    }

    #[Test]
    public function can_convert_message_without_embeds_to_array()
    {
        $message = Message::make()
            ->content('my content')
            ->from('John', 'avatar.url')
            ->tts(false)
            ->file('file content', 'example.txt');

        $this->assertEquals(
            ['content'    => 'my content',
             'username'   => 'John',
             'avatar_url' => 'avatar.url',
             'tts'        => 'false',
             'file'       => ['name'     => 'file',
                              'contents' => 'file content',
                              'filename' => 'example.txt',],
            ],
            $message->toArray());
    }
}
