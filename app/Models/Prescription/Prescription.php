<?php

namespace App\Models\Prescription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;
    protected $table = "tbl_Prescription";
    public $primaryKey = 'PrescriptionID';
    protected $guarded = [];
    public $timestamps = false;
    protected $keyType = 'string';
}
