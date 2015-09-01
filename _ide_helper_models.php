<?php
/**
 * An helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace LawGrabber\Jobs{
/**
 * LawGrabber\Jobs\Job
 *
 * @property integer $id
 * @property string $service
 * @property string $method
 * @property string $parameters
 * @property string $group
 * @property integer $claimed
 * @property integer $finished
 * @property boolean $priority
 * @property-read Collection|\$related[] $morphedByMany
 * @method static Builder|Job whereId($value)
 * @method static Builder|Job whereService($value)
 * @method static Builder|Job whereMethod($value)
 * @method static Builder|Job whereParameters($value)
 * @method static Builder|Job whereGroup($value)
 * @method static Builder|Job whereClaimed($value)
 * @method static Builder|Job whereFinished($value)
 * @method static Builder|Job wherePriority($value)
 */
	class Job {}
}

namespace LawGrabber\Laws{
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
	class Issuer {}
}

namespace LawGrabber\Laws{
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
	class Law {}
}

namespace LawGrabber\Laws{
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
	class Revision {}
}

namespace LawGrabber\Laws{
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
	class State {}
}

namespace LawGrabber\Laws{
/**
 * LawGrabber\Laws\Type
 *
 * @property string $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Type whereId($value)
 * @method static Builder|Type whereName($value)
 */
	class Type {}
}

