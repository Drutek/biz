<?php

namespace App\Events;

use App\Models\BusinessEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BusinessEventRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public BusinessEvent $businessEvent) {}
}
