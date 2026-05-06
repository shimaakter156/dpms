<?php

namespace App\Models\Doctor;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vw_DoctorInfo extends Model
{
    use HasFactory;
    protected $table = 'vw_DoctorInfo';
    protected $primaryKey = false;
    public $incrementing = false; // false if LocationCode is not auto-increment
    public $timestamps = false;
}
