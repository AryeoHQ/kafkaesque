# Kafkaesque

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aryeohq/kafkaesque.svg?style=flat-square)](https://packagist.org/packages/aryeohq/kafkaesque)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aryeohq/kafkaesque/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aryeohq/kafkaesque/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/aryeohq/kafkaesque/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/aryeohq/kafkaesque/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aryeohq/kafkaesque.svg?style=flat-square)](https://packagist.org/packages/aryeohq/kafkaesque)

**Kafkaesque** is an ergonomic Laravel package that provides a clean, object-oriented interface for producing and consuming Apache Kafka messages. Built on top of the robust [mateusjunges/laravel-kafka](https://github.com/mateusjunges/laravel-kafka) package, Kafkaesque simplifies Kafka operations with environment-aware topic management, Avro schema registry integration, and type-safe message handling.

## About the Package

Kafkaesque abstracts the complexity of Kafka message handling by providing:

- **Environment-aware Topic Management**: Automatically handle topic names across different environments (local, development, staging, production)
- **Type-safe Message Schemas**: Built on Spatie's Laravel Data for robust data validation and serialization
- **Avro Schema Registry Integration**: Seamless integration with Confluent Schema Registry for schema evolution
- **Producer/Consumer Abstractions**: Clean interfaces for both message production and consumption
- **Flexible Architecture**: Contract-based design allowing easy extension and customization

The package follows Laravel conventions and integrates seamlessly with your existing Laravel applications.

## Core Concept

Kafkaesque uses a simple flow:

**For Consuming:** `Topic->consume()` → `Consumer` → `Message->handle()`
**For Producing:** `Message->produce()` → `Topic` → `Producer`

## Installation

### Requirements

- PHP 8.3 or higher
- Laravel 10.45, 11.x, or 12.x
- ext-rdkafka extension

### Install the Package

Install via Composer:

```bash
composer require aryeo/kafkaesque
```

### Install the rdkafka Extension

The package requires the `php-rdkafka` extension. Install it using:

```bash
# On macOS with Homebrew
brew install librdkafka
pecl install rdkafka

# On Ubuntu/Debian
sudo apt-get install librdkafka-dev
pecl install rdkafka

# On CentOS/RHEL
yum install librdkafka-devel
pecl install rdkafka
```

Add the extension to your `php.ini`:

```ini
extension=rdkafka
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="kafkaesque-config"
```

## Configuration

Configure your Kafka connection in your `.env` file:

```env
KAFKA_BROKERS=localhost:9092
KAFKA_CONSUMER_GROUP_ID=your-app-group
KAFKA_SECURITY_PROTOCOL=PLAINTEXT
KAFKA_SASL_MECHANISMS=
KAFKA_SASL_USERNAME=
KAFKA_SASL_PASSWORD=

# For Avro Schema Registry (optional)
KAFKA_SCHEMA_REGISTRY_URL=http://localhost:8081
```

## Schemas

Create message schemas using Spatie's Laravel Data:

```php
<?php

namespace App\Kafka\Schemas;

use Aryeo\Kafkaesque\Schemas\KafkaesqueSchema;

class UserRegisteredSchema extends KafkaesqueSchema
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly \DateTimeInterface $registeredAt
    ) {}
}
```

For Avro schema support, implement the `IsAvroSchema` contract:

```php
use Aryeo\Kafkaesque\Schemas\Contracts\IsAvroSchema;
use Aryeo\Kafkaesque\Registries\Environments\Contracts\IsRegistryEnvironment;

class UserRegisteredSchema extends KafkaesqueSchema implements IsAvroSchema
{
    // ... constructor

    public function getSubject(): string
    {
        return 'user-registered-value';
    }

    public function getVersion(IsRegistryEnvironment $environment): int
    {
        return match ($environment->getName()) {
            'production' => 2,
            default => 1,
        };
    }
}
```

## Messages

### Producer Messages

Create messages that can be sent to topics:

```php
<?php

namespace App\Kafka\Messages;

use Aryeo\Kafkaesque\Messages\KafkaesqueMessage;
use App\Kafka\Schemas\UserRegisteredSchema;
use App\Kafka\Topics\UserEventsTopic;

class UserRegisteredMessage extends KafkaesqueMessage
{
    protected array $defaultTopics = [
        UserEventsTopic::class,
    ];

    public function __construct(
        UserRegisteredSchema $body,
        ?string $key = null
    ) {
        parent::__construct($body, $key);
    }
}
```

**Send messages:**

```php
$schema = new UserRegisteredSchema(
    userId: '12345',
    email: 'user@example.com',
    name: 'John Doe',
    registeredAt: now()
);

$message = new UserRegisteredMessage($schema, key: '12345');
$message->produce(); // Sends to configured topics via their producers
```

### Consumer Messages

Create messages that handle incoming data:

```php
<?php

namespace App\Kafka\Messages;

use Aryeo\Kafkaesque\Messages\KafkaesqueMessage;
use App\Kafka\Schemas\VersionOne;
use App\Jobs\SyncZillow3dHomeTour;

class AryeoListingAdded extends KafkaesqueMessage
{
    public function __construct(
        VersionOne $body,
        ?string $key = null
    ) {
        parent::__construct($body, $key);
    }

    public function handle(): void
    {
        if (!$this->shouldHandle()) {
            return;
        }

        SyncZillow3dHomeTour::dispatch(
            listingId: $this->body->listingId,
            vrModelId: $this->body->vrModelId
        );
    }

    protected function shouldHandle(): bool
    {
        return !is_null($this->body->listingId) && 
               !is_null($this->body->vrModelId);
    }
}
```

## Topics

Topics manage environment-aware naming and route messages between producers/consumers.

### Producible Topic

```php
<?php

namespace App\Kafka\Topics;

use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Aryeo\Kafkaesque\Topics\Contracts\IsProducible;
use App\Kafka\Producers\UserEventsProducer;

class UserEventsTopic extends KafkaesqueTopic implements IsProducible
{
    public function getProducer(): KafkaesqueProducer
    {
        return resolve(UserEventsProducer::class);
    }

    protected function getLocalName(): string
    {
        return 'local.user-events';
    }

    protected function getDevelopmentName(): string
    {
        return 'dev.user-events';
    }

    protected function getStagingName(): string
    {
        return 'staging.user-events';
    }

    protected function getProductionName(): string
    {
        return 'user-events';
    }

    protected function getTestingName(): string
    {
        return 'test.user-events';
    }
}
```

### Consumable Topic

```php
<?php

namespace App\Kafka\Topics;

use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Aryeo\Kafkaesque\Topics\Contracts\IsConsumable;
use App\Kafka\Consumers\StreamzConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

class SparkleTopic extends KafkaesqueTopic implements IsConsumable
{
    public function getConsumer(): KafkaesqueConsumer
    {
        return resolve(StreamzConsumer::class);
    }

    // ... environment name methods

    public function handleMessage(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = VersionOne::from($message->getBody());

        // Route to specific message handlers
        $messageClass = match ($body->phase) {
            SparklePhase::AryeoListingAdded => AryeoListingAdded::class,
            SparklePhase::AryeoListingDeleted => AryeoListingDeleted::class,
            default => null,
        };

        if ($messageClass) {
            resolve($messageClass, [
                'body' => $body,
                'key' => $message->getKey(),
            ])->handle();
        }
    }
}
```

**Consume messages:**

```php
// In an Artisan command
resolve(SparkleTopic::class)->consume(); // Uses configured consumer to process messages
```

## Producers

Create producers by extending `KafkaesqueProducer`. For detailed configuration options, see the [mateusjunges/laravel-kafka documentation](https://github.com/mateusjunges/laravel-kafka).

```php
<?php

namespace App\Kafka\Producers;

use Aryeo\Kafkaesque\Producers\KafkaesqueProducer;
use Junges\Kafka\Facades\Kafka;

class UserEventsProducer extends KafkaesqueProducer
{
    public function __construct()
    {
        $this->producer = Kafka::producer()
            ->withBrokers(config('kafka.brokers'))
            ->withSasl(
                username: config('kafka.username'),
                password: config('kafka.password'),
                mechanisms: 'SCRAM-SHA-256',
                securityProtocol: 'sasl_ssl',
            );
    }
}
```

## Consumers

Create consumers by extending `KafkaesqueConsumer`. For detailed configuration options, see the [mateusjunges/laravel-kafka documentation](https://github.com/mateusjunges/laravel-kafka).

```php
<?php

namespace App\Kafka\Consumers;

use Aryeo\Kafkaesque\Consumers\KafkaesqueConsumer;
use Junges\Kafka\Facades\Kafka;

class StreamzConsumer extends KafkaesqueConsumer
{
    public function __construct()
    {
        $this->consumerBuilder = Kafka::consumer()
            ->withBrokers(config('kafka.brokers'))
            ->withSasl(
                username: config('kafka.username'),
                password: config('kafka.password'),
                mechanisms: 'SCRAM-SHA-256',
                securityProtocol: 'sasl_ssl',
            )
            ->withConsumerGroupId(config('kafka.consumer_group_id'));
    }
}
```

## Avro Registry Setup

When using Avro schemas, create a registry environment:

```php
<?php

namespace App\Kafka\Registries\Environments;

use Aryeo\Kafkaesque\Registries\Environments\Contracts\IsAvroRegistryEnvironment;

class ProductionRegistryEnvironment implements IsAvroRegistryEnvironment
{
    public function getBaseUri(): string
    {
        return config('kafka.schema_registry_url');
    }

    public function getName(): string
    {
        return app()->environment();
    }
}
```

Then implement `HasAvroRegistry` on your topic:

```php
use Aryeo\Kafkaesque\Topics\Contracts\HasAvroRegistry;
use Aryeo\Kafkaesque\Registries\AvroRegistry;

class UserEventsTopic extends KafkaesqueTopic implements IsProducible, HasAvroRegistry
{
    public function getRegistry(): KafkaesqueRegistry
    {
        return new AvroRegistry(
            environment: new ProductionRegistryEnvironment()
        );
    }

    // ... other methods
}
```

## Running Consumers

Create Artisan commands to run your consumers:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Topics\SparkleTopic;

class ConsumeSparkeEvents extends Command
{
    protected $signature = 'kafka:consume-sparkle-events';

    public function handle(): void
    {
        resolve(SparkleTopic::class)->consume();
    }
}
```

## Testing

```php
<?php

namespace Tests\Feature\Kafka;

use Tests\TestCase;
use App\Kafka\Messages\UserRegisteredMessage;
use App\Kafka\Schemas\UserRegisteredSchema;

class UserRegisteredMessageTest extends TestCase
{
    public function test_message_creation(): void
    {
        $schema = new UserRegisteredSchema(
            userId: '12345',
            email: 'test@example.com',
            name: 'Test User',
            registeredAt: now()
        );

        $message = new UserRegisteredMessage($schema, key: '12345');

        $this->assertEquals('12345', $message->getKey());
        $this->assertEquals('test@example.com', $message->getBody()->email);
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Deven Jahnke](https://github.com/devenjahnke)
- [Cory Rosenwald](https://github.com/coryrose1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
