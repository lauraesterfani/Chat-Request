<?php

namespace App\Services\InstitutionalIdentity;

interface InstitutionalIdentityProvider
{
    /**
     * Retrieves identity data from an authorized institutional source.
     *
     * The caller must use the stable provider subject and issuer. Implementations
     * must not match accounts by e-mail address or name.
     */
    public function findBySubject(string $issuer, string $subject): InstitutionalIdentity;
}
