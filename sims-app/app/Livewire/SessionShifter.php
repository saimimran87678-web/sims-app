<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;

class SessionShifter extends Component
{
    public $activeSessions = [];
    public $currentSessionId;
    public $currentShift;
    public $allowedShifts = 'both'; // morning, evening, both
    public $currentSessionIsRegular = false;
    public $showBothOption = true;

    public function mount()
    {
        $this->currentSessionId = AcademicSession::getActiveSessionId();
        
        $currentSession = AcademicSession::find($this->currentSessionId);
        $this->currentSessionIsRegular = ($currentSession && $currentSession->shift_type === 'Regular');

        $route = request()->route();
        $routeName = $route ? $route->getName() : '';
        $allowedToSeeBoth = in_array($routeName, [
            'admin.dashboard',
            'teacher.dashboard',
            'admin.students',
            'teacher.students',
            'teacher.shared.students'
        ]);
        $this->showBothOption = $allowedToSeeBoth;

        if ($this->currentSessionIsRegular) {
            $this->currentShift = 'regular';
            session(['selected_shift_type' => 'regular']);
        } else {
            $this->currentShift = session('selected_shift_type', 'morning');
            if ($this->currentShift === 'regular') {
                $this->currentShift = 'morning';
                session(['selected_shift_type' => 'morning']);
            }
            
            if (!$allowedToSeeBoth && $this->currentShift === 'both' && $routeName) {
                $this->currentShift = 'morning';
                session(['selected_shift_type' => 'morning']);
            }
        }
        
        $user = auth()->user();
        if (!$user) return;

        // Get active parent/non-archived sessions
        $systemSessions = AcademicSession::active()->orderBy('start_date', 'desc')->get();

        if ($user->hasRole('Super Admin') || $user->role === 'admin') {
            $this->activeSessions = $systemSessions;
            $this->allowedShifts = $this->currentSessionIsRegular ? 'regular' : 'both';
        } else {
            // For teachers/staff, restrict access to only active sessions they are assigned to
            $userSessionIds = DB::table('session_user')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('academic_session_id');

            $this->activeSessions = $systemSessions->where('is_active', true)->whereIn('id', $userSessionIds);

            if ($this->currentSessionIsRegular) {
                $this->allowedShifts = 'regular';
            } else {
                // Restrict allowed shifts
                $sessionUser = DB::table('session_user')
                    ->where('user_id', $user->id)
                    ->where('academic_session_id', $this->currentSessionId)
                    ->first();
                $this->allowedShifts = $sessionUser ? ($sessionUser->allowed_shifts ?? 'both') : 'both';

                if ($this->allowedShifts === 'morning') {
                    $this->currentShift = 'morning';
                    session(['selected_shift_type' => 'morning']);
                } elseif ($this->allowedShifts === 'evening') {
                    $this->currentShift = 'evening';
                    session(['selected_shift_type' => 'evening']);
                }
            }
        }
    }

    public function switchSession($sessionId)
    {
        if (AcademicSession::active()->where('id', $sessionId)->exists()) {
            session(['selected_academic_session_id' => $sessionId]);
            session()->forget('current_session_id');
        }
        return redirect(request()->header('Referer'));
    }

    public function switchShift($shift)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && $user->role !== 'admin') {
            $sessionUser = DB::table('session_user')
                ->where('user_id', $user->id)
                ->where('academic_session_id', AcademicSession::getActiveSessionId())
                ->first();
            $allowed = $sessionUser ? ($sessionUser->allowed_shifts ?? 'both') : 'both';
            if ($allowed !== 'both' && $shift !== $allowed) {
                return redirect(request()->header('Referer'));
            }
        }

        if ($shift === 'both' && !$this->showBothOption) {
            return redirect(request()->header('Referer'));
        }

        if (in_array($shift, ['morning', 'evening', 'both'])) {
            session(['selected_shift_type' => $shift]);
        }
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        return view('livewire.session-shifter');
    }
}
