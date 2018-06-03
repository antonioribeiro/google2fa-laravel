<?php

namespace PragmaRX\Google2FALaravel\Events;

//use Illuminate\Broadcasting\InteractsWithSockets;
//use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OneTimePasswordExpired
{
    //use Dispatchable, InteractsWithSockets, SerializesModels;
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
