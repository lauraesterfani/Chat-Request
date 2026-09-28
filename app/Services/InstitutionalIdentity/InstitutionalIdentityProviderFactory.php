<?php

namespace App\Services\InstitutionalIdentity;

final class InstitutionalIdentityProviderFactory
{
    public static function make(): InstitutionalIdentityProvider
    {
        if (! config('institutional_identity.enabled')) {
            return new DisabledInstitutionalIdentityProvider;
        }

        throw new InstitutionalIdentityUnavailable(
            'Há uma integração institucional habilitada sem adaptador homologado.'
        );
    }
}
