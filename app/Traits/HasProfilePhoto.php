<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasProfilePhoto
{
    /**
     * Get the user's profile photo URL.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return Storage::disk('public')->url($this->profile_photo_path);
        }

        return $this->generateDefaultProfileImage();
    }

    /**
     * Generate a default profile image URL.
     *
     * @return string
     */
    private function generateDefaultProfileImage()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=556ee6&background=dce8f7&bold=true&rounded=true';
        //For 2 letters ans dandom colors
        // $name = urlencode($this->name ?? 'User');
        // return "https://ui-avatars.com/api/?name={$name}&background=random";
    }

}
