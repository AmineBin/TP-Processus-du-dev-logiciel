<?php

namespace App\Http\Controllers;

use App\Models\User;

abstract class UserController
{
    public function connect_user($mail, $password)
    {
        $user = User::where('email', $mail)->first();

        return $user;
    }
}