<?php
require '../vendor/autoload.php';

try {

    $deployr = new Deployr\Application('72e42c81-3e0d-4fb0-a21c-4a0bf83d36c4');
    $deployr->setOptions([
        'access_key_name' => 'access_key',
        'allowed_ip' => ['127.0.0.1', '::1'],
    ]);
    $deployr->run();

} catch (\Deployr\Exception $e) {
    echo $e->getMessage();
}

