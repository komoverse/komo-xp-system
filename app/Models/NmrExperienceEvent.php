<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NmrExperienceEvent extends Model
{
    use HasFactory;

    protected $table = 'tb_nmr_experience_events';
    protected $guarded = ['id'];
    protected $fillable = ['nmr_experience_id', 'source', 'delta'];
}
