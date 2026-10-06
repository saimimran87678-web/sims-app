<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['class_id', 'name', 'code'];

    protected $appends = ['abbreviation'];

    public function getAbbreviationAttribute(): string
    {
        return self::formatAbbreviation($this->name, $this->code);
    }

    public static function formatAbbreviation(?string $name, ?string $code = null): string
    {
        if (!empty($code)) {
            return strtoupper(trim($code));
        }
        if (empty($name)) {
            return '';
        }
        $trimmed = trim($name);
        $words = preg_split('/[\s\-_]+/', $trimmed);
        if (count($words) > 1) {
            $initials = '';
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= strtoupper($w[0]);
                }
            }
            if (strlen($initials) >= 2 && strlen($initials) <= 5) {
                return $initials;
            }
        }
        return strtoupper(substr($trimmed, 0, 4));
    }



    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }



    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_subjects')
            ->withPivot('subject_type', 'sort_order')
            ->withTimestamps();
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }
}
