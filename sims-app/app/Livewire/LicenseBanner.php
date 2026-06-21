<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\LicenseStatus;
use App\Services\LicenseVerifier;
use App\Services\FirebaseAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LicenseBanner extends Component
{
    public $status = [];
    public $dismissed = false;

    public function mount()
    {
        $this->status = LicenseStatus::getStatus();
        $this->dismissed = session('sims_license_banner_dismissed', false);
    }



    /**
     * Dismiss warning banner for the current session.
     */
    public function dismiss()
    {
        session(['sims_license_banner_dismissed' => true]);
        $this->dismissed = true;
    }

    public function render()
    {
        return view('livewire.license-banner');
    }
}
