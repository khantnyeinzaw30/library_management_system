<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returning extends Model
{
    use HasFactory;

    protected $fillable = ['borrow_id', 'user_id', 'book_id', 'date_returned', 'due_date', 'fine'];
}