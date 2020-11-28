<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'region';

    protected $fillable = [
        'region',
    ];

    public function city()
    {
        return $this->hasMany('App\Models\City');
    }

}
