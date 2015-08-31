<?php

namespace LawGrabber\Jobs;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

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
class Job extends Model
{
    const ERROR = 2147483648;
    public $timestamps = false;
    public $fillable = ['id', 'service', 'method', 'parameters', 'group', 'claimed', 'finished', 'priority'];
    protected $casts = [
        'parameters' => 'array',
    ];

    public function execute()
    {
        _log('==== Job ==== #' . $this->id . ' ' . $this->service . '->' . $this->method . '(' . json_encode($this->parameters, JSON_UNESCAPED_UNICODE) . ')', 'title');

        $func = $this->getFunc();

        try {
            call_user_func_array($func, $this->parameters);
            $this->update(['finished' => time(), 'claimed' => 0]);
        }
        catch(\Exception $e) {
            if ($e instanceof Exceptions\JobChangePriorityException) {
                $this->increment('priority', $e->newPriority);
                if ($this->priority <= -20) {
                    $this->fail();
                    _log('JOB#' . $this->id . ' FAILURE(' . $e->getMessage() . '). Job deleted.', 'red');

                }
                else {
                    _log('JOB#' . $this->id . ' ERROR(' . $e->getMessage() . '). Priority changed by ' . $e->newPriority, 'red');

                }
            }
            else {
                _log('JOB#' . $this->id . ' ERROR(' . str_replace('LawGrabber\Service\Exceptions\\', '', get_class($e)) . ': ' . $e->getMessage() . ')', 'red');
            }
        }
    }

    private function getFunc($error = false)
    {
        $method = $error ? $this->method . 'Fail' : $this->method;

        if ($this->service) {
            $func = [app()->make($this->service), $method];
        } else {
            $func = $method;
        }
        return $func;
    }

    public function fail()
    {
        $func = $this->getFunc(true);

        if (is_callable($func)) {
            call_user_func_array($func, $this->parameters);
        }

        $this->update(['finished' => self::ERROR, 'claimed' => 0]);
    }
}

