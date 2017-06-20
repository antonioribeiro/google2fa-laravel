<?php

namespace PragmaRX\Google2FALaravel\Support;

use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait ErrorBag
{
    /**
     * Create an error bag and store a message on int.
     *
     * @param $message
     *
     * @return MessageBag
     */
    protected function createErrorBagForMessage($message)
    {
        return new MessageBag([
            'message' => $message,
        ]);
    }

    /**
     * Get a message bag with a message for a particular status code.
     *
     * @param $statusCode
     *
     * @return MessageBag
     */
    protected function getErrorBagForStatusCode($statusCode)
    {
        return $this->createErrorBagForMessage(
            trans(
                config(
                    $statusCode == SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY
                        ? 'google2fa.error_messages.wrong_otp'
                        : 'unknown error'
                )
            )
        );
    }
}
