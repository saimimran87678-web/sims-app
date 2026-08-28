<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use App\Models\Classes;
use App\Models\AcademicSession;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

class StudentImportManager extends Component
{
    use WithFileUploads;

    public $file;
    public $previewRows = [];
    public $importErrors = [];
    public $selectedSessionId;
    public $showPreview = false;

    public function mount()
    {
        $this->selectedSessionId = AcademicSession::getActiveSessionId();
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $path = $this->file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->addError('file', 'Unable to open file.');
            return;
        }

        // Parse CSV headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->addError('file', 'Empty or invalid CSV file.');
            fclose($handle);
            return;
        }

        // Normalize headers
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $header)));
            $headerMap[$normalized] = $index;
        }

        // Identify fields
        $fields = [
            'name' => ['name', 'student name', 'student_name', 'full name'],
            'father_name' => ['father name', 'father_name', 'father'],
            'class_name' => ['class', 'class name', 'class_name', 'grade'],
            'admission_no' => ['admission no', 'admission_no', 'admission number', 'reg no'],
            'roll_no' => ['roll no', 'roll_no', 'roll number', 'roll'],
            'phone' => ['phone', 'phone no', 'phone number', 'contact'],
            'gender' => ['gender', 'sex'],
            'shift_type' => ['shift', 'shift type', 'shift_type'],
        ];

        $mappedIndexes = [];
        foreach ($fields as $field => $matches) {
            foreach ($matches as $match) {
                if (isset($headerMap[$match])) {
                    $mappedIndexes[$field] = $headerMap[$match];
                    break;
                }
            }
        }

        // Ensure minimum required columns exist
        if (!isset($mappedIndexes['name'])) {
            $this->addError('file', 'Required column "Name" not found in CSV. Expected header names: Name, Student Name.');
            fclose($handle);
            return;
        }
        if (!isset($mappedIndexes['class_name'])) {
            $this->addError('file', 'Required column "Class Name" not found in CSV. Expected header names: Class, Class Name.');
            fclose($handle);
            return;
        }

        $this->previewRows = [];
        $this->importErrors = [];
        $rowNum = 1;

        // Fetch all classes for validation mapping
        $classesList = Classes::withoutGlobalScope('active_session')
            ->where('academic_session_id', $this->selectedSessionId)
            ->get();

        // Normalize class names and shifts keys
        $normalizedClasses = [];
        foreach ($classesList as $c) {
            $key = strtolower(trim($c->name)) . '_' . strtolower($c->shift_type);
            $normalizedClasses[$key] = $c->id;
        }

        // Fetch existing admission numbers to detect duplicates
        $existingAdmissions = Student::pluck('admission_no')->toArray();
        $seenAdmissions = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Extract row values
            $name = isset($mappedIndexes['name']) ? trim($row[$mappedIndexes['name']] ?? '') : '';
            $fatherName = isset($mappedIndexes['father_name']) ? trim($row[$mappedIndexes['father_name']] ?? '') : '';
            $classNameInput = isset($mappedIndexes['class_name']) ? trim($row[$mappedIndexes['class_name']] ?? '') : '';
            $admissionNoInput = isset($mappedIndexes['admission_no']) ? trim($row[$mappedIndexes['admission_no']] ?? '') : '';
            $rollNoInput = isset($mappedIndexes['roll_no']) ? trim($row[$mappedIndexes['roll_no']] ?? '') : '';
            $phone = isset($mappedIndexes['phone']) ? trim($row[$mappedIndexes['phone']] ?? '') : '';
            $gender = isset($mappedIndexes['gender']) ? trim($row[$mappedIndexes['gender']] ?? '') : 'Male';
            $shiftTypeInput = isset($mappedIndexes['shift_type']) ? trim($row[$mappedIndexes['shift_type']] ?? '') : 'morning';

            // Normalize shift
            $shiftType = 'morning';
            $testShift = strtolower($shiftTypeInput);
            if (str_contains($testShift, 'eve')) {
                $shiftType = 'evening';
            } elseif (str_contains($testShift, 'reg')) {
                $shiftType = 'regular';
            } else {
                $sessionObj = \App\Models\AcademicSession::find($this->selectedSessionId);
                $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
                $shiftType = $isRegular ? 'regular' : 'morning';
            }

            // Validations
            $errors = [];
            if (empty($name)) {
                $errors[] = "Name is required.";
            }

            // Match class
            $matchedClassId = null;
            if (empty($classNameInput)) {
                $errors[] = "Class Name is required.";
            } else {
                $searchClassName = strtolower($classNameInput);
                if (!str_starts_with($searchClassName, 'class ')) {
                    $searchClassName = 'class ' . $searchClassName;
                }
                $key = $searchClassName . '_' . strtolower($shiftType);
                if (isset($normalizedClasses[$key])) {
                    $matchedClassId = $normalizedClasses[$key];
                } else {
                    $errors[] = "Class '{$classNameInput}' for shift '{$shiftType}' not found.";
                }
            }

            // Generate unique admission number if empty
            if (empty($admissionNoInput)) {
                $admissionNoInput = 'ADM-' . mt_rand(100000, 999999);
            }

            if (in_array($admissionNoInput, $existingAdmissions) || in_array($admissionNoInput, $seenAdmissions)) {
                $errors[] = "Admission No '{$admissionNoInput}' is a duplicate.";
            } else {
                $seenAdmissions[] = $admissionNoInput;
            }

            $this->previewRows[] = [
                'row_num' => $rowNum,
                'name' => $name,
                'father_name' => $fatherName,
                'class_name_input' => $classNameInput,
                'class_id' => $matchedClassId,
                'admission_no' => $admissionNoInput,
                'roll_no' => $rollNoInput,
                'phone' => $phone,
                'gender' => ucfirst(strtolower($gender)),
                'shift_type' => $shiftType,
                'errors' => $errors,
                'is_valid' => empty($errors),
            ];
        }

        fclose($handle);
        $this->showPreview = true;
    }

    public function import()
    {
        $validRows = array_filter($this->previewRows, fn($r) => $r['is_valid']);
        if (empty($validRows)) {
            session()->flash('error', 'No valid rows to import.');
            return;
        }

        DB::transaction(function () use ($validRows) {
            foreach ($validRows as $row) {
                // 1. Create Student
                $student = Student::create([
                    'name' => $row['name'],
                    'admission_no' => $row['admission_no'],
                    'father_name' => $row['father_name'],
                    'phone' => $row['phone'],
                    'gender' => strtolower($row['gender']),
                    'status' => 'active',
                ]);



                // 3. Create Enrollment
                DB::table('enrollments')->insert([
                    'student_id' => $student->id,
                    'class_id' => $row['class_id'],
                    'academic_session_id' => $this->selectedSessionId,
                    'shift_type' => $row['shift_type'],
                    'roll_number' => $row['roll_no'] ?: null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        session()->flash('message', count($validRows) . ' student(s) imported successfully!');
        $this->reset(['file', 'previewRows', 'showPreview']);
    }

    public function cancel()
    {
        $this->reset(['file', 'previewRows', 'showPreview']);
    }

    public function render()
    {
        $sessions = AcademicSession::orderBy('start_date', 'desc')->get();
        
        $layout = request()->is('teacher/*')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        return view('livewire.admin.student-import', [
            'sessions' => $sessions
        ])->layout($layout, ['title' => 'Bulk Import Students']);
    }
}
