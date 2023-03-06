<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompendiumExperience extends Model
{
    use HasFactory;

    protected $table = 'tb_compendium_experiences';
    protected $guarded = ['id'];
    protected $fillable = ['season_id', 'account_id', 'total_experience'];

}
