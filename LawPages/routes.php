<?php

use LawGrabber\Laws\Law;
use LawPages\LawRenderer;

Route::get('/laws/show/{id}', function ($id) {
    $law = Law::findOrFail($id);
    return view('lawpages::law')->with('law', $law);
})->where('id', '[A-Za-z0-9_абвгґдеєжзиіїйклмнопрстуфхцчшщьюяАБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ\-\/]+');
