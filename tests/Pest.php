<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Cases
|--------------------------------------------------------------------------
|
| Unit tests keep Pest's default PHPUnit test case so they stay isolated.
| Feature and browser tests boot Laravel and reset the database per test.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');
