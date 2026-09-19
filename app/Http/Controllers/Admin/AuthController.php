<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.admin.login');
    }

    public function login(Request $request)
    {
        // Proses login admin nanti di sini
    }
}