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

The package uses Laravel's environment configuration. Configure your Kafka connection in your `.env` file:

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

## Creating Messages

Messages in Kafkaesque extend the `KafkaesqueMessage` class and use schemas for type safety.

### 1. Create a Schema

First, create a schema by extending `KafkaesqueSchema`:

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

### 2. Create a Message

Create a message class that uses your schema:

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

### 3. Using Avro Schemas (Optional)

For Avro schema support, implement the `IsAvroSchema` contract:

```php
<?php

namespace App\Kafka\Schemas;

use Aryeo\Kafkaesque\Schemas\KafkaesqueSchema;
use Aryeo\Kafkaesque\Schemas\Contracts\IsAvroSchema;
use Aryeo\Kafkaesque\Registries\Environments\Contracts\IsRegistryEnvironment;

class UserRegisteredSchema extends KafkaesqueSchema implements IsAvroSchema
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly \DateTimeInterface $registeredAt
    ) {}

    public function getSubject(): string
    {
        return 'user-registered-value';
    }

    public function getVersion(IsRegistryEnvironment $environment): int
    {
        return match ($environment->getName()) {
            'production' => 2,
            'staging' => 2,
            default => 1,
        };
    }
}
```

## Kafka Message Production

### 1. Create a Topic

Topics in Kafkaesque implement environment-aware naming and can be marked as producible:

```php
<?php

namespace App\Kafka\Topics;

use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Aryeo\Kafkaesque\Topics\Contracts\IsProducible;
use Aryeo\Kafkaesque\Producers\KafkaesqueProducer;
use App\Kafka\Producers\UserEventsProducer;

class UserEventsTopic extends KafkaesqueTopic implements IsProducible
{
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

    public function getProducer(): KafkaesqueProducer
    {
        return resolve(UserEventsProducer::class);
    }
}
```

### 2. Create a Producer

Extend `KafkaesqueProducer` and configure it with the underlying Kafka producer:

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
            ->withConfigOptions([
                'bootstrap.servers' => config('kafka.brokers'),
                'security.protocol' => config('kafka.security_protocol'),
            ]);
    }
}
```

### 3. Producing Messages

Produce messages using the fluent interface:

```php
use App\Kafka\Messages\UserRegisteredMessage;
use App\Kafka\Schemas\UserRegisteredSchema;

// Create and produce a message
$schema = new UserRegisteredSchema(
    userId: '12345',
    email: 'user@example.com',
    name: 'John Doe',
    registeredAt: now()
);

$message = new UserRegisteredMessage($schema, key: '12345');

// Produce to default topics
$message->produce();

// Or produce to specific topics
$message->onTopics([
    UserEventsTopic::class,
    AnotherTopic::class,
])->produce();
```

### 4. Avro Registry Integration

For topics using Avro schemas, implement the `HasAvroRegistry` contract:

```php
<?php

namespace App\Kafka\Topics;

use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Aryeo\Kafkaesque\Topics\Contracts\IsProducible;
use Aryeo\Kafkaesque\Topics\Contracts\HasAvroRegistry;
use Aryeo\Kafkaesque\Registries\KafkaesqueRegistry;
use App\Kafka\Registries\ProductionAvroRegistry;

class UserEventsTopic extends KafkaesqueTopic implements IsProducible, HasAvroRegistry
{
    // ... topic name methods ...

    public function getRegistry(): KafkaesqueRegistry
    {
        return resolve(ProductionAvroRegistry::class);
    }

    public function getProducer(): KafkaesqueProducer
    {
        return resolve(UserEventsProducer::class);
    }
}
```

## Kafka Message Consumption

### 1. Create a Consumable Topic

Mark your topic as consumable and implement the message handling logic:

```php
<?php

namespace App\Kafka\Topics;

use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use Aryeo\Kafkaesque\Topics\Contracts\IsConsumable;
use Aryeo\Kafkaesque\Consumers\KafkaesqueConsumer;
use App\Kafka\Consumers\UserEventsConsumer;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;

class UserEventsTopic extends KafkaesqueTopic implements IsConsumable
{
    // ... topic name methods ...

    public function getConsumer(): KafkaesqueConsumer
    {
        return resolve(UserEventsConsumer::class);
    }

