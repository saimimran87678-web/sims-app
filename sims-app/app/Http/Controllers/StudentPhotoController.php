<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentPhotoController extends Controller
{
    /**
     * Serve a protected student photograph through an authorized gate.
     */
    public function show(Request $request, Student $student)
    {
        // 1. Authorize access
        if (!$this->isAuthorized($request, $student)) {
            abort(403, 'Unauthorized access to student photograph.');
        }

        // 2. Fallback to SVG avatar if no photo path is set
        if (empty($student->profile_photo_path)) {
            return $this->defaultAvatar($student->gender);
        }

        // 3. Locate file across private or legacy public storage
        $possiblePaths = [
            storage_path('app/private/' . $student->profile_photo_path),
            storage_path('app/public/' . $student->profile_photo_path),
            storage_path('app/' . $student->profile_photo_path),
            public_path('storage/' . $student->profile_photo_path),
        ];

        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath) && is_file($filePath)) {
                $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
                return response()->file($filePath, [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'private, max-age=86400',
                ]);
            }
        }

        // If file missing on disk, return default avatar
        return $this->defaultAvatar($student->gender);
    }

    /**
     * Verify whether the requester has permission to view this minor student's photo.
     */
    protected function isAuthorized(Request $request, Student $student): bool
    {
        // Signed URLs (e.g. for external RFID attendance displays or kiosks)
        if ($request->hasValidSignature()) {
            return true;
        }

        // Authenticated SIMS user (Admin, Staff, Teacher)
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAdmin() || $user->hasRole('Super Admin') || $user->hasRole('Teacher') || $user->can('students.view')) {
                return true;
            }
        }

        // Parent / Student portal session
        $portalStudentId = session('student_portal_id');
        if ($portalStudentId) {
            if ($portalStudentId == $student->id) {
                return true;
            }
            // Sibling access in portal
            $portalStudent = Student::find($portalStudentId);
            if ($portalStudent && $portalStudent->primary_family_cnic_hash === $student->primary_family_cnic_hash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Output a lightweight SVG avatar.
     */
    protected function defaultAvatar(?string $gender = null)
    {
        $bg = strtolower($gender ?? '') === 'female' ? '#fdf2f8' : '#eff6ff';
        $fill = strtolower($gender ?? '') === 'female' ? '#ec4899' : '#3b82f6';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
    <rect width="100" height="100" rx="20" fill="{$bg}"/>
    <circle cx="50" cy="38" r="18" fill="{$fill}"/>
    <path d="M22 84 C22 66, 34 60, 50 60 C66 60, 78 66, 78 84 Z" fill="{$fill}"/>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
