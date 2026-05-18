<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('agents:health-check')->everyFiveMinutes();

Schedule::command('inspire')->hourly();
