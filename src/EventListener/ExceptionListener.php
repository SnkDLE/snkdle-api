<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Log l'erreur
        $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        // Prépare la réponse JSON
        $response = new JsonResponse([
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode() ?: 500,
        ]);

        // Définit le code HTTP (par défaut 500 si code invalide)
        $statusCode = $exception->getCode();
        if (!is_int($statusCode) || $statusCode < 100 || $statusCode > 599) {
            $statusCode = 500;
        }
        $response->setStatusCode($statusCode);

        $event->setResponse($response);
    }
}
