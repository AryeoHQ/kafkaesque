<?php

namespace Aryeo\Kafkaesque;

use Aryeo\Kafkaesque\Commands\KafkaesqueCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class KafkaesqueServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('kafkaesque')
            ->hasConfigFile();
    }
}
