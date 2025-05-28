<?php

namespace Aryeo\Kafkaesque\Producers;

use Aryeo\Kafkaesque\Messages\KafkaesqueMessage;
use Aryeo\Kafkaesque\Registries\AvroRegistry;
use Aryeo\Kafkaesque\Schemas\Contracts\IsAvroSchema;
use Aryeo\Kafkaesque\Topics\Contracts\HasAvroRegistry;
use Aryeo\Kafkaesque\Topics\Contracts\IsProducible;
use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Exception;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Message\Message;

abstract class KafkaesqueProducer
{
    protected MessageProducer $producer;

    /**
     * @throws Exception
     */
    public function produce(
        KafkaesqueTopic&IsProducible $topic,
        KafkaesqueMessage $message
    ): void {
        if ($topic instanceof HasAvroRegistry) {
            $registry = $topic->getRegistry();
            $schema = $message->getBody();

            if ($schema instanceof IsAvroSchema) {
                $registry->registerSchema(
                    topic: $topic,
                    schema: $schema
                );
            }

            $this->producer
                ->usingSerializer(
                    serializer: $registry->getSerializer()
                );
        }

        $this->producer
            ->withMessage(
                message: new Message(
                    topicName: $topic->getName(),
                    body: $message->getBody()->toArray(),
                    key: $message->getKey()
                )
            )
            ->send();
    }
}
