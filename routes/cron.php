<?php
$router = \App\Core\App::getInstance()->getRouter();
// Cron job endpoints
$router->get('/cron/queue', function() {
    $runner = new \App\Queue\JobRunner();
    $runner->processNext();
});