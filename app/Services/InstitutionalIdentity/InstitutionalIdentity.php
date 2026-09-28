<?php

namespace App\Services\InstitutionalIdentity;

final readonly class InstitutionalIdentity
{
    /**
     * @param  array<string, mixed>  $academicLinks
     */
    public function __construct(
        public string $issuer,
        public string $subject,
        public ?string $displayName,
        public array $academicLinks,
        public \DateTimeImmutable $retrievedAt,
    ) {}
}
