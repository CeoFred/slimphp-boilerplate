<?php

$app->get('/test','Test:all');
$app->get('/test/{id}','Test:getSingle');
$app->post('/test','Test:post');
$app->put('/test/{id}','Test:updateSingle');
$app->delete('/test/{id}','Test:deleteSingle');