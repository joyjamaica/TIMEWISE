<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Auto-discovered by Laravel (App\Models\Team => App\Policies\TeamPolicy).
 */
class TeamPolicy
{
    /** Any member can open the workspace, manage tasks and upload resources. */
    public function view(User $user, Team $team): bool
    {
        return $team->hasMember($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    /** Public teams share the code with every member; private teams only with the owner. */
    public function viewInviteCode(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id
            || ($team->visibility === 'public' && $team->hasMember($user));
    }
}
