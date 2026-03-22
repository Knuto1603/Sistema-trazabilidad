<?php

namespace App\shared\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use function Symfony\Component\String\u;

class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        $message = $this->message($exception);

        $response = $this->response($message, $exception, $event);

        $event->setResponse($response);
    }

    protected function message(\Throwable $exception): string
    {
        return sprintf('%s', $exception->getMessage());
    }

    protected function response(string $message, \Throwable $exception, ExceptionEvent $event): Response
    {
        return $this->responseException($exception, $message);
    }

    protected function responseException(\Throwable $exception, string $message): JsonResponse
    {
        $data = [
            'status' => false,
            'exception' => $this->getNameClass($exception),
            'message' => $message,
        ];

        $response = new JsonResponse($data);

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    protected function getNameClass(\Throwable $exception): string
    {
        $nameClass = (new \ReflectionClass($exception))->getShortName();

        return u($nameClass)->snake()->toString();
    }
}
