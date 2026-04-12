<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    // Daftarkan kolom yang boleh diisi
   protected $fillable = [
    'user_id', 
    'user_name', 
    'action', 
    'description', 
    'ip_address',
    'user_agent'
];
}