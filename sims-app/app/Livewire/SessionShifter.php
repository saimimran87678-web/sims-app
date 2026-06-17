<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;

class SessionShifter extends Component
{
    public $activeSessions = [];
    public $currentSessionId;

    public function mount()
    {
        $this->currentSessionId = AcademicSession::getActiveSessionId();
        
        $user = auth()->user();
        if (!$user) return;

        // Get ALL sessions so users can access historical data
        $systemSessions = AcademicSession::orderBy('start_date', 'desc')->get();

        if ($user->hasRole('Super Admin') || $user->role === 'admin') {
            $this->activeSessions = $systemSessions;
        } else {
            // For teachers/staff, restrict access to only globally active sessions they are actively assigned to (not disabled)
            $userSessionIds = DB::table('session_user')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('academic_session_id');

            $this->activeSessions = $systemSessions->where('is_active', true)->whereIn('id', $userSessionIds);
        }
    }

    public function switchSession($sessionId)
    {
        session(['current_session_id' => $sessionId]);
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        // Only render if they have more than 1 active session available
        return view('livewire.session-shifter');
    }
}
