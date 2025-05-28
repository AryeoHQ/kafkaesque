<?php

namespace Aryeo\Kafkaesque\Messages\Contracts;

interface IsConsumable
{
    public function handle(): void;
}
