<?php

namespace Aryeo\Kafkaesque\Commands;

use Illuminate\Console\Command;

class KafkaesqueCommand extends Command
{
    public $signature = 'kafkaesque';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
