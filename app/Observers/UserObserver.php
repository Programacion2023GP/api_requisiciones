<?php

namespace App\Observers;

use App\Models\User;
use App\Models\RelUsuarioDepartamento;

class UserObserver
{
    public function created(User $user)
    {
        if (request()->has('IDDepartamento')) {
            RelUsuarioDepartamento::create([
                'IDUsuario' => $user->IDUsuario,
                'IDDepartamento' => request('IDDepartamento'),
            ]);
        }
    }
}