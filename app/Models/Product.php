<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = "Product";
    public $primaryKey = "ProductCode";
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = "string";
    protected $guarded = [];
}
