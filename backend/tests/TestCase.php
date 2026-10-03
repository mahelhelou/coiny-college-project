<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Behave like the SPA so Sanctum starts a cookie session (see SANCTUM_STATEFUL_DOMAINS).
        $this->withHeader('Referer', 'http://localhost:5173/');
    }
}
