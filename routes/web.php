<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect('/home');
});

Route::get('/home', function () {
    $categories = [
        'Jeux Vidéo' => ['Combat', 'FPS', 'Speed-run'],
        'Sports individuels' => ['Tennis', 'Natation', 'Athlétisme'],
        'Sports collectifs' => ['Football', 'Basketball', 'Rugby'],
    ];

    $model = new class
    {
        public function urlName(string $name): string
        {
            return Str::slug($name);
        }
    };

    return view('home', compact('categories', 'model'));
});

Route::get('/sport/{name?}', function (?string $name = null) {
    return view('sport', ['name' => $name ?? 'Sports']);
});

Route::get('/login/', function (?string $name = null) {
    return view('login');
});

Route::get('/register', function () {
    return view('register');
});