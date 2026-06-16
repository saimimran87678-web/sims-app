<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class LicenseLockedException extends Exception
{
    public function render(Request $request)
    {
        if ($request->header('X-Livewire')) {
            // For Livewire requests, return a special JSON response that
            // Livewire will treat as a validation error (no iframe popup).
            // We use a 422 with a special marker our JS can detect.
            return response()->json([
                'license_locked' => true,
                'message' => $this->getMessage(),
            ], 422);
        }

        // For normal page requests, show the full error view
        return response()->view('errors.license-locked', ['message' => $this->getMessage()], 403);
    }
}
