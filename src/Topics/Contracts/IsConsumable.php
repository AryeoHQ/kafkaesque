<?php

namespace Aryeo\Kafkaesque\Topics\Contracts;

use Aryeo\Kafkaesque\Consumers\KafkaesqueConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

interface IsConsumable
{
    public function getConsumer(): KafkaesqueConsumer;

    public function handleMessage(ConsumerMessage $message, MessageConsumer $consumer): void;
}