    public function handleMessage(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $messageBody = $message->getBody();
        $messageKey = $message->getKey();
        $headers = $message->getHeaders();
        
        // Process the message
        logger()->info('Received user event', [
            'key' => $messageKey,
            'body' => $messageBody,
            'headers' => $headers,
        ]);

        // Handle different message types
        match ($messageBody['eventType'] ?? null) {
            'user.registered' => $this->handleUserRegistered($messageBody),
            'user.updated' => $this->handleUserUpdated($messageBody),
            default => logger()->warning('Unknown event type', $messageBody),
        };
    }

    private function handleUserRegistered(array $data): void
    {
        // Process user registration
        User::create([
            'external_id' => $data['userId'],
            'email' => $data['email'],
            'name' => $data['name'],
        ]);
    }

    private function handleUserUpdated(array $data): void
    {
        // Process user update
        User::where('external_id', $data['userId'])
            ->update([
                'email' => $data['email'],
                'name' => $data['name'],
            ]);
    }
}
```

### 2. Create a Consumer

Extend `KafkaesqueConsumer` and configure it:

```php
<?php

namespace App\Kafka\Consumers;

use Aryeo\Kafkaesque\Consumers\KafkaesqueConsumer;
use Junges\Kafka\Facades\Kafka;

class UserEventsConsumer extends KafkaesqueConsumer
{
    public function __construct()
    {
        $this->consumerBuilder = Kafka::consumer()
            ->withConsumerGroupId(config('kafka.consumer_group_id'))
            ->withConfigOptions([
                'bootstrap.servers' => config('kafka.brokers'),
                'security.protocol' => config('kafka.security_protocol'),
                'auto.offset.reset' => 'earliest',
            ]);
    }
}
```

### 3. Consuming Messages

Create an Artisan command to consume messages:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Kafka\Topics\UserEventsTopic;

class ConsumeUserEvents extends Command
{
    protected $signature = 'kafka:consume-user-events';
    protected $description = 'Consume user events from Kafka';

    public function handle(): void
    {
        $this->info('Starting to consume user events...');
        
        $topic = resolve(UserEventsTopic::class);
        $topic->consume();
    }
}
```

Register the command and run it:

```bash
php artisan kafka:consume-user-events
```

### 4. Error Handling

Implement robust error handling in your message handlers:

```php
public function handleMessage(ConsumerMessage $message, MessageConsumer $consumer): void
{
    try {
        $messageBody = $message->getBody();
        
        // Validate message structure
        if (!isset($messageBody['eventType'])) {
            throw new InvalidArgumentException('Missing eventType in message');
        }

        // Process message
        $this->processMessage($messageBody);
        
        // Acknowledge successful processing
        $consumer->acknowledge($message);
        
    } catch (\Exception $e) {
        logger()->error('Failed to process message', [
            'error' => $e->getMessage(),
            'message' => $message->getBody(),
        ]);
        
        // Handle error (retry logic, dead letter queue, etc.)
        $this->handleError($message, $e, $consumer);
    }
}
```

## Advanced Usage

### Custom Registry Environments

Create custom registry environments for different deployment scenarios:

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
        return 'production';
    }
}
```

### Batch Message Production

Produce multiple messages efficiently:

```php
$messages = collect($users)->map(function ($user) {
    $schema = new UserRegisteredSchema(
        userId: $user->id,
        email: $user->email,
        name: $user->name,
        registeredAt: $user->created_at
    );
    
    return new UserRegisteredMessage($schema, key: $user->id);
});

$messages->each->produce();
```

### Testing

Test your Kafka implementations using Laravel's testing utilities:

```php
<?php

namespace Tests\Feature\Kafka;

use Tests\TestCase;
use App\Kafka\Messages\UserRegisteredMessage;
use App\Kafka\Schemas\UserRegisteredSchema;

class UserRegisteredMessageTest extends TestCase
{
    public function test_user_registered_message_creation(): void
    {
        $schema = new UserRegisteredSchema(
            userId: '12345',
            email: 'test@example.com',
            name: 'Test User',
            registeredAt: now()
        );

        $message = new UserRegisteredMessage($schema, key: '12345');

        $this->assertEquals('12345', $message->getKey());
        $this->assertInstanceOf(UserRegisteredSchema::class, $message->getBody());
        $this->assertEquals('test@example.com', $message->getBody()->email);
    }
}
```

## Testing

Run the package tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Fix code style issues:

```bash
composer format
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
