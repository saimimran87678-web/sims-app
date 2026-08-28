<?php

namespace App\Livewire\Admin\Fee;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\FeeRecord;
use App\Models\Classes;
use App\Models\Student;
use App\Models\AcademicSession;
use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\DB;

class DefaulterList extends Component
{
    use WithPagination;

    public $filter_class = '';
    public $min_due = 1;

    protected $paginationTheme = 'tailwind';

    public function updatingFilterClass()
    {
        $this->resetPage();
    }

    public function updatingMinDue()
    {
        $this->resetPage();
    }

    private function getDefaultersQuery()
    {
        $sessionId = AcademicSession::getActiveSessionId();
        $sessionObj = AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $minDueVal = (float)($this->min_due !== '' && $this->min_due !== null ? $this->min_due : 0);

        $query = FeeRecord::with(['student', 'class'])
            ->whereHas('class', function ($q) use ($shiftType) {
                $q->where('shift_type', $shiftType);
            })
            ->where('academic_session_id', $sessionId)
            ->where('balance', '>=', $minDueVal)
            ->where('period', '<=', now()->format('Y-m'))
            ->select('student_id', 'class_id', DB::raw('SUM(balance) as total_due'), DB::raw('COUNT(id) as unpaid_bills'))
            ->groupBy('student_id', 'class_id');

        if ($this->filter_class) {
            $query->where('class_id', $this->filter_class);
        }

        return $query->orderBy('total_due', 'desc');
    }

    /**
     * Send personalized fee reminder to a single student's parent/guardian.
     */
    public function sendSingleReminder($studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            session()->flash('error', 'Student not found.');
            return;
        }

        if (empty($student->phone)) {
            session()->flash('error', "No contact phone number registered for {$student->name}.");
            return;
        }

        $latestFeeRecord = FeeRecord::where('student_id', $studentId)
            ->where('balance', '>', 0)
            ->orderBy('period', 'desc')
            ->first();

        $totalDue = FeeRecord::where('student_id', $studentId)
            ->where('balance', '>', 0)
            ->sum('balance');

        $period = $latestFeeRecord ? \Carbon\Carbon::parse($latestFeeRecord->period . '-01')->format('F Y') : now()->format('F Y');
        $dueDate = ($latestFeeRecord && $latestFeeRecord->due_date) ? $latestFeeRecord->due_date->format('d M, Y') : now()->addDays(7)->format('d M, Y');
        $voucherUrl = ($latestFeeRecord && $latestFeeRecord->access_token)
            ? route('public.voucher.show', $latestFeeRecord->access_token)
            : url('/admin/fee/ledger/' . $studentId);

        $message = PhoneHelper::getFeeReminderMessage(
            $student->name,
            number_format($totalDue, 2),
            $period,
            $dueDate,
            \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2 ISLAMABAD'),
            $voucherUrl,
            $student->id
        );

