<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:admin,teacher'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Assign Spatie roles based on the user's role field
        if ($user->role === 'admin') {
            if (User::count() === 1) {
                $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
                $user->assignRole($superAdminRole);
            }
        } elseif ($user->role === 'teacher') {
            $teacherRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher']);
            $user->assignRole($teacherRole);
        }

        // Attach newly registered user to the active session so they can log in and have session context
        $activeSessionId = \App\Models\AcademicSession::getActiveSessionId();
        if ($activeSessionId) {
            \Illuminate\Support\Facades\DB::table('session_user')->insert([
                'user_id'             => $user->id,
                'academic_session_id' => $activeSessionId,
                'is_active'           => true,
                'is_primary'          => true,
                'allowed_shifts'      => $user->role === 'admin' ? 'both' : 'morning',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        if ($user->role === 'admin') {
            return redirect(route('admin.dashboard', absolute: false));
        }

        if ($user->role === 'teacher') {
            return redirect(route('teacher.dashboard', absolute: false));
        }

        return redirect(route('dashboard', absolute: false));
    }
}
