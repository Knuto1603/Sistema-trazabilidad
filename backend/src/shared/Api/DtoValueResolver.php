<?php

namespace App\shared\Api;

use App\Shared\Service\Dto\DtoRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class DtoValueResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $argumentType = $argument->getType();

        if (!$argumentType || !is_subclass_of($argumentType, DtoRequestInterface::class)) {
            return [];
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            $argumentType,
            'json',
        // $request->getContext()
        );

        $this->validate($dto);

        return [$dto];
    }

    public function validate(mixed $dto): void
    {
        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $validationErrors[] = ['field' => $error->getPropertyPath(), ' message' => $error->getMessage()];
            }
            if (!empty($validationErrors)) {
                throw new \RuntimeException($this->serializer->serialize($validationErrors, 'json'));
            }
        }
    }
}
