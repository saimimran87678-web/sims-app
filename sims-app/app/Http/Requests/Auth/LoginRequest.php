<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Always pass false for 'remember' to prevent persistent long-term sessions.
        // We only use the 'remember' checkbox to pre-fill the email address on the login screen.
        if (! Auth::attempt($this->only('email', 'password'), false)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Handle Custom "Remember Email" Behavior
        if ($this->boolean('remember')) {
            \Illuminate\Support\Facades\Cookie::queue('remember_email', $this->email, 60 * 24 * 30); // 30 days
        } else {
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('remember_email'));
        }

        $user = Auth::user();
        
        // Retrieve system active sessions
        $activeSessions = \App\Models\AcademicSession::active()->where('is_active', true)->get();
        if ($activeSessions->isEmpty()) {
            $activeSessions = \App\Models\AcademicSession::active()->orderBy('start_date', 'desc')->get();
        }
        
        // Check user's access via session_user pivot
        $userSessions = \Illuminate\Support\Facades\DB::table('session_user')
            ->where('user_id', $user->id)
            ->whereIn('academic_session_id', $activeSessions->pluck('id'))
            ->get();

        $activeUserSessions = $userSessions->filter(fn($s) => $s->is_active);

        // If completely blocked and not Admin / Super Admin
        if ($activeUserSessions->isEmpty() && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account has been disabled for the active academic session.'),
            ]);
        }

        // Determine target session ID
        $targetSessionId = null;
        if ($activeUserSessions->isNotEmpty()) {
            $targetSessionId = $activeUserSessions->first()->academic_session_id;
        } else {
            $targetSessionId = $activeSessions->first()->id ?? null;
        }

        if ($targetSessionId) {
            session(['current_session_id' => $targetSessionId]);
            if ($user->role === 'admin' || $user->hasRole('Super Admin')) {
                session(['selected_academic_session_id' => $targetSessionId]);
            } else {
                session()->forget('selected_academic_session_id');
            }

            // Determine Shift Type
            $targetSession = \App\Models\AcademicSession::find($targetSessionId);
            if ($targetSession && $targetSession->shift_type === 'Regular') {
                session(['selected_shift_type' => 'regular']);
            } else {
                // Check if user has restricted allowed_shifts in session_user
                $sessionUser = $activeUserSessions->firstWhere('academic_session_id', $targetSessionId);
                $allowedShifts = $sessionUser ? ($sessionUser->allowed_shifts ?? 'both') : 'both';

                if ($allowedShifts === 'morning') {
                    session(['selected_shift_type' => 'morning']);
                } elseif ($allowedShifts === 'evening') {
                    session(['selected_shift_type' => 'evening']);
                } else {
                    // Time-Based Default Shift: 12:00am - 2:00pm = morning, 2:01pm - 11:59pm = evening
                    $now = now()->format('H:i');
                    $preferredShift = ($now >= '00:00' && $now <= '14:00') ? 'morning' : 'evening';
                    session(['selected_shift_type' => $preferredShift]);
                }
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
