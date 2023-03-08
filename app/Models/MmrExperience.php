<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MmrExperience extends Model
{
    use HasFactory;

    protected $table = 'xp_mmr_experiences';
    protected $guarded = ['id'];
    protected $fillable = ['account_id', 'total_experience'];
}
