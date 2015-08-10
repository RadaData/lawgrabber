<?php

namespace LawGrabber\Jobs;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    public $timestamps = false;
    public $fillable = ['service', 'method', 'parameters', 'group', 'claimed', 'finished', 'priority'];
    protected $casts = [
        'parameters' => 'array',
        'finished' => 'bool',
    ];

    public function execute()
    {
        _log('==== Job ==== #' . $this->id . ' ' . $this->service . '->' . $this->method . '(' . json_encode($this->parameters, JSON_UNESCAPED_UNICODE) . ')', 'title');

        if ($this->service) {
            $func = [app()->make($this->service), $this->method];
        } else {
            $func = $this->method;
        }

        try {
            call_user_func_array($func, $this->parameters);
            $this->update(['finished' => time(), 'claimed' => 0]);
        }
        catch(\Exception $e) {
            if ($e instanceof Exceptions\JobChangePriorityException) {
                _log('JOB#' . $this->id . ' FAILURE(' . $e->getMessage() . '). Priority changed to ' . $e->newPriority, 'red');
                $this->update(['priority' => $e->newPriority]);
            }
            else {
                _log('JOB#' . $this->id . ' FAILURE(' . str_replace('LawGrabber\Service\Exceptions\\', '', get_class($e)) . ': ' . $e->getMessage() . ')', 'red');
            }
        }
    }
}

