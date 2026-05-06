<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vw_PatientSummary extends Model
{
    use HasFactory;
    protected $table = 'vw_PatientSummary';
    protected $primaryKey = false;
    public $incrementing = false; // false if LocationCode is not auto-increment
    public $timestamps = false;
}
