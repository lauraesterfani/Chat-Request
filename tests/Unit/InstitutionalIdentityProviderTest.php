<?php

namespace Tests\Unit;

use App\Services\InstitutionalIdentity\DisabledInstitutionalIdentityProvider;
use App\Services\InstitutionalIdentity\InstitutionalIdentityProviderFactory;
use App\Services\InstitutionalIdentity\InstitutionalIdentityUnavailable;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class InstitutionalIdentityProviderTest extends TestCase
{
    public function test_institutional_provider_is_disabled_by_default(): void
    {
        Config::set('institutional_identity.enabled', false);

        $provider = InstitutionalIdentityProviderFactory::make();

        $this->assertInstanceOf(DisabledInstitutionalIdentityProvider::class, $provider);
        $this->expectException(InstitutionalIdentityUnavailable::class);
        $provider->findBySubject('https://issuer.example.test', 'fixture-subject-1');
    }

    public function test_enabled_integration_without_a_homologated_adapter_fails_safely(): void
    {
        Config::set('institutional_identity.enabled', true);

        $this->expectException(InstitutionalIdentityUnavailable::class);
        InstitutionalIdentityProviderFactory::make();
    }
}
