<?php

namespace MarvinLabs\DiscordLogger\Tests\Discord;

use Illuminate\Support\Str;
use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use MarvinLabs\DiscordLogger\Discord\Embed;
use MarvinLabs\DiscordLogger\Tests\TestCase;
use function strlen;
use PHPUnit\Framework\Attributes\Test;

class EmbedTest extends TestCase
{
    #[Test]
    public function description_is_truncated_to_2000_characters()
    {
        $longString = Str::random(DiscordWebHook::MAX_CONTENT_LENGTH + 500);

        $embed = Embed::make()->description($longString);

        $this->assertLessThanOrEqual(DiscordWebHook::MAX_CONTENT_LENGTH, strlen($embed->description));
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

        $this->assertEquals(
            ['title'       => 'my title',
             'description' => 'my description',
             'url'         => 'main.url',
             'color'       => 0x123456,
             'footer'      => ['text'     => 'my footer',
                               'icon_url' => 'footer-icon.url',],
             'image'       => ['url' => 'image.url'],
             'thumbnail'   => ['url' => 'thumbnail.url'],
             'author'      => ['name'     => 'John',
                               'url'      => 'avatar.url',
                               'icon_url' => 'author-icon.url',],
             'fields'      => [['name'   => 'first-field',
                                'value'  => 'foo',
                                'inline' => true,],
                               ['name'   => 'second-field',
                                'value'  => 'bar',
                                'inline' => false,],],
             'timestamp'   => '2000-01-01T12:13:14.000Z',
            ],
            $embed->toArray());
    }
}
