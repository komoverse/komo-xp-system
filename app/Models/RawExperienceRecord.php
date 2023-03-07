<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawExperienceRecord extends Model
{
    use HasFactory;

    protected $table = 'tb_raw_experience_records';
    protected $guarded = ['id'];
    protected $fillable = ['account_id', 'api_key', 'experience_gained'];
}
