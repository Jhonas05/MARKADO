<?php

namespace Tests\Feature;

use App\Identity\Contracts\InstitutionalEmailPolicy;
use Tests\TestCase;

class InstitutionalEmailPolicyTest extends TestCase
{
    public function test_exact_configured_institutional_domain_is_allowed_without_rewriting_local_part(): void
    {
        config()->set('identity.institutional_email_domains', ['students.example.edu']);

        $policy = app(InstitutionalEmailPolicy::class);

        $this->assertSame(
            'Student+Lab@students.example.edu',
            $policy->normalizeAllowedAddress('Student+Lab@STUDENTS.EXAMPLE.EDU'),
        );
    }

    public function test_personal_lookalike_and_unlisted_subdomains_are_rejected(): void
    {
        config()->set('identity.institutional_email_domains', ['students.example.edu']);

        $policy = app(InstitutionalEmailPolicy::class);

        foreach ([
            'student@gmail.com',
            'student@students.example.edu.attacker.test',
            'student@portal.students.example.edu',
            'student@students-example.edu',
            ' student@students.example.edu',
            'student@students.example.edu.',
        ] as $email) {
            $this->assertNull($policy->normalizeAllowedAddress($email), "{$email} must be denied.");
        }
    }

    public function test_unconfigured_allowlist_denies_every_address(): void
    {
        config()->set('identity.institutional_email_domains', []);

        $this->assertNull(
            app(InstitutionalEmailPolicy::class)->normalizeAllowedAddress('student@students.example.edu'),
        );
    }

    public function test_internationalized_domains_are_compared_in_ascii_form(): void
    {
        config()->set('identity.institutional_email_domains', ['münchen.example']);

        $this->assertSame(
            'student@xn--mnchen-3ya.example',
            app(InstitutionalEmailPolicy::class)->normalizeAllowedAddress('student@MÜNCHEN.EXAMPLE'),
        );
    }
}
