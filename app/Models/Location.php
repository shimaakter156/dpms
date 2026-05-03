<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $table = 'Location';
    protected $primaryKey = 'LocationCode';
    public $incrementing = true; // false if LocationCode is not auto-increment
    public $timestamps = false;

    protected $fillable = [
        'LocationName',
        'LocationShortName',
        'Status',
    ];
}
