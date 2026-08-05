<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Entity;

use Modules\Identity\Domain\Enum\UserStatus;
use Modules\Identity\Domain\Exception\InvalidUserStatusTransition;
use Modules\Identity\Domain\Exception\UserAlreadyActive;
use Modules\Identity\Domain\Exception\UserAlreadySuspended;
use Modules\Identity\Domain\Exception\UserDisabled;
use Modules\Identity\Domain\Exception\UserNotSuspended;
use Modules\Identity\Domain\ValueObject\Email;
use Modules\Identity\Domain\ValueObject\PasswordHash;
use Modules\Identity\Domain\ValueObject\UserId;

final class User
{
    public function __construct(
        private readonly UserId $id,
        private Email $email,
        private PasswordHash $passwordHash,
        private UserStatus $status,
    ) {}

    public static function register(
        UserId $id,
        Email $email,
        PasswordHash $passwordHash,
    ): self {
        return new self(
            id: $id,
            email: $email,
            passwordHash: $passwordHash,
            status: UserStatus::Pending,
        );
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
    {
        return $this->passwordHash;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        if ($this->status === UserStatus::Disabled) {
            throw UserDisabled::cannotActivate();
        }

        if ($this->status === UserStatus::Active) {
            throw UserAlreadyActive::create();
        }

        if ($this->status !== UserStatus::Pending) {
            throw InvalidUserStatusTransition::fromTo(
                $this->status,
                UserStatus::Active,
            );
        }

        $this->status = UserStatus::Active;
    }

    public function suspend(): void
    {
        if ($this->status === UserStatus::Disabled) {
            throw UserDisabled::cannotSuspend();
        }

        if ($this->status === UserStatus::Suspended) {
            throw UserAlreadySuspended::create();
        }

        if ($this->status !== UserStatus::Active) {
            throw InvalidUserStatusTransition::fromTo(
                $this->status,
                UserStatus::Suspended,
            );
        }

        $this->status = UserStatus::Suspended;
    }

    public function restore(): void
    {
        if ($this->status !== UserStatus::Suspended) {
            throw UserNotSuspended::create();
        }

        $this->status = UserStatus::Active;
    }

    public function disable(): void
    {
        if (
            $this->status !== UserStatus::Active
            && $this->status !== UserStatus::Suspended
        ) {
            throw InvalidUserStatusTransition::fromTo(
                $this->status,
                UserStatus::Disabled,
            );
        }

        $this->status = UserStatus::Disabled;
    }

    public function changeEmail(Email $email): void
    {
        if ($this->status === UserStatus::Disabled) {
            throw UserDisabled::cannotChangeEmail();
        }

        $this->email = $email;
    }

    public function changePassword(PasswordHash $passwordHash): void
    {
        if ($this->status === UserStatus::Disabled) {
            throw UserDisabled::cannotChangePassword();
        }

        $this->passwordHash = $passwordHash;
    }
}
