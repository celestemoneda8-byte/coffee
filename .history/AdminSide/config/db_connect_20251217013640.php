<<<<<<< HEAD:.history/AdminSide/config/db_connect_20251217013640.php
<?php
// db_connect.php - create a mysqli $conn variable
// Put this file in the same folder as dashboard.php or adjust the require path accordingly.

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = ''; // set if you have a password
$DB_NAME = 'expresso_caffe'; // change to your DB name
$DB_PORT = 3306; // usually 3306

// Create connection and expose $conn variable
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

// Check connection
if ($conn->connect_errno) {
    // Log the real error and show a friendly message
    error_log("MySQL connection error ({$conn->connect_errno}): {$conn->connect_error}");
    // In development you may want to see the error:
    // die("Database connection failed: " . $conn->connect_error);
    // For production:
    die("Database connection error. Please check logs.");
}

// Optional: set charset
if (! $conn->set_charset('utf8mb4')) {
    error_log("Failed to set DB charset: " . $conn->error);
}
=======
<?php
// db_connect.php (patched)
// - Loads optional .env file in project root (simple parser).
// - Reads DB_* env vars with sensible defaults for XAMPP/Docker setups.
// - Attempts a primary connection and a fallback to 127.0.0.1 if the first host fails.
// - Sets $conn on success (mysqli instance) and $GLOBALS['db_connect_error'] on failure.
// - Logs full connection details to error_log while keeping displayed messages safe.

/*
Example .env (optional; place in project root):
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=expresso
*/

/////////////////////////
// Simple .env loader  //
/////////////////////////
$envFile = __DIR__ . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // split at first '='
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // remove surrounding quotes if present
            if ((substr($val,0,1) === '"' && substr($val,-1) === '"') ||
                (substr($val,0,1) === "'" && substr($val,-1) === "'")) {
                $val = substr($val,1,-1);
            }
            // Only set env var if not already set
            if (getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

/////////////////////////
// Read configuration  //
/////////////////////////
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
// default DB name set to 'expresso' (matches your project). Change if needed.
$dbName = getenv('DB_NAME') ?: 'expresso';

/////////////////////////
// Helper: try connect //
/////////////////////////
function try_mysqli_connect($host, $user, $pass, $name, $port) {
    mysqli_report(MYSQLI_REPORT_OFF);
    // Suppress warnings from constructor, handle errors below
    $m = @new mysqli($host, $user, $pass, $name, (int)$port);
    if ($m && $m->connect_errno) {
        // return array(false, mysqli instance) so caller can read connect_errno/msg if needed
        return [false, $m];
    }
    return [true, $m];
}

/////////////////////////
// Attempt connection  //
/////////////////////////
list($ok, $mysqli) = try_mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if (!$ok) {
    // Log full details for server logs (do NOT expose creds to users)
    $errFull = sprintf(
        'db_connect.php: MySQL connect failed to %s:%s db=%s user=%s errno=%s error=%s',
        $dbHost,
        $dbPort,
        $dbName,
        $dbUser,
        $mysqli->connect_errno ?? 'n/a',
        $mysqli->connect_error ?? 'n/a'
    );
    error_log($errFull);

    // If host is not 127.0.0.1/localhost, attempt a fallback to 127.0.0.1 (common when using XAMPP on host)
    if ($dbHost !== '127.0.0.1' && $dbHost !== 'localhost') {
        list($ok2, $mysqli2) = try_mysqli_connect('127.0.0.1', $dbUser, $dbPass, $dbName, $dbPort);
        if ($ok2) {
            $mysqli2->set_charset('utf8mb4');
            $conn = $mysqli2;
            if (isset($GLOBALS['db_connect_error'])) unset($GLOBALS['db_connect_error']);
            return;
        } else {
            error_log(sprintf(
                'db_connect.php: fallback to 127.0.0.1 failed: errno=%s error=%s',
                $mysqli2->connect_errno ?? 'n/a',
                $mysqli2->connect_error ?? 'n/a'
            ));
        }
    }

    // Store a safe, user-facing error that pages can show
    $GLOBALS['db_connect_error'] = 'Unable to connect to the database. Check database server and configuration.';
    // Ensure $conn is not set so existing code can use fallbacks
    if (isset($conn)) unset($conn);
} else {
    // success
    $mysqli->set_charset('utf8mb4');
    $conn = $mysqli;
    if (isset($GLOBALS['db_connect_error'])) unset($GLOBALS['db_connect_error']);
}
>>>>>>> 48c3441730d7f3bcc0f52162c85c9984cc42607c:AdminSide/db_connect.php
?>