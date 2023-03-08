<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameExperienceMultiplier extends Model
{
    use HasFactory;

    protected $table = 'xp_game_experience_multipliers';
    protected $guarded = ['id'];
    protected $fillable = ['api_key', 'daily_multiplier', 'mmr_multiplier', 'compendium_multiplier'];

}
