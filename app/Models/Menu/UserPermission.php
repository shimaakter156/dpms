<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use HasFactory;
    protected $table = "vw_UserPermissions";
    public $primaryKey = false;
    protected $guarded = [];
    public $timestamps = false;
    protected $keyType = 'string';
}
