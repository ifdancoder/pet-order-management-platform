<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

enum NotificationTemplate: string
{
    case WelcomeUser = 'welcome_user';
}
