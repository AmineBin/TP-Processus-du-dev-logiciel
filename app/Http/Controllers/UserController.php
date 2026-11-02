<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function create_user(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:utilisateur,nom_utilisateur'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:utilisateur,email'],
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'nom_utilisateur' => $data['username'],
            'nom' => $data['last_name'] ?? null,
            'prenom' => $data['first_name'] ?? null,
            'email' => $data['email'],
            'numero_telephone' => $data['phone'],
            'date_naissance' => $data['birth_date'],
            'mot_de_passe_hash' => Hash::make($data['password']),
            'role_id' => 1,
        ]);

        return redirect('/login')->with('success', 'Compte créé avec succès.');
    }
}