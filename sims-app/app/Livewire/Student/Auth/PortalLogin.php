<?php

namespace App\Livewire\Student\Auth;

use Livewire\Component;
use App\Models\Student;

class PortalLogin extends Component
{
    public $father_cnic = '';
    public $admission_no = '';
    public $errorMessage = '';

    protected function rules()
    {
        return [
            'father_cnic' => ['required', 'string', 'regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/'],
            'admission_no' => ['required', 'string'],
        ];
    }

    protected $messages = [
        'father_cnic.required' => 'Please enter the Father or Guardian CNIC.',
        'father_cnic.regex' => 'Please enter a valid 13-digit NADRA CNIC (XXXXX-XXXXXXX-X).',
        'admission_no.required' => 'Please enter the student Admission Number.',
    ];

    public function login()
    {
        $this->errorMessage = '';
        $this->validate();

        $cnic = trim($this->father_cnic);
        $admNo = trim($this->admission_no);
        $cnicHash = Student::hashBlindIndex($cnic);

        // Find matching active student
        $student = Student::where('admission_no', $admNo)
            ->where(function ($q) use ($cnicHash) {
                $q->where('father_cnic_hash', $cnicHash)
                  ->orWhere('guardian_cnic_hash', $cnicHash);
            })
            ->first();

        if (! $student) {
            $this->errorMessage = 'No active student record found matching this Admission Number and Father CNIC. Please check and try again.';
            return;
        }

        // Establish Portal Session
        session([
            'student_portal_id' => $student->id,
            'student_portal_cnic' => $student->father_cnic ?: $student->guardian_cnic,
            'student_portal_name' => $student->name,
        ]);

        return redirect()->route('student.dashboard');
    }

    public function render()
    {
        return view('livewire.student.auth.portal-login', [
            'errorMessage' => $this->errorMessage,
        ])->layout('layouts.guest', ['title' => 'Student & Parent Portal Login']);
    }
}
