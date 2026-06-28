<?php

namespace App\Livewire\Admin\Fee;

use Livewire\Component;

class InvoiceGenerator extends Component
{
    public $classes = [];
    public $selectedClassId = '';
    public $billingMonth = '';
    
    public $selectedTarget = 'all'; // 'all' or specific student_id
    public $students = [];
    public $includeCustom = false; // Overwrite custom students when saving entire class
    
    // Voucher Paper State
    public $baseItems = [];
    public $globalDiscount = '';
    public $studentDiscounts = [];
    public $globalDiscountDesc = '';
    public $globalDiscountCategory = 'monthly';
    public $studentDiscountDescs = [];
    public $studentDiscountCategories = [];
    
    // Binding helpers:
    public $currentDiscountDescInput = '';
    public $currentDiscountCategoryInput = 'monthly';

    public $classBaseItemsBackup = [];
    public $previousTarget = 'all';

    // Institute Info for Display
    public $instituteName = '';

    public $feeHeads = [];
    public $feeHeadList = [];
    public $newFeeHeadName = '';
    public $showManageHeadsModal = false;
    public $activeRowIndexForNewHead = null;
    public $editingFeeHeadId = null;
    public $editingFeeHeadName = '';
    public $activeTab = 'setup';

    // View Vouchers Properties
    public $viewClassId = '';
    public $viewStudentId = 'all';
    public $viewMonth = '';
    public $viewStudents = [];

    // View Receipts Properties
    public $receiptClassId = '';
    public $receiptStudentId = 'all';
    public $receiptMonth = '';
    public $receiptStudents = [];

    public function mount()
    {
        $this->billingMonth = date('Y-m');
        $this->viewMonth = date('Y-m');
        $this->receiptMonth = date('Y-m');
        $this->instituteName = \App\Models\Setting::get('institute_name', 'SIMS Institute');
        
        // Add one empty row by default
        $this->baseItems[] = ['id' => uniqid(), 'name' => '', 'amount' => '', 'category' => 'monthly'];
        
        $this->loadClasses();
        $this->loadFeeHeads();
    }
    
