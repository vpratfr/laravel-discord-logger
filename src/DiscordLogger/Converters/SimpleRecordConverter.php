<?php

namespace MarvinLabs\DiscordLogger\Converters;

use MarvinLabs\DiscordLogger\Discord\Exceptions\ConfigurationIssue;
use MarvinLabs\DiscordLogger\Discord\Message;

class SimpleRecordConverter extends AbstractRecordConverter
{
    /**
     * @throws \MarvinLabs\DiscordLogger\Discord\Exceptions\ConfigurationIssue
     */
    public function buildMessages(array $record): array
    {
        $message = Message::make();

        $this->addGenericMessageFrom($message);
        $this->addMessageContent($message, $record);
        $this->addMessageStacktrace($message, $record);

        return [$message];
    }

    protected function addMessageContent(Message $message, array $record): void
    {
        $content = $record['message'] ?? '';
        $emoji = $this->getRecordEmoji($record);

        $message->content($emoji === null ? "`$content`" : "$emoji `$content`");
    }

    /**
     * @throws \MarvinLabs\DiscordLogger\Discord\Exceptions\ConfigurationIssue
     */
    protected function addMessageStacktrace(Message $message, array $record): void
    {
        $stacktrace = $this->getStacktrace($record);
        if ($stacktrace === null)
        {
            return;
        }

        switch ($this->stackTraceMode($stacktrace))
        {
            case 'file':
                $message->file($stacktrace, $this->getStacktraceFilename($record));
                break;

            case 'inline' :
                $message->content($message->content . "\n\n`" . $stacktrace . '`');
                break;

            default:
                throw new ConfigurationIssue('Invalid value for configuration `discord-logger.stacktrace`');
        }
    }

//    protected function mainMessageEmbed(array $record): LogHandler
//    {
//        $message = Str::limit($record['message'], 2000);
//
//        $timestamp = $record['datetime']->format('Y-m-d H:i:s');
//
//        $this->currentMessage->embed(Embed::make()
//            ->title("`[$timestamp] {$record['channel']}.{$record['level_name']}`")
//            ->description("`{$message}`")
//            ->color($this->getRecordColor($record)));
//
//        return $this;
//    }
//
//    protected function exceptionEmbed(array $record): LogHandler
//    {
//        if (empty($record['context'])
//            || empty($record['context']['exception'])
//            || !is_a($record['context']['exception'], Throwable::class))
//        {
//            return $this;
//        }
//
//        /** @var \Throwable $exception */
//        $exception = $record['context']['exception'];
//
//        $traceAsString = Str::limit($exception->getTraceAsString(), 2000);
//
//        $this->currentMessage->embed(Embed::make()
//            ->description("`$traceAsString`")
//            ->color($this->getRecordColor($record)));
//
//        return $this;
//    }
}
