<?php

namespace Tests\Feature;

use App\Identity\Support\HostedPresentationEnvironment;
use Tests\TestCase;

class HostedPresentationEnvironmentTest extends TestCase
{
    public function test_hosted_presentation_is_default_off_and_command_fails_closed(): void
    {
        $this->assertFalse(HostedPresentationEnvironment::configurationIsSafe());
        $this->assertFalse(HostedPresentationEnvironment::enabled());

        $this->artisan('markado:presentation provision --confirm=missing')
            ->expectsOutputToContain('configuration did not verify')
            ->assertFailed();
    }

    public function test_configuration_requires_exact_staging_url_and_railway_identity(): void
    {
        $this->app['env'] = 'staging';
        config()->set([
            'app.url' => 'https://markado-app-staging.example.test',
            'database.default' => 'pgsql',
            'database.connections.pgsql.database' => 'markado_presentation',
            'presentation.hosted_enabled' => true,
            'presentation.expected_url' => 'https://markado-app-staging.example.test',
            'presentation.production_url' => 'https://markado-app-production.example.test',
            'presentation.environment_id' => 'staging-environment-id',
            'presentation.environment_name' => 'staging',
            'presentation.runtime_environment_id' => 'staging-environment-id',
            'presentation.runtime_environment_name' => 'staging',
        ]);

        $this->assertTrue(HostedPresentationEnvironment::configurationIsSafe());

        config()->set('presentation.runtime_environment_id', 'production-environment-id');
        $this->assertFalse(HostedPresentationEnvironment::configurationIsSafe());

        config()->set('presentation.runtime_environment_id', 'staging-environment-id');
        config()->set('presentation.expected_url', 'https://markado-app-production.example.test');
        config()->set('app.url', 'https://markado-app-production.example.test');
        $this->assertFalse(HostedPresentationEnvironment::configurationIsSafe());
    }
}
