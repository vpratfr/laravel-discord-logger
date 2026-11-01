<?php

namespace MarvinLabs\DiscordLogger;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use MarvinLabs\DiscordLogger\Contracts\DiscordWebHook;
use MarvinLabs\DiscordLogger\Contracts\RecordToMessage;
use MarvinLabs\DiscordLogger\Converters\SimpleRecordConverter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;
use RuntimeException;
use Throwable;
use function class_implements;

class LogHandler extends AbstractProcessingHandler
{
    /** @var \MarvinLabs\DiscordLogger\Contracts\DiscordWebHook */
    private $discord;

    /** @var \MarvinLabs\DiscordLogger\Contracts\RecordToMessage */
    private $recordToMessage;

    /** @var boolean */
    private $ignoreExceptions;

    /** @var \Illuminate\Contracts\Cache\Repository|null */
    private $throttleCache;

    /** @var int */
    private $throttleSeconds;

    /** @throws \Illuminate\Contracts\Container\BindingResolutionException */
    public function __construct(Container $container, Repository $config, array $channelConfig)
    {
        parent::__construct(Monolog::toMonologLevel($channelConfig['level'] ?? Monolog::DEBUG));

        $this->discord = $container->make(DiscordWebHook::class, ['url' => $channelConfig['url']]);
        $this->recordToMessage = $this->createRecordConverter($container, $config);

        $this->ignoreExceptions = $channelConfig['ignore_exceptions'] ?? false;

        $this->throttleSeconds = (int) ($channelConfig['throttle_seconds'] ?? 3600);
        if (($channelConfig['throttle'] ?? false) && $this->throttleSeconds > 0) {
            $this->throttleCache = $container->make(CacheFactory::class)
                ->store($channelConfig['throttle_cache_store'] ?? null);
        }
    }

    public function write(array|LogRecord $record): void
    {
        if ($record instanceof LogRecord) {
            $record =  $record->toArray();
        }

        if ($this->isThrottled($record)) {
            return;
        }

        foreach($this->recordToMessage->buildMessages($record) as $message) {
            try {
                $this->discord->send($message);
            } catch (\Exception $e) {
                if (!$this->ignoreExceptions) {
                    throw $e;
                }
            }
        }
    }

    protected function isThrottled(array $record): bool
    {
        if ($this->throttleCache === null) {
            return false;
        }

        try {
            return !$this->throttleCache->add($this->throttleKey($record), true, $this->throttleSeconds);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function throttleKey(array $record): string
    {
        $exception = $record['context']['exception'] ?? null;

        $fingerprint = $exception instanceof Throwable
            ? [get_class($exception), $exception->getFile(), $exception->getLine()]
            : [$record['level_name'] ?? $record['level'] ?? '', $record['message'] ?? ''];

        return 'discord-logger:throttle:' . md5(implode('|', $fingerprint));
    }

    /** @throws \Illuminate\Contracts\Container\BindingResolutionException */
    protected function createRecordConverter(Container $container, Repository $config): RecordToMessage
    {
        $converter = $container->make(
            $config->get('discord-logger.converter', SimpleRecordConverter::class));

        if (!class_implements($converter, RecordToMessage::class))
        {
            throw new RuntimeException('The converter specified in the discord-logger configuration should implement the RecordToMessage interface');
        }

        return $converter;
    }

}
