<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['number', 'capacity', 'qr_code', 'status'])]
class Table extends Model
{
    use HasFactory;

    protected $table = 'tables';
}