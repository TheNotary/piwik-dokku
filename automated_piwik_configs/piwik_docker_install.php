<?php
// this script checks to see if this is the initial deploy of piwik, and if so
// will create the required database tables and users and direct the user to
// the site setup page by default.  Otherwise this script won't do anything.

// Login Stuff
$adminLogin = getenv('ADMIN_LOGIN');
$adminEmail = urlencode(getenv('ADMIN_EMAIL'));
$adminPassword = urlencode(getenv('ADMIN_PASSWORD'));

// Db Stuff
$dbHost = getenv('DB_HOST');
$dbUsername = getenv('DB_USERNAME');
$dbPassword = getenv('DB_PASSWORD');
$dbName = getenv('DB_NAME');
$dbPrefix = getenv('DB_PREFIX');

// Misc
$piwikHost = "127.0.0.1:3000";
$piwikRootPath = "/var/www/html";


/////////////////////////
// Connect to Database //
/////////////////////////
$connectionString = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8;";

try {
  $pdo = new PDO($connectionString, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
}
catch (PDOException $e) {
  fwrite(STDERR, 'Connection failed: ' . $e->getMessage());
  fwrite(STDERR, 'Make sure you setup a DATABASE_URL pointing to the DB you want');
  exit(0);
}



///////////////////////////////////////////////////////////////////////////////
// Define Methods that Really Should Already be Built Into PHP at this Point //
///////////////////////////////////////////////////////////////////////////////

function checkIfTableExists($pdo, $tableToCheck) {
  $statement = $pdo->query('show tables');

  $does_table_exist = false;
  while ($row = $statement->fetch()) {
    if ($row[0] == $tableToCheck) {
      $does_table_exist = true;
      break;
    }
  }
  return $does_table_exist;
}

// Usage:  getCountOfMatchingRecords($pdo, 'piwik_user', 'WHERE superuser_access = 1')
function getCountOfMatchingRecords($pdo, $tableName, $whereClause) {
  $statement = $pdo->query('SELECT COUNT(*) FROM ' . $tableName . ' ' . $whereClause . ';');

  $row = $statement->fetch();
  return $row[0];
}



///////////////////////////////////////////////////////////////////////////
// Bash commands that are more compaitible with curl exports from Chrome //
///////////////////////////////////////////////////////////////////////////

$cmdToHitWelcomeScreen = <<<EOF
  curl -L 'http://{$piwikHost}/' -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' -H 'Accept-Encoding: gzip, deflate' -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'
EOF;

$cmdToDoSystemCheck = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?action=systemCheck' -H 'Host: {$piwikHost}' \
  -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
  -H 'Accept-Encoding: gzip, deflate' \
  -H 'Referer: http://{$piwikHost}/' -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'
EOF;

// -H 'Cookie: piwik_lang=language%3DczoyOiJlbiI7%3A_%3D49c70cce625838cbc9660157730029b0b19f4d65' \
$cmdToViewDatabaseEntrieForm = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?action=databaseSetup' \
    -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
    -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
    -H 'Accept-Encoding: gzip, deflate' \
    -H 'Referer: http://{$piwikHost}/index.php?action=systemCheck' \
    -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'
EOF;

// -H 'Cookie: piwik_lang=language%3DczoyOiJlbiI7%3A_%3D0a7830aac009ce3b78a6f48f4137b6250444b662' \
$cmdToConfigureDatabaseEntries = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?action=databaseSetup' \
    -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
    -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' \
    -H 'Accept-Language: en-US,en;q=0.5' -H 'Accept-Encoding: gzip, deflate' \
    -H 'Referer: http://{$piwikHost}/index.php?action=databaseSetup' -H 'DNT: 1' \
    -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1' -H 'Content-Type: application/x-www-form-urlencoded' \
    --data 'type=InnoDB&host={$dbHost}&username={$dbUsername}&password={$dbPassword}&dbname={$dbName}&tables_prefix={$dbPrefix}&adapter=PDO%5CMYSQL&submit=Next+%C2%BB'
EOF;


$cmdToGoPastTablesCreatedScreen = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?action=setupSuperUser&module=Installation' \
  -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
  -H 'Accept-Encoding: gzip, deflate' \
  -H 'Referer: http://{$piwikHost}/index.php?action=tablesCreation&module=Installation' \
  -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'
EOF;


$cmdToCreateAdminUser = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?action=setupSuperUser&module=Installation' \
  -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
  -H 'Accept-Encoding: gzip, deflate' \
  -H 'Referer: http://{$piwikHost}/index.php?action=setupSuperUser&module=Installation' \
  -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data 'login={$adminLogin}&password={$adminPassword}&password_bis={$adminPassword}&email={$adminEmail}&submit=Next+%C2%BB'
EOF;





// Not used
$cmdToCreateExampleSite = <<<EOF
  curl -L 'http://{$piwikHost}/index.php' \
  -H 'Host: {$piwikHost}' \
  -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
  -H 'Accept-Encoding: gzip, deflate' -H 'Referer: http://{$piwikHost}/index.php?action=firstWebsiteSetup' -H 'DNT: 1' -H 'Connection: keep-alive' \
  -H 'Upgrade-Insecure-Requests: 1' -H 'Content-Type: application/x-www-form-urlencoded' \
  --data 'siteName=WEBSITE_NAME&url=http%3A%2F%2Fexample.org&timezone=America%2FChicago&ecommerce=0&submit=Next+%C2%BB'
EOF;

// Not used
$cmdToConfirmTrackingSnippet = <<<EOF
  curl 'http://{$piwikHost}/index.php?module=Installation&action=finished&site_idSite=1&site_name=WEBSITE_NAME' \
    -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
    -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
    -H 'Accept-Encoding: gzip, deflate' \
    -H 'Referer: http://{$piwikHost}/index.php?module=Installation&action=trackingCode&site_idSite=1&site_name=WEBSITE_NAME' \
    -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1'
EOF;

// Not used
$cmdToConfirmNetiquette = <<<EOF
  curl -L 'http://{$piwikHost}/index.php?module=Installation&action=finished&site_idSite=1&site_name=WEBSITE_NAME' \
    -H 'Host: {$piwikHost}' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0' \
    -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' -H 'Accept-Language: en-US,en;q=0.5' \
    -H 'Accept-Encoding: gzip, deflate' \
    -H 'Referer: http://{$piwikHost}/index.php?module=Installation&action=finished&site_idSite=1&site_name=WEBSITE_NAME' \
    -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Upgrade-Insecure-Requests: 1' -H 'Content-Type: application/x-www-form-urlencoded' \
    --data 'do_not_track=1&anonymise_ip=1&submit=Continue+to+Piwik+%C2%BB'
EOF;


// Hack piwik to bring the user to the firstWebsiteSetup page instead
// of forcing them to start at the beginning and manually install
$cmdToHackPiwikWelcomePage = <<<EOF
  sed -i s/\ \ \ \ \ \ \ \ \ \ \\\$action\ =\ \\'welcome\\'/\\\$action\ =\ \\'firstWebsiteSetup\\'/ {$piwikRootPath}/plugins/Installation/Installation.php
EOF;

$cmdToInstructPiwikTheAppIsCompletelyConfigured = <<<EOF
  sed -i s/installation_in_progress\ =\ 1/\ / {$piwikRootPath}/config/config.ini.php
EOF;


////////////////////////////////////////////////////////////////////////////
// Algo for Automatically setting up the Database on the first connection //
////////////////////////////////////////////////////////////////////////////


// sleep(1);

if (!checkIfTableExists($pdo, 'piwik_user')) {
  system($cmdToHitWelcomeScreen);
  system($cmdToDoSystemCheck);
  // sleep(1);

  system($cmdToViewDatabaseEntrieForm);
  system($cmdToConfigureDatabaseEntries);
  system($cmdToGoPastTablesCreatedScreen);
}

if (getCountOfMatchingRecords($pdo, 'piwik_user', 'WHERE superuser_access = 1') == 0) {
  system($cmdToCreateAdminUser);
  // sleep(1);
}

// hack piwik if the first website hasn't been created yet...
if (getCountOfMatchingRecords($pdo, 'piwik_site', '') == 0) {
  system($cmdToHackPiwikWelcomePage);
}
else {
  system($cmdToInstructPiwikTheAppIsCompletelyConfigured);
}


?>
