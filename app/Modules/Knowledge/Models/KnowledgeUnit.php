<?php

namespace App\Modules\Knowledge\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeUnit extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'title_ar', 'title_en'];
}
