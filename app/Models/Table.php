<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = ['table_number'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
