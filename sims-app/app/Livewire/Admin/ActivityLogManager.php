<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogManager extends Component
{
    use WithPagination;

    public $search = '';
    public $actionFilter = '';
    public $moduleFilter = '';
    public $userFilter = '';
    public $dateFilter = 'all';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    public $selectedLogId = null;
    public $showDiffModal = false;
    public ?ActivityLog $activeLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'moduleFilter' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'dateFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('Super Admin') && !$user->can('security.view-logs') && $user->role !== 'admin')) {
            abort(403, 'Unauthorized access to security audit logs.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function updatingModuleFilter()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'actionFilter', 'moduleFilter', 'userFilter', 'dateFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function viewDiff($id)
    {
        $this->selectedLogId = $id;
        $this->activeLog = ActivityLog::find($id);
        $this->showDiffModal = true;
    }

    public function closeDiff()
    {
        $this->showDiffModal = false;
        $this->activeLog = null;
        $this->selectedLogId = null;
    }

    public function exportCsv()
    {
        $logs = $this->buildQuery()->get();
        $filename = 'activity_audit_logs_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Log ID',
                'Timestamp',
                'User Name',
                'User Email',
                'User Role',
                'Action',
                'Module',
                'Target Entity',
                'Description',
                'Changed Fields',
                'IP Address',
                'HTTP Method',
                'Request URL',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->user_email,
                    $log->user_role,
                    strtoupper($log->action),
                    ucfirst($log->module),
                    $log->subject_label,
                    $log->description,
                    !empty($log->changed_fields) ? implode(', ', (array)$log->changed_fields) : '—',
                    $log->ip_address,
                    $log->method,
                    $log->url,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function purgeOldLogs($days = 90)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            session()->flash('error', 'Only Super Admin can purge audit logs.');
            return;
        }

        $cutoff = Carbon::now()->subDays((int)$days);
        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        \App\Services\ActivityLogger::log(
            action: 'security',
            description: "Super Admin purged {$deleted} audit logs older than {$days} days",
            module: 'security'
        );

        session()->flash('message', "Successfully purged {$deleted} logs older than {$days} days.");
    }

    protected function buildQuery()
    {
        return ActivityLog::query()
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', $term)
                        ->orWhere('user_name', 'like', $term)
                        ->orWhere('user_email', 'like', $term)
                        ->orWhere('subject_label', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhere('model_id', 'like', $term);
                });
            })
            ->when($this->actionFilter, function ($q) {
                $q->where('action', $this->actionFilter);
            })
            ->when($this->moduleFilter, function ($q) {
                $q->where('module', $this->moduleFilter);
            })
            ->when($this->userFilter, function ($q) {
                $q->where('user_id', $this->userFilter);
            })
            ->when($this->dateFilter !== 'all', function ($q) {
                match ($this->dateFilter) {
                    'today' => $q->whereDate('created_at', Carbon::today()),
                    'yesterday' => $q->whereDate('created_at', Carbon::yesterday()),
                    '7days' => $q->where('created_at', '>=', Carbon::now()->subDays(7)),
                    '30days' => $q->where('created_at', '>=', Carbon::now()->subDays(30)),
                    'custom' => $q->when($this->dateFrom, fn($sub) => $sub->whereDate('created_at', '>=', $this->dateFrom))
                                  ->when($this->dateTo, fn($sub) => $sub->whereDate('created_at', '<=', $this->dateTo)),
                    default => null,
                };
            })
            ->latest('id');
    }

    public function render()
    {
        $logs = $this->buildQuery()->paginate($this->perPage);

        $users = User::select('id', 'name', 'role')->orderBy('name')->get();
        $totalLogsCount = ActivityLog::count();
        $todayLogsCount = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $criticalActionsCount = ActivityLog::whereIn('action', ['deleted', 'override', 'admin_override'])->count();
        $uniqueUsersToday = ActivityLog::whereDate('created_at', Carbon::today())->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        return view('livewire.admin.activity-log-manager', [
            'logs' => $logs,
            'users' => $users,
            'totalLogsCount' => $totalLogsCount,
            'todayLogsCount' => $todayLogsCount,
            'criticalActionsCount' => $criticalActionsCount,
            'uniqueUsersToday' => $uniqueUsersToday,
        ])->layout('components.layouts.admin', ['title' => 'Activity Logs & Audit Trail']);
    }
}
