<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function user_add_open() {
        $roles = Role::all();
        if (Auth::check() && Auth::user()->role_id == 2) {
            return view('components.user-add-box', compact('roles'));
        }
        return view('login');
    }
    public function user_edit_open($id) {
        $user = User::findOrFail($id);
        if (Auth::check() && Auth::user()->role_id == 2) {
            return view('components.user-edit-box', compact('user'));
        }
        return view('login');
    }
    public function user_deactivate_open($id) {
        $user = User::findOrFail($id);
        if (Auth::check() && Auth::user()->role_id == 2) {
            return view('components.user-deactivate-box', compact('user'));
        }
        return view('login');
    }
}
