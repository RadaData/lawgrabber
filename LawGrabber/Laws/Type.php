<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\Type
 *
 * @property string $id 
 * @property string $name 
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany 
 * @method static Builder|Type whereId($value)
 * @method static Builder|Type whereName($value)
 */
class Type extends Model
{
    const FIELD_NAME = 'Види';

    public $incrementing = false;
    public $timestamps = false;
    public $primaryKey = 'name';
    public $fillable = ['id', 'name'];
}

