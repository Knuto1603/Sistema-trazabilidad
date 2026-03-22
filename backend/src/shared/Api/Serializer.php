<?php

namespace App\shared\Api;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Symfony\Component\Serializer\SerializerInterface;

final class Serializer implements SerializerInterface
{
    private SerializerInterface $serializer;

    public function __construct()
    {
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            }, DateTimeNormalizer::FORMAT_KEY => 'd-m-yy',
        ];

        $this->serializer = new SymfonySerializer(
            [
                new ObjectNormalizer(
                    propertyTypeExtractor: new ReflectionExtractor(),
                    defaultContext: $defaultContext
                ),
                new DateTimeNormalizer(),
                new ArrayDenormalizer(),
            ],
            [
                new JsonEncoder(),
            ]
        );
    }

    public function serialize(mixed $data, string $format = JsonEncoder::FORMAT, array $context = []): string
    {
        return $this->serializer->serialize($data, $format, $context);
    }

    public function deserialize(mixed $data, string $type, string $format = JsonEncoder::FORMAT, array $context = []): mixed
    {
        return $this->serializer->deserialize($data, $type, $format, $context);
    }
}
