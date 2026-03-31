<?php
// src/Includes/config.php

define('DB_HOST',    'localhost');
define('DB_NAME',    'rfid_payment');
define('DB_USER',    'rfid_user');
define('DB_PASS',    'rfid1234');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',   'RFID Pay');
define('BASE_URL',   '/');               // adapter si sous-dossier

define('RFID_SCRIPT', '/home/pi/script/tag_detect.sh');

define('DEBUG', false);
