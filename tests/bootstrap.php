<?php
$loader = require_once __DIR__ . '/../vendor/autoload.php';
if (is_object($loader)) {
    $loader->add('Makotokw\\YouTube\\', __DIR__);
}
