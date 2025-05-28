<?php

namespace Aryeo\Kafkaesque\Registries\Deserializers;

use Aryeo\Kafkaesque\Registries\AvroRegistry;
use Exception;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Junges\Kafka\Contracts\AvroMessageDeserializer;
use Junges\Kafka\Contracts\AvroSchemaRegistry;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\ConsumedMessage;

readonly class AvroDeserializer implements AvroMessageDeserializer
{
    public function __construct(
        private AvroRegistry $registry,
        private RecordSerializer $recordSerializer
    ) {}

    public function getRegistry(): AvroSchemaRegistry
    {
        return $this->registry->getRegistry();
    }

    public function deserialize(ConsumerMessage $message): ConsumerMessage
    {
        return new ConsumedMessage(
            topicName: $message->getTopicName(),
            partition: $message->getPartition(),
            headers: $message->getHeaders(),
            body: $this->decodeBody($message),
            key: $message->getKey(),
            offset: $message->getOffset(),
            timestamp: $message->getTimestamp()
        );
    }

    protected function decodeBody(ConsumerMessage $message): mixed
    {
        $magicByte = ord($message->getBody()[0]);

        if ($magicByte !== 0) {
            throw new Exception('Unknown magic byte!');
        }

        $schemaId = unpack('N', substr($message->getBody(), 1, 4))[1];

        $schema = $this->registry->getSchemaForId($schemaId);

        $data = $this->recordSerializer
            ->decodeMessage(
                binaryMessage: $message->getBody(),
                readersSchema: $schema
            );

        return $data;
    }
}
