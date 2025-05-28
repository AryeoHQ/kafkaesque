<?php

namespace Aryeo\Kafkaesque\Topics\Contracts;

use Aryeo\Kafkaesque\Registries\AvroRegistry;

interface HasAvroRegistry
{
    public function getRegistry(): AvroRegistry;
}
