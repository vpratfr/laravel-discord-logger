<?php

namespace MarvinLabs\DiscordLogger\Tests\Discord;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Config;
use MarvinLabs\DiscordLogger\Discord\Embed;
use MarvinLabs\DiscordLogger\Discord\GuzzleWebHook;
use MarvinLabs\DiscordLogger\Discord\Message;
use MarvinLabs\DiscordLogger\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

#[Group('RequiresNetwork')]
class LiveClientTest extends TestCase
{
    /** @var \MarvinLabs\DiscordLogger\Discord\GuzzleWebHook */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $webHookUrl = $this->app['config']->get('logging.channels.discord.url');
        if ($webHookUrl === null)
        {
            // You can add that line to phpunit.xml and configure your logging.php config file accordingly
            // <server name="LOG_DISCORD_WEBHOOK_URL" value="https://discordapp.com/api/webhooks/<abc>/<xyz>"/>
            $this->markTestSkipped('Live test is ignored as you have not configured a logging channel with a real webhook');
        }

        $this->client = new GuzzleWebHook(new HttpClient(), $webHookUrl);
    }

    #[Test]
    public function can_send_a_simple_message()
    {
        $this->client->send(Message::make('This is a test'));
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function can_send_a_message_with_file()
    {
        $this->client->send(Message::make('This is a test with a file')
            ->file('This is the content of the file', 'example.txt'));
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function can_send_a_message_with_embeds()
    {
        $this->client->send(
            Message::make('This is a test with embeds')
                ->from('John Doe', 'http://lorempixel.com/100/100/people')
                ->embed(Embed::make()
                    ->title('This is a title', 'http://example.com')
                    ->color(0x345987)
                    ->author('Jane Dane', 'http://example.com/john', 'http://lorempixel.com/100/100/people')
                    ->image('http://lorempixel.com/300/100/nature'))
                ->embed(Embed::make()
                    ->color(0x789678)
                    ->thumbnail('http://lorempixel.com/200/200/cats')
                    ->description("`This is a sample code block\n\nAnd more code here`"))
                ->embed(Embed::make()
                    ->color(0xab7812)
                    ->footer('I am testing the footer', 'http://lorempixel.com/100/100/transport')
                    ->field('field-1', 'foo', true)
                    ->field('field-2', 'bar', true)
                    ->field('field-3', 'baz', false)));

        $this->expectNotToPerformAssertions();
    }
}