        DB::table('whatsapp_queue')->insert([
            'phone' => $student->phone,
            'message' => $message,
            'status' => 'pending',
            'student_id' => $student->id,
            'priority' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->flash('message', "Friendly fee reminder queued for {$student->name} ({$student->phone}).");
    }

    /**
     * Send bulk personalized fee reminders to all filtered defaulter parents.
     */
    public function sendBulkReminders()
    {
        $defaulters = $this->getDefaultersQuery()->get();
        if ($defaulters->isEmpty()) {
            session()->flash('error', 'No defaulters found to send notifications.');
            return;
        }

        $queuedCount = 0;
        $now = now();
        $queueRecords = [];

        foreach ($defaulters as $def) {
            $student = $def->student;
            if (!$student || empty($student->phone)) {
                continue;
            }

            $latestFeeRecord = FeeRecord::where('student_id', $def->student_id)
                ->where('balance', '>', 0)
                ->orderBy('period', 'desc')
                ->first();

            $period = $latestFeeRecord ? \Carbon\Carbon::parse($latestFeeRecord->period . '-01')->format('F Y') : now()->format('F Y');
            $dueDate = ($latestFeeRecord && $latestFeeRecord->due_date) ? $latestFeeRecord->due_date->format('d M, Y') : now()->addDays(7)->format('d M, Y');
            $voucherUrl = ($latestFeeRecord && $latestFeeRecord->access_token)
                ? route('public.voucher.show', $latestFeeRecord->access_token)
                : url('/admin/fee/ledger/' . $def->student_id);

            $message = PhoneHelper::getFeeReminderMessage(
                $student->name,
                number_format($def->total_due, 2),
                $period,
                $dueDate,
                \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2 ISLAMABAD'),
                $voucherUrl,
                $student->id
            );

            $queueRecords[] = [
                'phone' => $student->phone,
                'message' => $message,
                'status' => 'pending',
                'student_id' => $student->id,
                'priority' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $queuedCount++;
        }

        if (!empty($queueRecords)) {
            DB::table('whatsapp_queue')->insert($queueRecords);
        }

        session()->flash('message', "Bulk fee reminders successfully queued for {$queuedCount} defaulter student parents.");
    }

    /**
     * Native Excel (.xls) file export with Excel XML/HTML formatting, cell styles & gridlines.
     */
    public function exportExcel()
    {
        $sessionId = AcademicSession::getActiveSessionId();
        $sessionObj = AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $className = 'All_Classes';
        if ($this->filter_class) {
            $c = Classes::withoutGlobalScope('active_session')->find($this->filter_class);
            if ($c) {
                $className = \Illuminate\Support\Str::slug($c->name);
            }
        }

        $fileName = 'Defaulter_List_' . $className . '_' . ucfirst($shiftType) . '_' . date('Y-m-d') . '.xls';
        $defaulters = $this->getDefaultersQuery()->get();
        $totalDueAggregate = $defaulters->sum('total_due');
        $selectedSessionName = $sessionObj ? $sessionObj->name : 'Active Session';
        $instituteName = \App\Models\Setting::getGlobal('institute_name', 'IMCB G-6/2 ISLAMABAD');

        return response()->streamDownload(function () use ($defaulters, $shiftType, $totalDueAggregate, $selectedSessionName, $instituteName) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Defaulter List</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'table { border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 11pt; }';
            echo 'th { background-color: #1e293b; color: #ffffff; font-weight: bold; border: 1px solid #0f172a; padding: 8px; text-align: left; }';
            echo 'td { border: 1px solid #cbd5e1; padding: 6px; }';
            echo '.num { mso-number-format:"\#\,\#\#0\.00"; text-align: right; }';
            echo '.center { text-align: center; }';
            echo '.title { font-size: 16pt; font-weight: bold; color: #1e3a8a; }';
            echo '.sub-title { font-size: 10pt; color: #475569; font-weight: bold; }';
            echo '.total-row { background-color: #fee2e2; font-weight: bold; font-size: 11pt; }';
            echo '</style>';
            echo '</head><body>';

            echo '<table>';
            echo '<tr><td colspan="9" class="title">' . htmlspecialchars($instituteName) . ' - DEFAULTERS LIST REPORT</td></tr>';
            echo '<tr><td colspan="9" class="sub-title">Session: ' . htmlspecialchars($selectedSessionName) . ' | Shift: ' . ucfirst($shiftType) . ' | Generated: ' . date('d-M-Y h:i A') . '</td></tr>';
            echo '<tr><td colspan="9"></td></tr>';

            echo '<thead><tr>';
            echo '<th>S.No</th>';
            echo '<th>Admission No</th>';
            echo '<th>Roll No</th>';
            echo '<th>Student Name</th>';
            echo '<th>Father Name</th>';
            echo '<th>Class</th>';
            echo '<th>Contact / Phone</th>';
            echo '<th class="center">Unpaid Bills</th>';
            echo '<th class="num">Total Due (Rs.)</th>';
            echo '</tr></thead>';

            echo '<tbody>';
            $index = 1;
            foreach ($defaulters as $def) {
                $student = $def->student;
                echo '<tr>';
                echo '<td class="center">' . $index++ . '</td>';
                echo '<td>' . htmlspecialchars($student ? $student->admission_no : 'N/A') . '</td>';
                echo '<td class="center">' . htmlspecialchars($student ? ($student->roll_no ?? '-') : '-') . '</td>';
                echo '<td><b>' . htmlspecialchars($student ? $student->name : 'N/A') . '</b></td>';
                echo '<td>' . htmlspecialchars($student ? ($student->father_name ?? '-') : '-') . '</td>';
                echo '<td>' . htmlspecialchars($def->class ? $def->class->name : 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($student ? ($student->phone ?? 'N/A') : 'N/A') . '</td>';
                echo '<td class="center">' . $def->unpaid_bills . '</td>';
                echo '<td class="num">' . number_format($def->total_due, 2, '.', '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody>';

            echo '<tfoot>';
            echo '<tr class="total-row">';
            echo '<td colspan="8" style="text-align: right; font-weight: bold;">TOTAL OUTSTANDING BALANCE DUE:</td>';
            echo '<td class="num" style="font-weight: bold; color: #991b1b;">' . number_format($totalDueAggregate, 2, '.', '') . '</td>';
            echo '</tr>';
            echo '</tfoot>';

            echo '</table></body></html>';
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * CSV file export for lightweight reporting.
     */
    public function exportCsv()
    {
        $sessionId = AcademicSession::getActiveSessionId();
        $sessionObj = AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $className = 'All_Classes';
        if ($this->filter_class) {
            $c = Classes::withoutGlobalScope('active_session')->find($this->filter_class);
            if ($c) {
                $className = \Illuminate\Support\Str::slug($c->name);
            }
        }

        $fileName = 'Defaulters_' . $className . '_' . ucfirst($shiftType) . '_' . date('Y-m-d') . '.csv';
        $defaulters = $this->getDefaultersQuery()->get();

        return response()->streamDownload(function () use ($defaulters, $shiftType) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel CSV compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'S.No',
                'Admission No',
                'Roll No',
                'Student Name',
                'Father Name',
                'Class',
                'Shift',
                'Contact Phone',
                'Unpaid Bills',
                'Total Due (Rs)'
            ]);

            $index = 1;
            foreach ($defaulters as $def) {
                $student = $def->student;
                fputcsv($handle, [
                    $index++,
                    $student ? $student->admission_no : 'N/A',
                    $student ? ($student->roll_no ?? '-') : '-',
                    $student ? $student->name : 'N/A',
                    $student ? ($student->father_name ?? '-') : '-',
                    $def->class ? $def->class->name : 'N/A',
                    ucfirst($shiftType),
                    $student ? ($student->phone ?? 'N/A') : 'N/A',
                    $def->unpaid_bills,
                    number_format($def->total_due, 2, '.', '')
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function render()
    {
        $sessionId = AcademicSession::getActiveSessionId();
        $sessionObj = AcademicSession::find($sessionId);
        $isRegular = ($sessionObj && $sessionObj->shift_type === 'Regular');
        $shiftType = $isRegular ? 'regular' : session('selected_shift_type', 'morning');
        if ($shiftType === 'both') {
            $shiftType = 'morning';
        }

        $query = $this->getDefaultersQuery();

        // Paginated for web UI
        $defaulters = (clone $query)->paginate(15);

        // Unpaginated full list for print view & aggregate stats
        $allDefaulters = $query->get();
        $totalDueAggregate = $allDefaulters->sum('total_due');

        $layout = request()->is('teacher/*')
            ? 'components.layouts.teacher'
            : 'components.layouts.admin';

        $selectedClassName = 'All Classes';
        if ($this->filter_class) {
            $c = Classes::withoutGlobalScope('active_session')->find($this->filter_class);
            if ($c) {
                $selectedClassName = $c->name;
            }
        }

        return view('livewire.admin.fee.defaulter-list', [
            'defaulters' => $defaulters,
            'allDefaulters' => $allDefaulters,
            'classes' => Classes::withoutGlobalScope('active_session')
                ->where('academic_session_id', $sessionId)
                ->where('shift_type', $shiftType)
                ->orderBy('numeric_value')
                ->get(),
            'totalDefaulters' => $allDefaulters->count(),
            'totalDueAggregate' => $totalDueAggregate,
            'selectedSessionName' => $sessionObj ? $sessionObj->name : 'Active Session',
            'currentShift' => ucfirst($shiftType),
            'selectedClassName' => $selectedClassName,
        ])->layout($layout);
    }
}
