<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    //
    protected $fillable = [  'factual_id' ,
        'name'  ,
        'description',
        'type',
        'ethnicity',
        'category',
        'address',
        'postcode',
        'locality',
        'region',
        'contact',
        'email',
        'rating',
        'cuisine',
        'opening'] ;
}
