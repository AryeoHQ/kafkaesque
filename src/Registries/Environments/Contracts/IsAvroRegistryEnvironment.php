<?php

namespace Aryeo\Kafkaesque\Registries\Environments\Contracts;

interface IsAvroRegistryEnvironment extends IsRegistryEnvironment
{
    public function getBaseUri(): string;
}
