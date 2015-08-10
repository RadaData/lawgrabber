<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\Issuer
 *
 * @property string $id 
 * @property string $name 
 * @property string $full_name 
 * @property string $group_name 
 * @property string $website 
 * @property string $url 
 * @property boolean $international 
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Issuer where($value)
 * @method static Builder|Issuer whereId($value)
 * @method static Builder|Issuer whereName($value)
 * @method static Builder|Issuer whereFullName($value)
 * @method static Builder|Issuer whereGroupName($value)
 * @method static Builder|Issuer whereWebsite($value)
 * @method static Builder|Issuer whereUrl($value)
 * @method static Builder|Issuer whereInternational($value)
 */
class Issuer extends Model
{
    const FIELD_NAME = 'Видавники';

    public $incrementing = false;
    public $timestamps = false;
    public $primaryKey = 'name';
    public $fillable = ['id', 'name', 'full_name', 'group_name', 'website', 'url', 'international'];
}

