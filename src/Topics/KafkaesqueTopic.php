<?php

namespace Aryeo\Kafkaesque\Topics;

use Aryeo\Kafkaesque\Messages\KafkaesqueMessage;
use Aryeo\Kafkaesque\Topics\Contracts\IsConsumable;
use Aryeo\Kafkaesque\Topics\Contracts\IsProducible;
use Exception;

abstract class KafkaesqueTopic
{
    /**
     * @throws Exception
     */
    public function getName(): string
    {
        return match (app()->environment()) {
            'development' => $this->getDevelopmentName(),
            'local' => $this->getLocalName(),
            'production' => $this->getProductionName(),
            'staging' => $this->getStagingName(),
            'testing' => $this->getTestingName(),
            default => throw new Exception('Unhandled application environment')
        };
    }

    abstract protected function getDevelopmentName(): string;

    abstract protected function getLocalName(): string;

    abstract protected function getProductionName(): string;

    abstract protected function getStagingName(): string;

    abstract protected function getTestingName(): string;

    /**
     * @throws Exception
     */
    public function produce(KafkaesqueMessage $message): void
    {
        if (! $this instanceof IsProducible) {
            throw new Exception;
        }

        $this->getProducer()
            ->produce(
                topic: $this,
                message: $message
            );
    }

    /**
     * @throws Exception
     */
    public function consume(): void
    {
        if (! $this instanceof IsConsumable) {
            throw new Exception('Not consumable');
        }

        $this->getConsumer()
            ->consume(
                topic: $this,
            );

    }
}
