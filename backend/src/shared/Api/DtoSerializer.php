<?php

namespace App\shared\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DtoSerializer implements SerializerInterface
{
    private SerializerInterface $serializer;

    public function __construct()
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $this->serializer = new Serializer(
            [
                new ObjectNormalizer($classMetadataFactory),
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

    public function json(array $data, ?string $message = null, int $code = Response::HTTP_OK, array $context = []): JsonResponse
    {
        if (null !== $message) {
            $data = [...['message' => $message], ...$data];
        }

        return new JsonResponse(
            data: $this->serialize(array_merge(['status' => true], $data), JsonEncoder::FORMAT, array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ], $context)),
            status: $code,
            json: true,
        );
    }

    public function jsonNotNull(array $data, ?string $message = null, int $code = Response::HTTP_OK, array $context = []): JsonResponse
    {
        return $this->json($data, $message, $code, array_merge([AbstractObjectNormalizer::SKIP_NULL_VALUES => true], $context));
    }
}
