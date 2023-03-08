<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MmrExperienceEvent extends Model
{
    use HasFactory;

    protected $table = 'tb_mmr_experience_events';
    protected $guarded = ['id'];
    protected $fillable = ['mmr_experience_id', 'api_key', 'delta'];
}