    public function loadFeeHeads()
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        if ($sessionId) {
            $heads = \App\Models\FeeHead::where('academic_session_id', $sessionId)
                ->where('is_active', true)
                ->get();
            
            $this->feeHeads = $heads->pluck('name', 'name')->toArray();
            $this->feeHeadList = $heads->toArray();
        } else {
            $this->feeHeads = [];
            $this->feeHeadList = [];
        }
    }

    public function handleFeeHeadChange($index, $value)
    {
        if ($value === '__manage__') {
            $this->baseItems[$index]['name'] = '';
            
            $this->activeRowIndexForNewHead = $index;
            $this->newFeeHeadName = '';
            $this->editingFeeHeadId = null;
            $this->editingFeeHeadName = '';
            $this->showManageHeadsModal = true;
        }
    }

    public function createFeeHead()
    {
        $this->validate([
            'newFeeHeadName' => 'required|string|max:100',
        ]);

        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        if (!$sessionId) {
            session()->flash('error', 'No active session found.');
            return;
        }

        $head = \App\Models\FeeHead::firstOrCreate(
            ['name' => $this->newFeeHeadName, 'academic_session_id' => $sessionId],
            ['description' => 'Created from Data Entry', 'is_active' => true]
        );

        $this->loadFeeHeads();

        if ($this->activeRowIndexForNewHead !== null && isset($this->baseItems[$this->activeRowIndexForNewHead])) {
            $this->baseItems[$this->activeRowIndexForNewHead]['name'] = $head->name;
        }

        $this->newFeeHeadName = '';
        $this->activeRowIndexForNewHead = null;
        
        session()->flash('message', 'New Payment Head added successfully!');
    }

    public function editFeeHead($id)
    {
        $head = \App\Models\FeeHead::find($id);
        if ($head) {
            $this->editingFeeHeadId = $id;
            $this->editingFeeHeadName = $head->name;
        }
    }

    public function updateFeeHead()
    {
        $this->validate([
            'editingFeeHeadName' => 'required|string|max:100',
        ]);

        if ($this->editingFeeHeadId) {
            $head = \App\Models\FeeHead::find($this->editingFeeHeadId);
            if ($head) {
                $oldName = $head->name;
                $head->update([
                    'name' => $this->editingFeeHeadName,
                ]);

                // Update any baseItems matching oldName to newName
                foreach ($this->baseItems as $idx => $item) {
                    if ($item['name'] === $oldName) {
                        $this->baseItems[$idx]['name'] = $this->editingFeeHeadName;
                    }
                }

                $this->loadFeeHeads();
                session()->flash('message', 'Payment Head updated successfully!');
            }
            $this->editingFeeHeadId = null;
            $this->editingFeeHeadName = '';
        }
    }

    public function deleteFeeHead($id)
    {
        $head = \App\Models\FeeHead::find($id);
        if ($head) {
            $name = $head->name;
            try {
                $head->delete();
                
                // Clear any rows using this name
                foreach ($this->baseItems as $idx => $item) {
                    if ($item['name'] === $name) {
                        $this->baseItems[$idx]['name'] = '';
                    }
                }

                $this->loadFeeHeads();
                session()->flash('message', 'Payment Head deleted successfully!');
            } catch (\Exception $e) {
                // Deactivate if deletion fails due to integrity constraints
                $head->update(['is_active' => false]);
                $this->loadFeeHeads();
                session()->flash('message', 'Payment Head is in use, so it was deactivated instead.');
            }
        }
    }
    
    public function loadClasses()
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        if ($sessionId) {
            $this->classes = \App\Models\Classes::where('academic_session_id', $sessionId)->get();
        }
    }

    public function updatedSelectedClassId()
    {
        $this->selectedTarget = 'all';
        $this->previousTarget = 'all';
        $this->classBaseItemsBackup = [];
        $this->loadStudents();
        $this->loadLastVoucherItems();
    }

    public function updatedBillingMonth()
    {
        $this->previousTarget = 'all';
        $this->classBaseItemsBackup = [];
        $this->loadStudents();
        $this->loadLastVoucherItems();
    }

    public function loadLastVoucherItems()
    {
        if (!$this->selectedClassId) {
            return;
        }

        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        
        // Try to find if we already have records generated for THIS specific class in THIS selected billing month.
        $existingRecord = \App\Models\FeeRecord::where('class_id', $this->selectedClassId)
            ->where('academic_session_id', $sessionId)
            ->where('period', $this->billingMonth)
            ->where(function ($q) {
                $q->where('is_custom', false)->orWhereNull('is_custom');
            })
            ->first();

        if ($existingRecord) {
            $items = \App\Models\FeeRecordItem::where('fee_record_id', $existingRecord->id)
                ->where('fee_head_name', '!=', 'Discount')
                ->get();

            // Load discount details if saved
            $discountItem = \App\Models\FeeRecordItem::where('fee_record_id', $existingRecord->id)
                ->where('fee_head_name', 'Discount')
                ->first();

            if ($discountItem) {
                $this->globalDiscount = abs((float)$discountItem->amount);
                $this->globalDiscountDesc = $discountItem->description ?? '';
                $this->globalDiscountCategory = $discountItem->category ?? 'monthly';
            } else {
                $this->globalDiscount = '';
                $this->globalDiscountDesc = '';
                $this->globalDiscountCategory = 'monthly';
            }

            // Sync with current target bindings if targeted at 'all'
            if ($this->selectedTarget === 'all') {
                $this->currentDiscountInput = $this->globalDiscount;
                $this->currentDiscountDescInput = $this->globalDiscountDesc;
                $this->currentDiscountCategoryInput = $this->globalDiscountCategory;
            }

            if ($items->isNotEmpty()) {
                $this->baseItems = [];
                foreach ($items as $item) {
                    $this->baseItems[] = [
                        'id' => uniqid(),
                        'name' => $item->fee_head_name,
                        'amount' => (float) $item->amount,
                        'category' => $item->category ?? 'monthly',
                    ];
                }
                return;
            }
        } else {
            // No record exists for the selected billing month.
            // Let's check the last month's fee record for this class!
            $lastRecord = \App\Models\FeeRecord::where('class_id', $this->selectedClassId)
                ->where('academic_session_id', $sessionId)
                ->where('period', '<', $this->billingMonth)
                ->where(function ($q) {
                    $q->where('is_custom', false)->orWhereNull('is_custom');
                })
                ->orderBy('period', 'desc')
                ->first();

            if ($lastRecord) {
                // Fetch monthly category items only
                $items = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                    ->where('fee_head_name', '!=', 'Discount')
                    ->where('category', 'monthly')
                    ->get();

                // Load monthly category discount if saved
                $discountItem = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                    ->where('fee_head_name', 'Discount')
                    ->where('category', 'monthly')
                    ->first();

                if ($discountItem) {
                    $this->globalDiscount = abs((float)$discountItem->amount);
                    $this->globalDiscountDesc = $discountItem->description ?? '';
                    $this->globalDiscountCategory = 'monthly';
                } else {
                    $this->globalDiscount = '';
                    $this->globalDiscountDesc = '';
                    $this->globalDiscountCategory = 'monthly';
                }

                if ($this->selectedTarget === 'all') {
                    $this->currentDiscountInput = $this->globalDiscount;
                    $this->currentDiscountDescInput = $this->globalDiscountDesc;
                    $this->currentDiscountCategoryInput = $this->globalDiscountCategory;
                }

                if ($items->isNotEmpty()) {
                    $this->baseItems = [];
                    foreach ($items as $item) {
                        $this->baseItems[] = [
                            'id' => uniqid(),
                            'name' => $item->fee_head_name,
                            'amount' => (float) $item->amount,
                            'category' => 'monthly',
                        ];
                    }
                    return;
                }
            } else {
                // Reset global discount when no record exists for current selection
                $this->globalDiscount = '';
                $this->globalDiscountDesc = '';
                $this->globalDiscountCategory = 'monthly';
                if ($this->selectedTarget === 'all') {
                    $this->currentDiscountInput = '';
                    $this->currentDiscountDescInput = '';
                    $this->currentDiscountCategoryInput = 'monthly';
                }
            }
        }

        // Fallback: if no records exist for the selected billing month, start with an empty editor (single blank row)
        $this->baseItems = [
            ['id' => uniqid(), 'name' => '', 'amount' => '', 'category' => 'monthly']
        ];
    }

    public function loadStudents()
    {
        if (!$this->selectedClassId) {
            $this->students = [];
            return;
        }

        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        $studentRecords = \App\Models\Student::where('class_id', $this->selectedClassId)
            ->where('status', 'active')
            ->get();
        
        $this->students = [];

        foreach ($studentRecords as $student) {
            $arrears = \App\Models\FeeRecord::where('student_id', $student->id)
                ->where('status', '!=', 'paid')
                ->where('period', '<', $this->billingMonth)
                ->sum('balance');

            $studentRecord = \App\Models\FeeRecord::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('period', $this->billingMonth)
                ->first();

            $isCustom = false;
            if ($studentRecord) {
                $isCustom = $studentRecord->is_custom;
                $discountItem = \App\Models\FeeRecordItem::where('fee_record_id', $studentRecord->id)
                    ->where('fee_head_name', 'Discount')
                    ->first();

                if ($discountItem) {
                    $this->studentDiscounts[$student->id] = abs((float)$discountItem->amount);
                    $this->studentDiscountDescs[$student->id] = $discountItem->description ?? '';
                    $this->studentDiscountCategories[$student->id] = $discountItem->category ?? 'monthly';
                } else {
                    $this->studentDiscounts[$student->id] = '';
                    $this->studentDiscountDescs[$student->id] = '';
                    $this->studentDiscountCategories[$student->id] = 'monthly';
                }
            } else {
                $lastRecord = \App\Models\FeeRecord::where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)
                    ->where('period', '<', $this->billingMonth)
                    ->orderBy('period', 'desc')
                    ->first();
                $isCustom = $lastRecord ? $lastRecord->is_custom : false;

                if ($lastRecord) {
                    $discountItem = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                        ->where('fee_head_name', 'Discount')
                        ->where('category', 'monthly')
                        ->first();

                    if ($discountItem) {
                        $this->studentDiscounts[$student->id] = abs((float)$discountItem->amount);
                        $this->studentDiscountDescs[$student->id] = $discountItem->description ?? '';
                        $this->studentDiscountCategories[$student->id] = 'monthly';
                    } else {
                        $this->studentDiscounts[$student->id] = '';
                        $this->studentDiscountDescs[$student->id] = '';
                        $this->studentDiscountCategories[$student->id] = 'monthly';
                    }
                } else {
                    $this->studentDiscounts[$student->id] = '';
                    $this->studentDiscountDescs[$student->id] = '';
                    $this->studentDiscountCategories[$student->id] = 'monthly';
                }
            }

            $this->students[$student->id] = [
                'id' => $student->id,
                'name' => $student->name,
                'roll_no' => $student->roll_no,
                'father_name' => $student->father_name ?? 'N/A',
                'admission_no' => $student->admission_no ?? 'N/A',
                'phone' => $student->phone ?? 'N/A',
                'arrears' => $arrears,
                'is_custom' => (bool) $isCustom,
            ];
        }
    }

    public function addRow()
    {
        $this->baseItems[] = ['id' => uniqid(), 'name' => '', 'amount' => '', 'category' => 'monthly'];
    }

    public function removeRow($index)
    {
        if (count($this->baseItems) > 1) {
            unset($this->baseItems[$index]);
            $this->baseItems = array_values($this->baseItems);
        } else {
            // Clear it if it's the last one
            $this->baseItems[0] = ['id' => uniqid(), 'name' => '', 'amount' => '', 'category' => 'monthly'];
        }
    }

    // Computed Property for Subtotal
    public function getSubtotalProperty()
    {
        $total = 0;
        foreach ($this->baseItems as $item) {
            if (is_numeric($item['amount'])) {
                $total += (float) $item['amount'];
            }
        }
        return $total;
    }

    // Computed Property for Discount applied on current view
    public function getCurrentDiscountProperty()
    {
        if ($this->selectedTarget === 'all') {
            return (float) ($this->globalDiscount ?: 0);
        } else {
            return (float) ($this->studentDiscounts[$this->selectedTarget] ?? $this->globalDiscount ?: 0);
        }
    }

    public function updatedGlobalDiscount($value)
    {
        if ($this->selectedTarget !== 'all') {
            // If they type discount while a student is selected, it only applies to that student
            $this->studentDiscounts[$this->selectedTarget] = $value;
            // Restore global view string so input doesn't bind to global
            $this->globalDiscount = $this->globalDiscount; // keep unchanged
        }
    }

    // Helper to handle the generic binding from the view
    public $currentDiscountInput = '';

    public function updatedSelectedTarget()
    {
        $sessionId = \App\Models\AcademicSession::getActiveSessionId();

        // 1. Backup class template if switching away from 'all'
        if ($this->previousTarget === 'all') {
            $this->classBaseItemsBackup = $this->baseItems;
        }

        // 2. Load target items
        if ($this->selectedTarget === 'all') {
            // Restore class template backup
            if (!empty($this->classBaseItemsBackup)) {
                $this->baseItems = $this->classBaseItemsBackup;
            } else {
                $this->loadLastVoucherItems();
            }
            $this->currentDiscountInput = $this->globalDiscount;
            $this->currentDiscountDescInput = $this->globalDiscountDesc;
            $this->currentDiscountCategoryInput = $this->globalDiscountCategory;
        } else {
            // Load specific student items if they exist in the database for the current period
            $studentId = $this->selectedTarget;
            $record = \App\Models\FeeRecord::where('student_id', $studentId)
                ->where('academic_session_id', $sessionId)
                ->where('period', $this->billingMonth)
                ->first();

            if ($record) {
                $items = \App\Models\FeeRecordItem::where('fee_record_id', $record->id)
                    ->where('fee_head_name', '!=', 'Discount')
                    ->get();

                if ($items->isNotEmpty()) {
                    $this->baseItems = [];
                    foreach ($items as $item) {
                        $this->baseItems[] = [
                            'id' => uniqid(),
                            'name' => $item->fee_head_name,
                            'amount' => (float) $item->amount,
                            'category' => $item->category ?? 'monthly',
                        ];
                    }
                } else {
                    $this->baseItems = $this->classBaseItemsBackup;
                }

                // Load discount details from the database
                $discountItem = \App\Models\FeeRecordItem::where('fee_record_id', $record->id)
                    ->where('fee_head_name', 'Discount')
                    ->first();

                if ($discountItem) {
                    $this->studentDiscounts[$studentId] = abs((float)$discountItem->amount);
                    $this->studentDiscountDescs[$studentId] = $discountItem->description ?? '';
                    $this->studentDiscountCategories[$studentId] = $discountItem->category ?? 'monthly';
                } else {
                    $this->studentDiscounts[$studentId] = '';
                    $this->studentDiscountDescs[$studentId] = '';
                    $this->studentDiscountCategories[$studentId] = 'monthly';
                }
            } else {
                $lastRecord = \App\Models\FeeRecord::where('student_id', $studentId)
                    ->where('academic_session_id', $sessionId)
                    ->where('period', '<', $this->billingMonth)
                    ->orderBy('period', 'desc')
                    ->first();

                if ($lastRecord && $lastRecord->is_custom) {
                    $priorItems = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                        ->where('fee_head_name', '!=', 'Discount')
                        ->where('category', 'monthly')
                        ->get();

                    if ($priorItems->isNotEmpty()) {
                        $this->baseItems = [];
                        foreach ($priorItems as $item) {
                            $this->baseItems[] = [
                                'id' => uniqid(),
                                'name' => $item->fee_head_name,
                                'amount' => (float) $item->amount,
                                'category' => 'monthly',
                            ];
                        }
                    } else {
                        $this->baseItems = $this->classBaseItemsBackup;
                    }
                } else {
                    $this->baseItems = $this->classBaseItemsBackup;
                }
            }

            $this->currentDiscountInput = $this->studentDiscounts[$studentId] ?? '';
            $this->currentDiscountDescInput = $this->studentDiscountDescs[$studentId] ?? '';
            $this->currentDiscountCategoryInput = $this->studentDiscountCategories[$studentId] ?? 'monthly';
        }

        // 3. Update previous target tracker
        $this->previousTarget = $this->selectedTarget;
    }

    public function updatedCurrentDiscountInput($value)
    {
        if ($this->selectedTarget === 'all') {
            $this->globalDiscount = $value;
        } else {
            $this->studentDiscounts[$this->selectedTarget] = $value;
        }
    }

    public function updatedCurrentDiscountDescInput($value)
    {
        if ($this->selectedTarget === 'all') {
            $this->globalDiscountDesc = $value;
        } else {
            $this->studentDiscountDescs[$this->selectedTarget] = $value;
        }
    }

    public function updatedCurrentDiscountCategoryInput($value)
    {
        if ($this->selectedTarget === 'all') {
            $this->globalDiscountCategory = $value;
        } else {
            $this->studentDiscountCategories[$this->selectedTarget] = $value;
        }
    }

    public function generateInvoices()
    {
        if (!$this->selectedClassId) {
            session()->flash('error', 'Please select a class first.');
            return;
        }

        $sessionId = \App\Models\AcademicSession::getActiveSessionId();
        if (!$sessionId) {
            session()->flash('error', 'No active academic session found.');
            return;
        }

        // Validate items
        $validItems = array_filter($this->baseItems, function ($item) {
            return !empty(trim($item['name'] ?? '')) && is_numeric($item['amount']);
        });

        // Fallback: If editor is empty, check for last month's fee items with 'monthly' category
        if (empty($validItems)) {
            $lastRecord = \App\Models\FeeRecord::where('class_id', $this->selectedClassId)
                ->where('academic_session_id', $sessionId)
                ->where('period', '<', $this->billingMonth)
                ->where(function ($q) {
                    $q->where('is_custom', false)->orWhereNull('is_custom');
                })
                ->orderBy('period', 'desc')
                ->first();

            if ($lastRecord) {
                // Fetch monthly category items only
                $items = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                    ->where('fee_head_name', '!=', 'Discount')
                    ->where('category', 'monthly')
                    ->get();

                if ($items->isNotEmpty()) {
                    $validItems = [];
                    foreach ($items as $item) {
                        $validItems[] = [
                            'name' => $item->fee_head_name,
                            'amount' => (float) $item->amount,
                            'category' => 'monthly',
                        ];
                    }
                }
            }
        }

        if (empty($validItems)) {
            session()->flash('error', 'Please enter at least one valid fee item, or ensure a previous month has active monthly fees.');
            return;
        }

        $dueDate = now()->addDays(7);
        $generatedCount = 0;

        $targetStudents = [];
        if ($this->selectedTarget === 'all') {
            foreach ($this->students as $id => $std) {
                $targetStudents[] = $id;
            }
        } else {
            $targetStudents = [$this->selectedTarget];
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($sessionId, $dueDate, $validItems, $targetStudents, &$generatedCount) {
            foreach ($targetStudents as $studentId) {
                $isCustomStudent = $this->students[$studentId]['is_custom'] ?? false;

                // Check if already generated
                $record = \App\Models\FeeRecord::where('student_id', $studentId)
                    ->where('academic_session_id', $sessionId)
                    ->where('period', $this->billingMonth)
                    ->first();

                // Preserve existing custom student record if saving class-wide and includeCustom is false
                if ($record && $record->is_custom && $this->selectedTarget === 'all' && !$this->includeCustom) {
                    continue;
                }

                // Determine fee items for this student
                $studentItems = $validItems;
                $studentSetAsCustom = ($this->selectedTarget !== 'all');

                // If record does not exist and student was custom in prior month, carry forward their prior custom monthly items
                if (!$record && $isCustomStudent && $this->selectedTarget === 'all' && !$this->includeCustom) {
                    $lastRecord = \App\Models\FeeRecord::where('student_id', $studentId)
                        ->where('academic_session_id', $sessionId)
                        ->where('period', '<', $this->billingMonth)
                        ->orderBy('period', 'desc')
                        ->first();

                    if ($lastRecord && $lastRecord->is_custom) {
                        $priorItems = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                            ->where('fee_head_name', '!=', 'Discount')
                            ->where('category', 'monthly')
                            ->get();

                        if ($priorItems->isNotEmpty()) {
                            $studentItems = [];
                            foreach ($priorItems as $item) {
                                $studentItems[] = [
                                    'name' => $item->fee_head_name,
                                    'amount' => (float) $item->amount,
                                    'category' => 'monthly',
                                ];
                            }
                            $studentSetAsCustom = true;
                        }
                    }
                }

                if (empty($studentItems)) {
                    continue;
                }

                // Determine discount for this student
                $studentDiscount = $this->studentDiscounts[$studentId] ?? '';
                $studentDiscountDesc = $this->studentDiscountDescs[$studentId] ?? '';
                $studentDiscountCategory = $this->studentDiscountCategories[$studentId] ?? '';

                // Load monthly discount from last record if carrying forward custom prior items
                if (!$record && $isCustomStudent && $this->selectedTarget === 'all' && !$this->includeCustom) {
                    $lastRecord = \App\Models\FeeRecord::where('student_id', $studentId)
                        ->where('academic_session_id', $sessionId)
                        ->where('period', '<', $this->billingMonth)
                        ->orderBy('period', 'desc')
                        ->first();
                    if ($lastRecord) {
                        $priorDiscount = \App\Models\FeeRecordItem::where('fee_record_id', $lastRecord->id)
                            ->where('fee_head_name', 'Discount')
                            ->where('category', 'monthly')
                            ->first();
                        if ($priorDiscount) {
                            $studentDiscount = abs((float)$priorDiscount->amount);
                            $studentDiscountDesc = $priorDiscount->description ?? '';
                            $studentDiscountCategory = 'monthly';
                        } else {
                            $studentDiscount = '';
                            $studentDiscountDesc = '';
                            $studentDiscountCategory = 'monthly';
                        }
                    }
                }

                $discount = (float) (($studentDiscount !== '') ? $studentDiscount : ($this->globalDiscount ?: 0));
                $discountDesc = ($studentDiscount !== '') ? $studentDiscountDesc : ($this->globalDiscountDesc ?: 'Voucher Discount');
                $discountCategory = ($studentDiscount !== '') ? $studentDiscountCategory : ($this->globalDiscountCategory ?: 'monthly');
                $baseTotal = collect($studentItems)->sum('amount');
                $recordTotal = max(0, $baseTotal - $discount);

                $isNew = false;
                $amountChanged = false;

                if ($record) {
                    // Update existing
                    if ((float)$record->total_amount !== (float)$recordTotal) {
                        $amountChanged = true;
                    }

                    $newBalance = max(0, $recordTotal - $record->paid_amount);
                    $newStatus = $newBalance <= 0 ? 'paid' : ($record->paid_amount > 0 ? 'partial' : 'unpaid');

                    $record->update([
                        'total_amount' => $recordTotal,
                        'balance' => $newBalance,
                        'status' => $newStatus,
                        'is_custom' => $studentSetAsCustom,
                    ]);

                    // Delete existing items
                    \App\Models\FeeRecordItem::where('fee_record_id', $record->id)->delete();
                } else {
                    $isNew = true;
                    // Create new
                    $record = \App\Models\FeeRecord::create([
                        'student_id' => $studentId,
                        'class_id' => $this->selectedClassId,
                        'academic_session_id' => $sessionId,
                        'period' => $this->billingMonth,
                        'cycle' => 'monthly',
                        'total_amount' => $recordTotal,
                        'paid_amount' => 0,
                        'balance' => $recordTotal,
                        'status' => $recordTotal == 0 ? 'paid' : 'unpaid',
                        'due_date' => $dueDate,
                        'is_custom' => $studentSetAsCustom,
                    ]);
                }

                $recordItems = [];
                foreach ($studentItems as $item) {
                    $head = \App\Models\FeeHead::firstOrCreate(
                        ['name' => $item['name'], 'academic_session_id' => $sessionId],
                        ['description' => 'Created from Voucher', 'is_active' => true]
                    );

                    $recordItems[] = [
                        'fee_record_id' => $record->id,
                        'fee_head_id' => $head->id,
                        'fee_head_name' => $item['name'],
                        'amount' => $item['amount'],
                        'description' => null,
                        'category' => $item['category'] ?? 'monthly',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($discount > 0) {
                    $discountHead = \App\Models\FeeHead::firstOrCreate(
                        ['name' => 'Discount', 'academic_session_id' => $sessionId],
                        ['description' => 'Voucher Discount', 'is_active' => true]
                    );
                    $recordItems[] = [
                        'fee_record_id' => $record->id,
                        'fee_head_id' => $discountHead->id,
                        'fee_head_name' => 'Discount',
                        'amount' => -$discount,
                        'description' => $discountDesc,
                        'category' => $discountCategory,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                \App\Models\FeeRecordItem::insert($recordItems);

                $plan = \App\Services\LicenseStatus::getStatus()['plan'] ?? 'basic';
                if ($plan === 'premium') {
                    $invoiceService = app(\App\Services\InvoiceService::class);
                    $invoiceService->generateInvoice($record, true);
                }

                if ($plan !== 'basic') {
                    $student = \App\Models\Student::find($studentId);
                    if ($student && $student->phone) {
                        if ($isNew) {
                            $msg = "Dear Parent, fee voucher for {$this->billingMonth} of Rs. {$recordTotal} has been issued. Due on {$dueDate->format('d M')}.";
                            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert([
                                'phone' => $student->phone,
                                'message' => $msg,
                                'status' => 'pending',
                                'student_id' => $student->id,
                                'priority' => 2,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } elseif ($amountChanged) {
                            $msg = "Dear Parent, fee voucher for {$this->billingMonth} has been updated to Rs. {$recordTotal}. Due on {$dueDate->format('d M')}.";
                            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert([
                                'phone' => $student->phone,
                                'message' => $msg,
                                'status' => 'pending',
                                'student_id' => $student->id,
                                'priority' => 2,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                $generatedCount++;
            }
        });

        session()->flash('message', "Successfully saved and processed {$generatedCount} vouchers.");
        $this->loadStudents(); // Refresh arrears
        $this->loadLastVoucherItems(); // Refresh editor items view
    }

    public function updatedViewClassId($value)
    {
        $this->viewStudentId = 'all';
        if ($value) {
            $this->viewStudents = \App\Models\Student::where('class_id', $value)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(function ($std) {
                    return [$std->id => $std->first_name . ' ' . $std->last_name . ' (' . ($std->roll_number ?? 'N/A') . ')'];
                })->toArray();
        } else {
            $this->viewStudents = [];
        }
    }

    public function updatedReceiptClassId($value)
    {
        $this->receiptStudentId = 'all';
        if ($value) {
            $this->receiptStudents = \App\Models\Student::where('class_id', $value)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(function ($std) {
                    return [$std->id => $std->first_name . ' ' . $std->last_name . ' (' . ($std->roll_number ?? 'N/A') . ')'];
                })->toArray();
        } else {
            $this->receiptStudents = [];
        }
    }

    public function getVouchersProperty()
    {
        if (!$this->viewClassId) {
            return collect();
        }

        $query = \App\Models\FeeRecord::with(['student', 'class', 'items'])
            ->where('class_id', $this->viewClassId)
            ->where('academic_session_id', \App\Models\AcademicSession::getActiveSessionId());

        if ($this->viewMonth) {
            $query->where('period', $this->viewMonth);
        }

        if ($this->viewStudentId && $this->viewStudentId !== 'all') {
            $query->where('student_id', $this->viewStudentId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getReceiptsProperty()
    {
        if (!$this->receiptClassId) {
            return collect();
        }

        $query = \App\Models\FeePayment::with(['student', 'record', 'record.class'])
            ->whereHas('record', function ($q) {
                $q->where('class_id', $this->receiptClassId)
                  ->where('academic_session_id', \App\Models\AcademicSession::getActiveSessionId());
            });

        if ($this->receiptMonth) {
            $query->whereHas('record', function ($q) {
                $q->where('period', $this->receiptMonth);
            });
        }

        if ($this->receiptStudentId && $this->receiptStudentId !== 'all') {
            $query->where('student_id', $this->receiptStudentId);
        }

        return $query->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc')->get();
    }

    public function sendVoucherWhatsApp($recordId)
    {
        $record = \App\Models\FeeRecord::with(['student', 'class', 'items'])->find($recordId);
        if (!$record) {
            session()->flash('error', 'Voucher not found.');
            return;
        }

        $student = $record->student;
        if (!$student || empty($student->phone)) {
            session()->flash('error', 'Parent phone number not configured for this student.');
            return;
        }

        $whatsapp = app(\App\Services\WhatsAppService::class);
        
        // 1. Check if WhatsApp service is connected
        if (!$whatsapp->isConnected()) {
            // Queue a text notification instead
            $formattedPeriod = \Carbon\Carbon::parse($record->period . '-01')->format('F Y');
            $dueDate = $record->due_date->format('d M, Y');
            $message = \App\Helpers\PhoneHelper::getFeeReminderMessage(
                $student->name,
                $record->balance,
                $formattedPeriod,
                $dueDate
            );

            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert([
                'phone' => $student->phone,
                'message' => $message,
                'status' => 'pending',
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            session()->flash('message', 'WhatsApp service is offline. A plain-text fee reminder has been queued.');
            return;
        }

        // 2. Generate PDF voucher using our helper
        try {
            $invoiceService = app(\App\Services\InvoiceService::class);
            $filePath = $invoiceService->generateInvoice($record, true);

            if (!file_exists($filePath)) {
                session()->flash('error', 'Failed to generate PDF voucher file.');
                return;
            }

            // 3. Send media message
            $formattedPeriod = \Carbon\Carbon::parse($record->period . '-01')->format('F Y');
            $caption = "Dear Parent, please find attached the fee voucher for {$student->name} for the month of {$formattedPeriod}. Total payable: Rs. {$record->balance}. Due Date: {$record->due_date->format('d M, Y')}.";
            
            $result = $whatsapp->sendMediaMessage($student->phone, $caption, $filePath);

            if ($result['success'] ?? false) {
                // Log notification
                \Illuminate\Support\Facades\DB::table('whatsapp_notifications')->insert([
                    'student_id' => $student->id,
                    'date' => now()->format('Y-m-d'),
                    'type' => 'voucher',
                    'sent' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                session()->flash('message', "Voucher PDF sent to parent's WhatsApp successfully!");
            } else {
                session()->flash('error', 'WhatsApp Service Error: ' . ($result['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp Media Send Error: ' . $e->getMessage());
            session()->flash('error', 'Failed to send PDF: ' . $e->getMessage());
        }
    }

    public function sendReceiptWhatsApp($paymentId)
    {
        $payment = \App\Models\FeePayment::with(['student', 'record'])->find($paymentId);
        if (!$payment) {
            session()->flash('error', 'Payment receipt not found.');
            return;
        }

        $student = $payment->student;
        if (!$student || empty($student->phone)) {
            session()->flash('error', 'Parent phone number not configured for this student.');
            return;
        }

        $whatsapp = app(\App\Services\WhatsAppService::class);
        
        // 1. Check if WhatsApp service is connected
        if (!$whatsapp->isConnected()) {
            // Queue a text notification instead
            $formattedPeriod = \Carbon\Carbon::parse($payment->record->period . '-01')->format('F Y');
            $message = \App\Helpers\PhoneHelper::getPaymentMessage(
                $student->name,
                $payment->amount_paid,
                $formattedPeriod,
                $payment->record->balance
            );

            \Illuminate\Support\Facades\DB::table('whatsapp_queue')->insert([
                'phone' => $student->phone,
                'message' => $message,
                'status' => 'pending',
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            session()->flash('message', 'WhatsApp service is offline. A plain-text payment confirmation has been queued.');
            return;
        }

        // 2. Generate PDF receipt
        try {
            $filePath = $this->generateReceiptPdfFile($payment);

            if (!$filePath || !file_exists($filePath)) {
                session()->flash('error', 'Failed to generate PDF receipt file.');
                return;
            }

            // 3. Send media message
            $formattedPeriod = \Carbon\Carbon::parse($payment->record->period . '-01')->format('F Y');
            $caption = "Dear Parent, please find attached the payment receipt for {$student->name} for the month of {$formattedPeriod}. Amount received: Rs. {$payment->amount_paid}. Remaining balance: Rs. {$payment->record->balance}. Thank you!";
            
            $result = $whatsapp->sendMediaMessage($student->phone, $caption, $filePath);

            if ($result['success'] ?? false) {
                // Log notification
                \Illuminate\Support\Facades\DB::table('whatsapp_notifications')->insert([
                    'student_id' => $student->id,
                    'date' => now()->format('Y-m-d'),
                    'type' => 'receipt',
                    'sent' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                session()->flash('message', "Receipt PDF sent to parent's WhatsApp successfully!");
            } else {
                session()->flash('error', 'WhatsApp Service Error: ' . ($result['error'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp Receipt Media Send Error: ' . $e->getMessage());
            session()->flash('error', 'Failed to send PDF receipt: ' . $e->getMessage());
        }
    }

    protected function generateReceiptPdfFile($payment)
    {
        $payment->load(['student', 'record', 'record.class', 'record.items']);
        $instituteName = \App\Models\Setting::get('institute_name', 'SIMS');
        $instituteAddress = \App\Models\Setting::get('institute_address', '');
        $institutePhone = \App\Models\Setting::get('institute_phone', '');
        $instituteEmail = \App\Models\Setting::get('institute_email', '');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fee-receipt', [
            'payment' => $payment,
            'record' => $payment->record,
            'student' => $payment->student,
            'instituteName' => $instituteName,
            'instituteAddress' => $instituteAddress,
            'institutePhone' => $institutePhone,
            'instituteEmail' => $instituteEmail,
        ]);

        $fileName = "receipts/REC-{$payment->id}.pdf";
        \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $pdf->output());
        return storage_path("app/public/{$fileName}");
    }

    public function render()
    {
        return view('livewire.admin.fee.invoice-generator')->layout('components.layouts.admin', ['title' => 'Voucher Management']);
    }
}
