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
        $activeSessions = \App\Models\AcademicSession::where('is_active', true)->get();
        
        // Check user's access via session_user pivot
        $userSessions = \Illuminate\Support\Facades\DB::table('session_user')
            ->where('user_id', $user->id)
            ->whereIn('academic_session_id', $activeSessions->pluck('id'))
            ->get();

        $activeUserSessions = $userSessions->filter(fn($s) => $s->is_active);

        // If completely blocked and not Super Admin
        if ($activeUserSessions->isEmpty() && !$user->hasRole('Super Admin')) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('Your account has been disabled in all active shifts.'),
            ]);
        }

        if ($user->hasRole('Super Admin') && $activeUserSessions->isEmpty()) {
            // Super admins can bypass, just pick the first active system session
            $targetSessionId = $activeSessions->first()->id ?? null;
            if ($targetSessionId) {
                session(['current_session_id' => $targetSessionId]);
            }
        } elseif ($activeUserSessions->isNotEmpty()) {
            // Determine Time-Based Default Shift (12:00am - 2:00pm = Morning, 2:01pm - 11:59pm = Evening)
            $now = now()->format('H:i');
            $preferredShift = ($now >= '00:00' && $now <= '14:00') ? 'Morning' : 'Evening';
            
            // Map the active sessions to their shift types (assuming parent or 'Morning' is Morning)
            $morningSession = $activeSessions->first(fn($s) => $s->shift_type === 'Morning' || is_null($s->parent_id));
            $eveningSession = $activeSessions->first(fn($s) => $s->shift_type === 'Evening');
            
            $morningId = $morningSession ? $morningSession->id : null;
            $eveningId = $eveningSession ? $eveningSession->id : null;

            $targetSessionId = null;

            // Attempt to assign the preferred shift
            if ($preferredShift === 'Morning' && $morningId && $activeUserSessions->contains('academic_session_id', $morningId)) {
                $targetSessionId = $morningId;
            } elseif ($preferredShift === 'Evening' && $eveningId && $activeUserSessions->contains('academic_session_id', $eveningId)) {
                $targetSessionId = $eveningId;
            }

            // Fallback to whichever shift is available to them
            if (!$targetSessionId) {
                $targetSessionId = $activeUserSessions->first()->academic_session_id;
            }

            session(['current_session_id' => $targetSessionId]);
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
