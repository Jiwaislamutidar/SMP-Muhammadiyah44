<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.guru.login');
    }

    public function login(Request $request)
    {
        // Proses login guru nanti di sini
    }
}