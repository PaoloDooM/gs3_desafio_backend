<?php

namespace App\Services;

use App\Models\User;
use App\Exceptions\NotFoundException;

class UserService
{
    public static function updateUserById($id, $userData)
    {
        $user = User::find($id);
        if (!$user) {
            throw new NotFoundException('User "' . $id . '" not found');
        }
        $user->update($userData);
    }
}
