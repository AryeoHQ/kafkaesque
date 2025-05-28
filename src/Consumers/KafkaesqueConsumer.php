<?php

namespace Aryeo\Kafkaesque\Consumers;

use Aryeo\Kafkaesque\Topics\Contracts\HasAvroRegistry;
use Aryeo\Kafkaesque\Topics\Contracts\IsConsumable;
use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Junges\Kafka\Consumers\Builder;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

abstract class KafkaesqueConsumer
{
    protected Builder $consumerBuilder;

    public function consume(KafkaesqueTopic&IsConsumable $topic): void
    {
        $consumer = $this->consumerBuilder
            ->subscribe($topic->getName())
            ->withHandler(
                function (ConsumerMessage $message, MessageConsumer $consumer) use ($topic) {
                    $topic->handleMessage($message, $consumer);
                }
            );

        if ($topic instanceof HasAvroRegistry) {
            $consumer->usingDeserializer(
                $topic->getRegistry()->getDeserializer(),
            );
        }

        $consumer->build()->consume();
    }
}
