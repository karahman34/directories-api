<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Check file visibility.
     *
     * @param   File  $file
     *
     * @return  bool
     */
    private function checkFileVisibility(File $file)
    {
        if (!$file->isPublic()) {
            if (!$file->isOwned()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return mixed
     */
    public function view(?User $user, File $file)
    {
        if (!$user && $file->isPublic()) {
            return true;
        }

        return $this->checkFileVisibility($file);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return mixed
     */
    public function update(User $user, File $file)
    {
        return $file->isOwned();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return mixed
     */
    public function delete(User $user, File $file)
    {
        return $file->isOwned();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return mixed
     */
    public function restore(User $user, File $file)
    {
        return $file->isOwned();
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\File  $file
     * @return mixed
     */
    public function forceDelete(User $user, File $file)
    {
        return $file->isOwned();
    }
}
