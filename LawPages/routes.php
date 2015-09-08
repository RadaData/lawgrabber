<?php

use LawGrabber\Laws\Law;

Route::get('/laws/show/{id}/raw', function ($id) {
    $law = Law::findOrFail($id);
    return view('lawpages::law')->with('law', $law)->with('raw', true);
})->where('id', '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+');

Route::get('/laws/show/{id}', function ($id) {
    $law = Law::findOrFail($id);
    return view('lawpages::law')->with('law', $law);
})->where('id', '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+');

