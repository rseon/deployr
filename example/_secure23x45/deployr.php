<?php
require '../../vendor/autoload.php';

$deployr = new Deployr\Application('72e42c81-3e0d-4fb0-a21c-4a0bf83d36c4');
$deployr->setOptions([
    'restrict_ip' => ['127.0.0.1', '::1'],
]);
$deployr->run();

