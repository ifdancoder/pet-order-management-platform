<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adapter\Out\Authentication;

use DateInterval;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Modules\Identity\Application\Authentication\AccessTokenClaims;
use Modules\Identity\Application\Authentication\IssuedAccessToken;
use Modules\Identity\Application\Exception\InvalidAccessToken;
use Modules\Identity\Application\Port\Out\Authentication\IAccessTokenService;
use Modules\Identity\Domain\Entity\User;
use Psr\Clock\ClockInterface;

final readonly class LcobucciAccessTokenService implements IAccessTokenService
{
    /** @var non-empty-string */
    private string $issuer;

    /** @var non-empty-string */
    private string $audience;

    public function __construct(
        private Configuration $configuration,
        private ClockInterface $clock,
        string $issuer,
        string $audience,
        private int $ttlSeconds,
    ) {
        if ($issuer === '' || $audience === '' || $ttlSeconds < 1) {
            throw new \InvalidArgumentException(
                'JWT issuer, audience, and positive TTL are required.',
            );
        }

        $this->issuer = $issuer;
        $this->audience = $audience;
    }

    public function issue(User $user): IssuedAccessToken
    {
        $issuedAt = $this->clock->now();
        $expiresAt = $issuedAt->add(
            new DateInterval(sprintf('PT%dS', $this->ttlSeconds)),
        );
        $tokenId = bin2hex(random_bytes(16));

        $token = $this->configuration->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->identifiedBy($tokenId)
            ->relatedTo($user->id()->value())
            ->issuedAt($issuedAt)
            ->canOnlyBeUsedAfter($issuedAt)
            ->expiresAt($expiresAt)
            ->getToken(
                $this->configuration->signer(),
                $this->configuration->signingKey(),
            );

        return new IssuedAccessToken(
            token: $token->toString(),
            expiresAt: $expiresAt,
        );
    }

    public function verify(string $token): AccessTokenClaims
    {
        if ($token === '') {
            throw InvalidAccessToken::create();
        }

        try {
            $parsedToken = $this->configuration->parser()->parse($token);
        } catch (
            CannotDecodeContent
            |InvalidTokenStructure
            |UnsupportedHeaderFound $exception
        ) {
            throw InvalidAccessToken::create($exception);
        }

        if (! $parsedToken instanceof UnencryptedToken) {
            throw InvalidAccessToken::create();
        }

        try {
            $this->configuration->validator()->assert(
                $parsedToken,
                new SignedWith(
                    $this->configuration->signer(),
                    $this->configuration->verificationKey(),
                ),
                new IssuedBy($this->issuer),
                new PermittedFor($this->audience),
                new StrictValidAt($this->clock),
            );
        } catch (RequiredConstraintsViolated $exception) {
            throw InvalidAccessToken::create($exception);
        }

        $userId = $parsedToken->claims()->get('sub');
        $tokenId = $parsedToken->claims()->get('jti');
        $expiresAt = $parsedToken->claims()->get('exp');

        if (
            ! is_string($userId)
            || $userId === ''
            || ! is_string($tokenId)
            || $tokenId === ''
            || ! $expiresAt instanceof DateTimeImmutable
        ) {
            throw InvalidAccessToken::create();
        }

        return new AccessTokenClaims(
            userId: $userId,
            tokenId: $tokenId,
            expiresAt: $expiresAt,
        );
    }
}
