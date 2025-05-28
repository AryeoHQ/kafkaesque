<?php

namespace Aryeo\Kafkaesque;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Aryeo\Kafkaesque\Commands\KafkaesqueCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_kafkaesque_table')
            ->hasCommand(KafkaesqueCommand::class);
    }
}
