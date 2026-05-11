<?php

namespace App\Models\Complaint;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiefComplaint extends Model
{
    use HasFactory;
    protected $table = 'tbl_ChiefComplaint';
    protected $primaryKey = 'ComplaintID';
    public $incrementing = true; // false if LocationCode is not auto-increment
    public $timestamps = false;
}
