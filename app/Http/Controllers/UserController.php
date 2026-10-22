<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect('/home');
        }

        return back()->withErrors([
            'email' => 'Adresse e-mail ou mot de passe incorrect.',
        ])->onlyInput('email');
    }

    public function connect_user($mail, $password)
    {
        $user = User::where('email', $mail)->first();

        return $user;
    }

    public function create_user($user_name, $name, $surname, $mail, $phone, $password, $confirm_password)
    {
        
        return true;
    }
}