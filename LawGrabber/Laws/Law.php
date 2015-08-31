<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\Law
 *
 * @property string $id
 * @property string $date
 * @property string $title
 * @property boolean $status
 * @property \LawGrabber\Laws\State $state
 * @property boolean $has_text
 * @property string $card
 * @property integer $card_updated
 * @property Revision $active_revision
 * @property-read Collection|Issuer[] $issuers
 * @property-read Collection|Type[] $types
 * @property-read Collection|Revision[] $revisions
 * @property-read Collection|\$related[] $morphedByMany
 * @method static Builder|Law whereId($value)
 * @method static Builder|Law whereDate($value)
 * @method static Builder|Law whereStatus($value)
 * @method static Builder|Law whereState($value)
 * @method static Builder|Law whereHasText($value)
 * @method static Builder|Law whereCard($value)
 * @method static Builder|Law whereCardUpdated($value)
 * @method static Builder|Law whereActiveRevision($value)
 */
class Law extends Model
{
    const UNKNOWN = 0;
    const HAS_TEXT = 1;
    const NO_TEXT = 10;

    const NOT_DOWNLOADED = 0;
    const DOWNLOADED_BUT_NEEDS_UPDATE = 4;
    const DOWNLOADED_CARD = 5;
    const DOWNLOADED_REVISIONS = 10;
    const DOWNLOADED_RELATIONS = 15;
    const DOWNLOAD_ERROR = 100;

    public $incrementing = false;
    public $timestamps = false;
    public $fillable = ['id', 'date', 'title', 'status', 'state', 'has_text', 'card', 'card_updated', 'active_revision'];

    public function issuers()
    {
        return $this->belongsToMany('\LawGrabber\Laws\Issuer', 'law_issuers', 'law_id', 'issuer_name');
    }
    public function types()
    {
        return $this->belongsToMany('\LawGrabber\Laws\Type', 'law_types', 'law_id', 'type_name');
    }
    public function state()
    {
        return $this->hasOne('\LawGrabber\Laws\State', 'name', 'state');
    }
    public function revisions()
    {
        return $this->hasMany('\LawGrabber\Laws\Revision', 'law_id');
    }
    public function active_revision()
    {
        return $this->hasOne('\LawGrabber\Laws\Revision', 'date', 'active_revision');
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        $result = [];
        $types = $this->types()->get()->all();
        array_walk($types, function($item) use (&$result) {
            $result[] = $item->name;
        });
        return $result;
    }

    /**
     * @return array
     */
    public function getIssuers()
    {
        $result = [];
        $issuers = $this->issuers()->get()->all();
        array_walk($issuers, function($item) use (&$result) {
            $result[] = $item->name;
        });
        return $result;
    }

    /**
     * @return Revision[]
     */
    public function getAllRevisions()
    {
        return $this->revisions()->all();
    }

    /**
     * @return Revision|null
     */
    public function getRevision($date)
    {
        return $this->revisions()->where('date', $date)->first();
    }

    /**
     * @return Revision
     */
    public function getActiveRevision()
    {
        return $this->active_revision()->first();
    }

    /**
     * @return bool
     */
    public function hasText()
    {
        return $this->has_text == static::HAS_TEXT;
    }

    /**
     * @return bool
     */
    public function notHasText()
    {
        return $this->has_text == static::NO_TEXT;
    }
}

