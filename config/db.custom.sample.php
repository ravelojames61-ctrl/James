<?php
/**
 * Production / InfinityFree Database Configuration Sample
 * 
 * Instructions:
 * 1. Duplicate or rename this file to 'db.custom.php' on your InfinityFree server (or locally).
 * 2. Fill in the MySQL connection details from your InfinityFree Client Area / Control Panel.
 * 3. Never commit 'db.custom.php' to a public Git repository.
 */

// Your InfinityFree MySQL details (found in Control Panel -> MySQL Databases):
define('DB_HOST', 'sqlXXX.infinityfree.com'); // e.g. sql100.infinityfree.com or sql200.epizy.com
define('DB_NAME', 'if0_XXXXXXXX_vlms_db');     // e.g. if0_12345678_vlms_db
define('DB_USER', 'if0_XXXXXXXX');             // e.g. if0_12345678
define('DB_PASS', 'YOUR_INFINITYFREE_PASSWORD'); // Your InfinityFree account/vPanel password
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

