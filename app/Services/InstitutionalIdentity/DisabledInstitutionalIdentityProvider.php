<?php

namespace App\Services\InstitutionalIdentity;

final class DisabledInstitutionalIdentityProvider implements InstitutionalIdentityProvider
{
    public function findBySubject(string $issuer, string $subject): InstitutionalIdentity
    {
        throw new InstitutionalIdentityUnavailable(
            'A integração institucional não está configurada ou homologada para este ambiente.'
        );
    }
}
