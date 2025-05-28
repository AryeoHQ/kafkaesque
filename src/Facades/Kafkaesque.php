<?php

namespace Aryeo\Kafkaesque\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aryeo\Kafkaesque\Kafkaesque
 */
class Kafkaesque extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aryeo\Kafkaesque\Kafkaesque::class;
    }
}
