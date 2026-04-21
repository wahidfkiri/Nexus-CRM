<?php

namespace Vendor\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserInvited
{
    use Dispatchable;
    public function __construct(public \Vendor\User\Models\UserInvitation $invitation) {}
}

class UserActivated
{
    use Dispatchable;
    public function __construct(public User $user) {}
}

class UserSuspended
{
    use Dispatchable;
    public function __construct(public User $user) {}
}

class UserRoleChanged
{
    use Dispatchable;
    public function __construct(
        public User   $user,
        public string $oldRole,
        public string $newRole,
    ) {}
}