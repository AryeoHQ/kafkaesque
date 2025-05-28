<?php

namespace Aryeo\Kafkaesque\Topics\Contracts;

use Aryeo\Kafkaesque\Producers\KafkaesqueProducer;

interface IsProducible
{
    public function getProducer(): KafkaesqueProducer;
}
