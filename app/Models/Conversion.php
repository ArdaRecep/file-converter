<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    protected $fillable = ['source_type', 'target_type', 'status'];

    public function files()
    {
        return $this->hasMany(ConversionFile::class);
    }
}