<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    use HasFactory;
    protected $table = 'tbl_PatientMedicalHistory';
    protected $primaryKey = 'HistoryID';
    public $incrementing = true;
    public $timestamps = false;
}
