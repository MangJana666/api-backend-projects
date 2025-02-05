<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\Users;
use Illuminate\Auth\Access\Response;

class StoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?Users $user, Story $story): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Users $user, Story $story): bool
    {
        return $user->id === $story->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Users $user, Story $story): bool
    {
        return $user->id === $story->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Users $user, Story $story): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Users $user, Story $story): bool
    {
        //
    }

    public function viewMyStories(Users $user): bool
    {
        return true;
    }


}
