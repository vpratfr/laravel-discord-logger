<?php


namespace MarvinLabs\DiscordLogger\Tests\Support;


use function is_array;
use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use MarvinLabs\DiscordLogger\Discord\Message;
use PHPUnit\Framework\Assert;

class FakeDiscordWebHook implements DiscordWebHook
{
    /** @var string */
    private $url;

    /** @var array */
    private $sentMessages = [];

    /** @var \MarvinLabs\DiscordLogger\Discord\Message|null */
    private $lastMessageSent;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function send(Message $message): void
    {
        $this->sentMessages[] = $message;
        $this->lastMessageSent = $message;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLastMessageSent(): ?Message
    {
        return $this->lastMessageSent;
    }

    public function getSendCount(): int
    {
        return count($this->sentMessages);
    }

    public function assertNothingSent()
    {
        Assert::assertCount(0, $this->sentMessages);
    }

    public function assertSendCount(int $expected)
    {
        Assert::assertCount($expected, $this->sentMessages);
    }

    public function assertLastMessage(Message $expected)
    {
        if ($this->lastMessageSent === null)
        {
            Assert::fail('Expecting a last message but none has been sent yet');
        }

        Assert::assertEquals($expected->toArray(), $this->lastMessageSent->toArray());
    }

    public function assertLastMessageMatches(array $expectedSubset)
    {
        if ($this->lastMessageSent === null)
        {
            Assert::fail('Expecting a last message but none has been sent yet');
        }

        $this->assertArraySubset($this->lastMessageSent->toArray(), $expectedSubset);
    }

    protected function assertArraySubset(array $actualArray, array $expectedSubset)
    {
        foreach ($expectedSubset as $key => $value)
        {
            if (is_array($value))
            {
                $this->assertArraySubset($actualArray[$key], $value);
                continue;
            }

            Assert::assertArrayHasKey($key, $actualArray);
            Assert::assertSame($value, $actualArray[$key]);
        }
    }
}
