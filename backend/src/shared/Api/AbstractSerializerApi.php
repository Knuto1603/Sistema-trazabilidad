<?php

namespace App\shared\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractSerializerApi extends AbstractApi
{
    protected ?SerializerInterface $serializer = null;
    protected bool $nullable = false;

    protected function response(array $params, int $code, array $headers): JsonResponse
    {
        $context = ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS];
        if (!$this->nullable) {
            $context = [...$context, ...[AbstractObjectNormalizer::SKIP_NULL_VALUES => true]];
        }

        return new JsonResponse(
            data: $this->serializer()->serialize(
                $params,
                JsonEncoder::FORMAT,
                $context,
            ),
            status: $code,
            json: true,
        );
    }

    protected function serializer(): SerializerInterface
    {
        if (null === $this->serializer) {
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
        return $this->serializer;
    }

    protected function visibleNull(): void
    {
        $this->nullable = true;
    }
}
