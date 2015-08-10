<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    const FIELD_NAME = 'Види';

    public $incrementing = false;
    public $timestamps = false;
    public $primaryKey = 'name';
    public $fillable = ['id', 'name'];
}

