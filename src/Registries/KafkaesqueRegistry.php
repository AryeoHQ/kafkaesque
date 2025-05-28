<?php

namespace Aryeo\Kafkaesque\Registries;

use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\MessageSerializer;

abstract class KafkaesqueRegistry
{
    abstract public function getDeserializer(): MessageDeserializer;

    abstract public function getSerializer(): MessageSerializer;
}
