<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\State
 *
 * @property string $id 
 * @property string $name 
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|State where($value)
 * @method static Builder|State whereId($value)
 * @method static Builder|State whereName($value)
 */
class State extends Model
{
    const FIELD_NAME = 'Стан';
    const STATE_UNKNOWN = 'Не визначено';

    public $incrementing = false;
    public $timestamps = false;
    public $primaryKey = 'name';
    public $fillable = ['id', 'name'];
}