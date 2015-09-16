<?php

use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawPages\LawRenderer;

Route::get('/laws/show/{law_id}/ed{date}/raw', function ($law_id, $date) {
    $law = Law::findOrFail($law_id);
    $date = date_format(date_create_from_format('Ymd', $date), 'Y-m-d');
    $revision = Revision::find($law_id, $date);
    return view('lawpages::law_page')->with([
        'law' => $law,
        'revision' => $revision,
        'raw' => true
    ]);
})->where([
    'law_id' => '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+',
    'date' => '[0-9]+'
]);

Route::get('/laws/show/{law_id}/ed{date}', function ($law_id, $date) {
    $law = Law::findOrFail($law_id);
    $date = date_format(date_create_from_format('Ymd', $date), 'Y-m-d');
    $revision = Revision::find($law_id, $date);
    return view('lawpages::law_page')->with([
        'law' => $law,
        'revision' => $revision
    ]);
})->where([
    'law_id' => '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+',
    'date' => '[0-9]+'
]);

Route::get('/laws/show/{law_id}/raw', function ($law_id) {
    $law = Law::findOrFail($law_id);
    return view('lawpages::law_page')->with('law', $law)->with('raw', true);
})->where('law_id', '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+');

Route::get('/laws/show/{law_id}', function ($law_id) {
    $law = Law::findOrFail($law_id);
    return view('lawpages::law_page')->with(['law' => $law]);
})->where(['law_id' => '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+']);

// TODO: Laws with includes.
// TODO: Laws without text, but with files.
// TODO: Laws with tables.