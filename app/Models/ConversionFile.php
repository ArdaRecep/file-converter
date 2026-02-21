<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversionFile extends Model
{
    protected $fillable = [
        'conversion_id',
        'original_name',
        'input_path',
        'output_path',
        'status',
        'error',
    ];

    public function conversion()
    {
        return $this->belongsTo(Conversion::class);
    }
}