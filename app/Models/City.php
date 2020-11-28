<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city';

    protected $fillable = [
        'city',
    ];

    public function region()
    {
        return $this->belongsTo('App\Models\Region');
    }

}
