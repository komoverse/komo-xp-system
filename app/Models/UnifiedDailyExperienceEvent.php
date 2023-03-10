<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnifiedDailyExperienceEvent extends Model
{
    use HasFactory;

    protected $table = 'xp_unified_daily_experience_events';
    protected $guarded = ['id'];
    protected $fillable = ['unified_daily_experience_id', 'api_key', 'delta'];
}
