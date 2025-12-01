<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

class PageController extends Controller
{
    public function dashboard() {
        $user = Auth::user();
        if (Auth::check() && Auth::user()->role_id == 2) {
            return View::make('admin.dashboard');
        }
        return View::make('dashboard');
    }

    public function welcome() {
        return redirect()->route('welcome');
    }

    public function home() {
        return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
    }

    public function admin() {
        if (Auth::check() && Auth::user()->role_id == 2) {
            return view('admin.dashboard');
        } elseif (Auth::check()) {
            return view('dashboard');
        }
        return redirect('login');
    }

    public function bin_monitoring() {
        return View::make('bin-monitoring');
    }

    // MIDDLEWARE WHEN OPENING CLASSIFICATION PAGE
    public function classification() {
        return View::make('classification');
    }

    public function notifications() {
        return View::make('notifications');
    }

    // MIDDLEWARE WHEN OPENING USER MANAGEMENT PAGE
    public function user_management(Request $request)
    {
        $user = Auth::user();
        if ($user->role_id !== 2) {
            abort(403, 'Unauthorized access.');
        }

        $sort      = $request->query('sort', 'id');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['id','name','email','role','created_at','updated_at','status','last_seen','last_status_updated'];
        if (!in_array($sort, $allowedSorts)) $sort = 'id';

        $usersQuery = User::with('role')
            ->withMax('sessions', 'last_activity');

        if ($sort === 'role') {
            $usersQuery = $usersQuery
                ->join('roles', 'roles.id', '=', 'users.role_id')
                ->orderBy('roles.name', $direction)
                ->select('users.*');
        } elseif ($sort === 'last_seen') {
            $usersQuery = $usersQuery->orderBy('sessions_max_last_activity', $direction);
        } elseif ($sort === 'last_status_updated') {
            $usersQuery = $usersQuery->orderBy('last_status_updated', $direction);
        } else {
            $usersQuery = $usersQuery->orderBy($sort, $direction);
        }

        // paginate, preserve query string
        $users = $usersQuery->paginate(25)->withQueryString();

        $roles = Role::get();

        return view('admin.user-management', [
            'users' => $users,
            'roles' => $roles,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

}
