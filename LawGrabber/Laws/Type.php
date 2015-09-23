<?php

namespace LawGrabber\Laws;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * LawGrabber\Laws\Type
 *
 * @property string $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\ $related[] $morphedByMany
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

    public function getRid()
    {
        $rules = [
            [
                'patterns' => ['кодекс', 'договір', 'графік', 'склад', 'паспорт', 'порядок', 'документ', 'бланк'],
                'result' => 'm',
            ],
            [
                'patterns' => ['форма'],
                'result' => 'f',
            ],
            [
                'patterns' => ['узагальнення', 'зміни', 'правила'],
                'result' => 'b+',
            ],
            [
                'patterns' => ['ння$','о$','е$','є$','у$','ю$'],
                'result' => 'b',
            ],
            [
                'patterns' => ['и$','ї$'],
                'result' => 'b+',
            ],
            [
                'patterns' => ['я$','а$','ь$'],
                'result' => 'f',
            ],
        ];
        foreach ($rules as $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match('%' . $pattern . '%ui', $this->name)) {
                    return $rule['result'];
                }
            }
        }
        return 'm';
    }
}

