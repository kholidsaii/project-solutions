<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function companies()
    {
        // Ubah bagian \App\Models\Company::class sesuai nama file Model Anda
        return $this->belongsToMany(\App\Models\Company::class, 'project_companies', 'project_id', 'company_id')
                    ->withPivot('role', 'share_percentage')
                    ->withTimestamps();
    }
}
