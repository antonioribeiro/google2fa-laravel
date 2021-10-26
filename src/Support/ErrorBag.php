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
        $errorMap = [
            SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY => 'google2fa.error_messages.wrong_otp',
            SymfonyResponse::HTTP_BAD_REQUEST          => 'google2fa.error_messages.cannot_be_empty',
        ];

        return $this->createErrorBagForMessage(
            trans(
                config(
                    array_key_exists($statusCode, $errorMap) ? $errorMap[$statusCode] : 'google2fa.error_messages.unknown',
                    'google2fa.error_messages.unknown'
                )
            )
        );
    }
}
