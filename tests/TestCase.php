<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\PendingCommand;
use Laravel\Fortify\Features;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function pendingArtisan(string $command, array $parameters = []): PendingCommand
    {
        $pendingCommand = $this->artisan($command, $parameters);

        if (! $pendingCommand instanceof PendingCommand) {
            throw new LogicException('Console output mocking must be enabled for this test.');
        }

        return $pendingCommand;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
