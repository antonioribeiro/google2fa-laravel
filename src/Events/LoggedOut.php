<?php

namespace PragmaRX\Google2FALaravel\Events;

//use Illuminate\Broadcasting\InteractsWithSockets;
//use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoggedOut
{
    //use Dispatchable, InteractsWithSockets, SerializesModels;
    use SerializesModels;

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
