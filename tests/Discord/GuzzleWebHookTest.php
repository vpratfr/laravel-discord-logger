<?php

namespace MarvinLabs\DiscordLogger\Tests\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MarvinLabs\DiscordLogger\Discord\Exceptions\MessageCouldNotBeSent;
use MarvinLabs\DiscordLogger\Discord\GuzzleWebHook;
use MarvinLabs\DiscordLogger\Discord\Message;
use MarvinLabs\DiscordLogger\Tests\TestCase;
use function explode;
use function json_decode;
use PHPUnit\Framework\Attributes\Test;

class GuzzleWebHookTest extends TestCase
{
    /** @var array */
    private $httpHistory;

    /** @var \MarvinLabs\DiscordLogger\Contracts\DiscordWebHook */
    private $discord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpHistory = [];

        $stack = HandlerStack::create(new MockHandler([new Response()]));
        $stack->push(Middleware::history($this->httpHistory));
        $httpClient = new Client(['handler' => $stack]);

        $this->discord = new GuzzleWebHook($httpClient, 'http://example.com');
    }

    /** @test */
    public function forms_json_request_when_no_file()
    {
        $this->discord->send(Message::make()->content('No files attached'));

        $this->assertCount(1, $this->httpHistory);

        tap($this->httpHistory[0]['request'], function (Request $request) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('application/json', $request->getHeader('Content-Type')[0]);

            $payload = json_decode($request->getBody()->getContents(), true);
            $this->assertEquals('No files attached', $payload['content']);
        });
    }

    /** @test */
    public function forms_multipart_request_when_file_present()
    {
        $this->discord->send(Message::make()->content('File attached')->file('test', 'test.txt'));

        $this->assertCount(1, $this->httpHistory);

        tap($this->httpHistory[0]['request'], function (Request $request) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));
            $payload = collect(explode('--', $request->getBody()->getContents()))
                ->map(static function ($data) {
                    return explode("\r\n", $data);
                })
                ->map(static function ($data) {
                    return collect($data)->reject(static function ($item) {
                        return empty($item);
                    })->toArray();
                })
                ->map(static function ($data) {
                    return \array_filter(['name' => $data[1] ?? null, 'contents' => $data[4] ?? null]);
                })
                ->reject(static function ($item) {
                    return empty($item);
                })
                ->toArray();

            $this->assertEquals('Content-Disposition: form-data; name="content"', $payload[1]['name']);
            $this->assertEquals('Content-Disposition: form-data; name="tts"', $payload[2]['name']);
            $this->assertEquals('Content-Disposition: form-data; name="file"; filename="test.txt"',
                $payload[3]['name']);
        });
    }

    #[Test]
    public function reports_the_error_discord_responded_with()
    {
        $discord = $this->webHookAnswering(new Response(400, [], '{"message": "Cannot send an empty message", "code": 50006}'));

        try
        {
            $discord->send(Message::make()->content('Rejected'));
            $this->fail('No exception was thrown for a 400 response');
        }
        catch (MessageCouldNotBeSent $e)
        {
            $this->assertEquals(
                'Discord web hook responded with an error (400): {"message": "Cannot send an empty message", "code": 50006}',
                $e->getMessage()
            );
        }
    }

    #[Test]
    public function reports_a_failed_communication_with_discord()
    {
        $discord = $this->webHookAnswering(new ConnectException('Connection refused', new Request('POST', 'http://example.com')));

        try
        {
            $discord->send(Message::make()->content('Unreachable'));
            $this->fail('No exception was thrown for a connection failure');
        }
        catch (MessageCouldNotBeSent $e)
        {
            $this->assertEquals('The communication with Discord Web hook failed.', $e->getMessage());
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    /** @param \Psr\Http\Message\ResponseInterface|\Throwable $answer */
    private function webHookAnswering($answer): GuzzleWebHook
    {
        $httpClient = new Client(['handler' => HandlerStack::create(new MockHandler([$answer]))]);

        return new GuzzleWebHook($httpClient, 'http://example.com');
    }
}
