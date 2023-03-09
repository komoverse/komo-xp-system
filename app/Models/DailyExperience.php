<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyExperience extends Model
{
    use HasFactory;

    protected $table = 'xp_daily_experiences';
    protected $guarded = ['id'];
    protected $fillable = ['account_id', 'api_key', 'total_experience'];
}
