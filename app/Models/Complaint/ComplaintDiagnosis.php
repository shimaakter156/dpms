<?php

namespace App\Models\Complaint;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintDiagnosis extends Model
{
    use HasFactory;
    protected $table = 'tbl_ComplaintDiagnosis';
    protected $primaryKey = 'ComplaintDiagnosisID';
    public $incrementing = true; // false if LocationCode is not auto-increment
    public $timestamps = false;
}
