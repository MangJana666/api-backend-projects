<?php

namespace App\Policies;

use App\Models\Users;
use App\Models\Bookmark;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookmarkPolicy
{
    /**
     * Determine whether the user can view any models.
     */

     use HandlesAuthorization;
     
    public function viewAny(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Users $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Users $user): bool
    {
        return $user !== null && $user->id !== $story->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Users $user, Bookmark $bookmark): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Users $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Users $user, Bookmark $bookmark): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Users $user, Bookmark $bookmark): bool
    {
        //
    }
}
