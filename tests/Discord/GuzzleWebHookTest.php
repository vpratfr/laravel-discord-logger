<?php

namespace MarvinLabs\DiscordLogger\Tests\Discord;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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

    #[Test]
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

    #[Test]
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
}
