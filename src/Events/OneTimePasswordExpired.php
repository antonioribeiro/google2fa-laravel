<?php

namespace PragmaRX\Google2FALaravel\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class OneTimePasswordExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    /**
     * Create a new event instance.
     *
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}
