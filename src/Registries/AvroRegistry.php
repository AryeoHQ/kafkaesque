<?php

namespace Aryeo\Kafkaesque\Registries;

use Aryeo\Kafkaesque\Registries\Deserializers\AvroDeserializer;
use Aryeo\Kafkaesque\Registries\Environments\Contracts\IsAvroRegistryEnvironment;
use Aryeo\Kafkaesque\Schemas\Contracts\IsAvroSchema;
use Aryeo\Kafkaesque\Schemas\KafkaesqueSchema;
use Aryeo\Kafkaesque\Topics\Contracts\HasAvroRegistry;
use Aryeo\Kafkaesque\Topics\KafkaesqueTopic;
use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use GuzzleHttp\Client;
use Junges\Kafka\Contracts\MessageDeserializer;
use Junges\Kafka\Contracts\MessageSerializer;
use Junges\Kafka\Message\KafkaAvroSchema;
use Junges\Kafka\Message\Registry\AvroSchemaRegistry;
use Junges\Kafka\Message\Serializers\AvroSerializer;

class AvroRegistry extends KafkaesqueRegistry
{
    protected CachedRegistry $cachedRegistry;
    protected RecordSerializer $recordSerializer;
    protected AvroDeserializer $deserializer;
    protected AvroSchemaRegistry $registry;
    protected AvroSerializer $serializer;

    public function __construct(
        protected readonly IsAvroRegistryEnvironment $environment
    ) {
        $this->cachedRegistry = new CachedRegistry(
            registry: new BlockingRegistry(
                registry: new PromisingRegistry(
                    client: new Client([
                        'base_uri' => $this->environment->getBaseUri(),
                    ])
                )
            ),
            cacheAdapter: new AvroObjectCacheAdapter()
        );

        $this->registry = new AvroSchemaRegistry(
            registry: $this->cachedRegistry
        );

        $this->recordSerializer = new RecordSerializer($this->cachedRegistry);

        $this->serializer = new AvroSerializer(
            registry: $this->registry,
            recordSerializer: $this->recordSerializer,
        );

        $this->deserializer = new AvroDeserializer(
            registry: $this,
            recordSerializer: $this->recordSerializer,
        );
    }

    public function getDeserializer(): MessageDeserializer
    {
        return $this->deserializer;
    }

    public function getRegistry(): AvroSchemaRegistry
    {
        return $this->registry;
    }

    public function getSchemaForId(int $id): ?AvroSchema
    {
        return $this->cachedRegistry->schemaForId($id);
    }

    public function getSerializer(): MessageSerializer
    {
        return $this->serializer;
    }

    public function registerSchema(
        KafkaesqueTopic&HasAvroRegistry $topic,
        KafkaesqueSchema&IsAvroSchema $schema
    ): void {
        $this->registry
            ->addBodySchemaMappingForTopic(
                topicName: $topic->getName(),
                avroSchema: new KafkaAvroSchema(
                    schemaName: $schema->getSubject(),
                    version: $schema->getVersion(
                        environment: $this->environment
                    )
                )
            );
    }
}
