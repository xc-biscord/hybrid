<?php

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$data = getJsonInput();

respondFromController(apiKernel()->authController()->register($data));
