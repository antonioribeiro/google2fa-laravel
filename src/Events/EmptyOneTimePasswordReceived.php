<?php

namespace PragmaRX\Google2FALaravel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmptyOneTimePasswordReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
