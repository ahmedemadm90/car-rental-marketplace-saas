<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('reservations:expire-holds')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();
