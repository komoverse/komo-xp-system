<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedDailyExperience extends Model
{
    use HasFactory;

    protected $table = 'xp_unified_daily_experiences';
    protected $guarded = ['id'];
    protected $fillable = ['account_id', 'total_experience'];
}
