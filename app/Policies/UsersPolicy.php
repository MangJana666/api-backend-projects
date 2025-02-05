<?php

namespace App\Policies;

use App\Models\Users;
use Illuminate\Auth\Access\Response;

class UsersPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Users $user, Users $model): bool
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
    public function update(Users $user, Users $model): bool
    {
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Users $user, Users $model): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Users $user, Users $model): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Users $user, Users $model): bool
    {
        //
    }

    public function updatePassword(Users $user, Users $model): bool
    {
        return $user->id === $model->id;
    }

    public function uploadAvatar(Users $user, Users $model): bool
    {
        return $user->id === $model->id;
    }
    
    // public function updateUserProfile(Users $user, Users $model): bool
    // {
    //     return $user->id === $model->id;
    // }
    
}
