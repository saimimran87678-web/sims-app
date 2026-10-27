<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    protected const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'token',
        'secret',
        'api_key',
        'app_key',
    ];

    /**
     * Record a system or user activity log.
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $module = null,
        ?User $user = null,
        ?string $subjectLabel = null
    ): ?ActivityLog {
        try {
            $user = $user ?: Auth::user();

            $cleanOld = self::sanitizeAttributes($oldValues);
            $cleanNew = self::sanitizeAttributes($newValues);

            $changedFields = [];
            if ($cleanOld !== null && $cleanNew !== null) {
                foreach ($cleanNew as $k => $v) {
                    if (!array_key_exists($k, $cleanOld) || $cleanOld[$k] !== $v) {
                        $changedFields[] = $k;
                    }
                }
                foreach ($cleanOld as $k => $v) {
                    if (!array_key_exists($k, $cleanNew) && !in_array($k, $changedFields, true)) {
                        $changedFields[] = $k;
                    }
                }
            } elseif ($cleanNew !== null) {
                $changedFields = array_keys($cleanNew);
            } elseif ($cleanOld !== null) {
                $changedFields = array_keys($cleanOld);
            }

            // Derive module if omitted
            if (empty($module)) {
                $module = self::resolveModuleFromModel($model);
            }

            // Derive subject label if omitted
            if (empty($subjectLabel) && $model) {
                $subjectLabel = self::resolveSubjectLabel($model);
            }

            $userRole = 'System';
            if ($user) {
                if (method_exists($user, 'getRoleNames') && $user->getRoleNames()->isNotEmpty()) {
                    $userRole = $user->getRoleNames()->first();
                } else {
                    $userRole = ucfirst((string)($user->role ?? 'User'));
                }

                $isSharedRoute = request() && (
                    str_contains(request()->path(), 'shared') ||
                    (method_exists(request(), 'routeIs') && request()->routeIs('*shared*'))
                );
                if ($isSharedRoute && in_array(strtolower($userRole), ['teacher', 'staff'], true)) {
                    $userRole = 'Teacher (Shared Access)';
                }
            }

            $req = request();
            $ip = $req ? $req->ip() : null;
            $userAgent = $req ? substr((string)$req->userAgent(), 0, 500) : null;
            $url = $req ? substr((string)$req->fullUrl(), 0, 500) : null;
            $method = $req ? $req->method() : null;

            $activeSessionId = null;
            try {
                $activeSessionId = AcademicSession::getActiveSessionId();
            } catch (\Throwable) {}

            $shiftType = session('selected_shift_type', 'morning');

            return ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'user_email' => $user?->email,
                'user_role' => $userRole,
                'action' => strtolower($action),
                'module' => strtolower($module),
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? (string)$model->getKey() : null,
                'subject_label' => $subjectLabel,
                'description' => $description,
                'old_values' => $cleanOld,
                'new_values' => $cleanNew,
                'changed_fields' => $changedFields,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'url' => $url,
                'method' => $method,
                'academic_session_id' => $activeSessionId,
                'shift_type' => $shiftType,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write activity log: ' . $e->getMessage(), [
                'action' => $action,
                'description' => $description,
            ]);
            return null;
        }
    }

    /**
     * Helper to log authentication events
     */
    public static function logAuth(string $action, User $user, string $description): ?ActivityLog
    {
        return self::log(
            action: $action,
            description: $description,
            model: $user,
            module: 'auth',
            user: $user,
            subjectLabel: "User: {$user->name} ({$user->email})"
        );
    }

    /**
     * Helper to log admin overrides (e.g. in timetable/substitution)
     */
    public static function logOverride(string $description, ?Model $model = null, array $context = []): ?ActivityLog
    {
        return self::log(
            action: 'override',
            description: $description,
            model: $model,
            newValues: $context,
            module: 'substitutions'
        );
    }

    /**
     * Helper to log schedule and report printing
     */
    public static function logPrint(string $type, string $description, array $details = []): ?ActivityLog
    {
        return self::log(
            action: 'printed',
            description: $description,
            module: 'schedule',
            subjectLabel: "Print: {$type}",
            newValues: array_merge(['print_type' => $type], $details)
        );
    }

    /**
     * Sanitize sensitive attributes before logging
     */
    protected static function sanitizeAttributes(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = [];
        foreach ($data as $key => $val) {
            if (in_array(strtolower((string)$key), self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '••••••••';
            } elseif (is_array($val)) {
                $sanitized[$key] = self::sanitizeAttributes($val);
            } else {
                $sanitized[$key] = $val;
            }
        }

        return $sanitized;
    }

    /**
     * Map model class to module name
     */
    public static function resolveModuleFromModel(?Model $model): string
    {
        if (!$model) {
            return 'general';
        }

        $class = class_basename($model);
        return match ($class) {
            'Student', 'Enrollment' => 'students',
            'User' => 'users',
            'Classes', 'Subject', 'AcademicSession' => 'academics',
            'Timetable', 'PeriodConfig' => 'schedule',
            'Attendance', 'TeacherAttendance' => 'attendance',
            'Substitution', 'ClosedClassroom', 'DailyClassMerge' => 'substitutions',
            'FeePayment', 'FeeRecord', 'FeeStructure', 'FeeChallan' => 'fees',
            'Exam', 'Grade', 'Datesheet', 'ExamMark' => 'grades',
            'Setting' => 'settings',
            default => 'general',
        };
    }

    /**
     * Resolve a readable subject label for the model
     */
    public static function resolveSubjectLabel(Model $model): string
    {
        $class = class_basename($model);

        if (method_exists($model, 'getActivitySubjectLabel')) {
            return $model->getActivitySubjectLabel();
        }

        $identifier = $model->name ?? ($model->title ?? ($model->label ?? ($model->code ?? '#' . $model->getKey())));
        return "{$class}: {$identifier}";
    }
}
