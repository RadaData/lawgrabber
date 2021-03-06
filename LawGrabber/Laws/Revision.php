<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use DB;

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
    const UP_TO_DATE = 5;
    const NO_TEXT = 10;
    const DOWNLOAD_ERROR = 100;

    protected $table = 'law_revisions';
    public $timestamps = false;
    public $fillable = ['id', 'date', 'law_id', 'state', 'text', 'text_updated', 'comment', 'status'];

    /**
     * @param $law_id
     * @param $date
     *
     * @return Revision
     */
    public static function find($law_id, $date)
    {
        return static::where('law_id', $law_id)->where('date', $date)->first();
    }

    /**
     * @param $law_id
     * @param $date
     *
     * @return Revision
     */
    public static function findROrNew($law_id, $date)
    {
        $revision = static::find($law_id, $date);
        if (!$revision) {
            $revision = new Revision(['law_id' => $law_id, 'date' => $date]);
        }
        return $revision;
    }

    /**
     * @return Law
     */
    public function getLaw()
    {
        return Law::find($this->law_id);
    }

    /**
     * @return Revision|null
     */
    public function getPreviousRevision()
    {
        $revisions = DB::table('law_revisions')->select('date')->where('law_id', $this->law_id)->get();
        
        $previous = null;
        $result = null;
        foreach ($revisions as $revision) {
            if ($revision->date == $this->date) {
                $result = $previous ? $previous->date : null;
                break;
            }
            $previous = $revision;
        }
        
        return $result ? static::where('law_id', $this->law_id)->where('date', $result)->first() : null;
    }
}