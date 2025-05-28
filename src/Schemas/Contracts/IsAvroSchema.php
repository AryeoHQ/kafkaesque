<?php

namespace Aryeo\Kafkaesque\Schemas\Contracts;

use Aryeo\Kafkaesque\Registries\Environments\Contracts\IsRegistryEnvironment;

interface IsAvroSchema
{
    public function getVersion(IsRegistryEnvironment $environment): int;

    public function getSubject(): string;
}
