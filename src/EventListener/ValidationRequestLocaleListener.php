<?php

declare(strict_types=1);

namespace Pawsitiwe\EventListener;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ValidationRequestLocaleListener
{
    private const VALIDATION_PATH = '/ajax/form/validate';

    public function __construct(
        private readonly RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (self::VALIDATION_PATH !== $request->getPathInfo()) {
            return;
        }

        $refererUrl = $request->headers->get('Referer');
        if (null === $refererUrl) {
            return;
        }

        $refererRequest = Request::create($refererUrl);
        $this->requestAnalyzer->analyze($refererRequest);

        $resolvedAttributes = $refererRequest->attributes->get(RequestAnalyzer::SULU_ATTRIBUTE);
        if (null !== $resolvedAttributes) {
            $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $resolvedAttributes);
        }
    }
}
