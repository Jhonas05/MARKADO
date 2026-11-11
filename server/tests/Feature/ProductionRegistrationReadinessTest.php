<?php

namespace Tests\Feature;

use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Support\ProductionRegistrationEnvironment;
use App\Identity\Support\RegistrationEnvironment;
use App\Identity\Support\ResendHttpInstitutionalEmailVerificationDelivery;
use App\Identity\Support\TrustedRosterRecordPolicy;
use App\Models\SchoolStudentRecord;
use Tests\TestCase;

class ProductionRegistrationReadinessTest extends TestCase
{
    public function test_production_registration_fails_closed_without_real_mail_and_roster_configuration(): void
    {
        $this->configureBaseProductionControls();
        config()->set('mail.default', 'log');
        config()->set('identity.trusted_roster_source', '');
        config()->set('identity.trusted_roster_max_age_hours', null);

        $this->assertFalse(ProductionRegistrationEnvironment::enabled());
        $this->assertFalse(RegistrationEnvironment::enabled());
    }

    public function test_production_readiness_requires_secure_origin_remote_mail_and_fresh_source_tagged_roster_evidence(): void
    {
        $this->configureBaseProductionControls();
        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp.host', 'smtp.institution.invalid');
        config()->set('mail.from.address', 'markado@institution.invalid');
        config()->set('identity.trusted_roster_source', 'institution-approved-sis-feed');
        config()->set('identity.trusted_roster_max_age_hours', 24);

        $this->assertTrue(ProductionRegistrationEnvironment::enabled());

        $record = new SchoolStudentRecord([
            'roster_source' => 'institution-approved-sis-feed',
            'roster_synced_at' => now()->subHour(),
        ]);
        $this->assertTrue(app(TrustedRosterRecordPolicy::class)->accepts($record));

        $record->roster_synced_at = now()->subHours(25);
        $this->assertFalse(app(TrustedRosterRecordPolicy::class)->accepts($record));
    }

    public function test_production_readiness_accepts_the_configured_resend_https_delivery_adapter(): void
    {
        $this->configureBaseProductionControls();
        config()->set('identity.institutional_email_verification_delivery_driver', 'resend-http');
        config()->set('services.resend.key', 're_synthetic_test_key');
        config()->set('mail.from.address', 'verification@institution.invalid');
        config()->set('identity.trusted_roster_source', 'institution-approved-sis-feed');
        config()->set('identity.trusted_roster_max_age_hours', 24);

        $this->assertTrue(ProductionRegistrationEnvironment::enabled());
        $this->assertInstanceOf(
            ResendHttpInstitutionalEmailVerificationDelivery::class,
            app(InstitutionalEmailVerificationDelivery::class),
        );

        config()->set('services.resend.key', '');
        $this->assertFalse(ProductionRegistrationEnvironment::enabled());

        config()->set('services.resend.key', 're_synthetic_test_key');
        config()->set('identity.institutional_email_verification_delivery_driver', 'unapproved-driver');
        $this->assertFalse(ProductionRegistrationEnvironment::enabled());
    }

    private function configureBaseProductionControls(): void
    {
        $this->app['env'] = 'production';
        config()->set('app.url', 'https://markado.institution.invalid');
        config()->set('database.default', 'pgsql');
        config()->set('identity.public_student_registration_enabled', true);
        config()->set('identity.student_self_registration_enabled', true);
        config()->set('identity.automatic_student_activation_enabled', true);
        config()->set('identity.institutional_email_verification_enabled', true);
        config()->set('identity.institutional_email_verification_delivery_enabled', true);
        config()->set('identity.institutional_email_verification_hourly_limit', 5);
        config()->set('identity.institutional_email_domains', ['institution.invalid']);
    }
}
