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

        // Get all active sessions in the database
        $systemActiveSessions = AcademicSession::where('is_active', true)->get();

        if ($user->hasRole('Super Admin')) {
            $this->activeSessions = $systemActiveSessions;
        } else {
            // Only get sessions the user is active in
            $userSessionIds = DB::table('session_user')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('academic_session_id');

            $this->activeSessions = $systemActiveSessions->whereIn('id', $userSessionIds);
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
