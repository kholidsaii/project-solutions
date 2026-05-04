<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    // Pastikan menunjuk ke tabel yang benar di database Anda
    protected $table = 'companies';

    protected $guarded = []; // Mengizinkan mass-assignment

    // Relasi balik ke Project (opsional, tapi disarankan)
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_companies', 'company_id', 'project_id')
                    ->withPivot('role', 'share_percentage')
                    ->withTimestamps();
    }
}