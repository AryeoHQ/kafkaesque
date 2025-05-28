<?php

namespace Aryeo\Kafkaesque\Messages;

use Aryeo\Kafkaesque\Schemas\KafkaesqueSchema;
use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;

abstract class KafkaesqueMessage
{
    /** @var class-string[] */
    protected array $defaultTopics;

    public function __construct(
        protected readonly string $key,
        protected readonly KafkaesqueSchema $body,
    ) {}

    public function getBody(): KafkaesqueSchema
    {
        return $this->body;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function onTopics(array $topics): self
    {
        $this->defaultTopics = $topics;

        return $this;
    }

    public function produce(): void
    {
        collect($this->defaultTopics)
            ->map(fn (string $topic) => resolve($topic))
            ->each(fn (KafkaesqueTopic $topic) => $topic->produce(
                message: $this
            ));
    }
}
