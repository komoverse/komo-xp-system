<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameExperienceMultiplier extends Model
{
    use HasFactory;

    protected $table = 'tb_game_experience_multipliers';
    protected $guarded = ['id'];
    protected $fillable = ['game_id', 'daily_multiplier', 'mmr_multiplier', 'compendium_multiplier'];

}
