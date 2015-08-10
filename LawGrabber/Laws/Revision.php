<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\Revision
 *
 * @property integer $id 
 * @property string $date 
 * @property string $law_id 
 * @property string $state 
 * @property string $text 
 * @property integer $text_updated 
 * @property string $comment 
 * @property boolean $status 
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Revision whereId($value)
 * @method static Builder|Revision whereDate($value)
 * @method static Builder|Revision whereLawId($value)
 * @method static Builder|Revision whereState($value)
 * @method static Builder|Revision whereText($value)
 * @method static Builder|Revision whereTextUpdated($value)
 * @method static Builder|Revision whereComment($value)
 * @method static Builder|Revision whereStatus($value)
 */
class Revision extends Model
{
    const NEEDS_UPDATE = 0;
    const UP_TO_DATE = 1;
    const NO_TEXT = 5;

    protected $table = 'law_revisions';
    public $timestamps = false;
    public $fillable = ['id', 'date', 'law_id', 'state', 'text', 'text_updated', 'comment', 'status'];

    public static function find($law_id, $date)
    {
        return static::where('law_id', $law_id)->where('date', $date)->first();
    }
}