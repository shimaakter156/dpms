<?php

namespace App\Models\Login;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;
    protected $table = "tbl_LoginLog";
    public $primaryKey = 'LoginLogID';
    protected $guarded = [];
    public $timestamps = false;
    protected $keyType = 'string';
}
