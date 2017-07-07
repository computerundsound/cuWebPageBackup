<?php
/*
 * Copyright by Jörg Wrase - www.Computer-Und-Sound.de
 * Date: 03.07.2017
 * Time: 01:54
 *
 * Created by PhpStorm
 *
 */

//Backup Script
//
//This Script will search for Wordpress and Gambio DB-credentials. If not found, it will use the credentials from below

/* ************************/

/* Remove this to enable this script {because it is secure to have an exit here, if script is not used} */

$scriptIsActive = 1; // 0 => script will only return a blank page, nothing done (switched off) || 1 = will run

/* You can enter your db-Credentials here - if no other credentials found, this will be used */
/** @noinspection PhpUnreachableStatementInspection */
$dbServer   = '';
$dbUser     = '';
$dbPassword = '';
$dbName     = '';

$zipFileOnServer = 'cuBackup.zip'; // File on Server to unpack
$dbFileOnServer  = 'cuBackup.sql'; // File on Server to for db

// End Edit **********************************************************
// End Edit **********************************************************
// End Edit **********************************************************

/*
 *
 * Script Start
 *
 */
session_start();

if (isset($_POST['killSession']) && $_POST['killSession'] === 'true') {
    unset($_SESSION);
    session_destroy();
    session_start();
}

/*Demomodus*/
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

/**
 * Class CuDemo
 */
class CuDemo
{

    public static $activeModus;

}

/**
 * @param $message
 */
function cuEcho($message) {

    echo "$message <br>";
}

define('CU_SCRIPT_START', time());
define('CU_SCRIPT_MAX_TIME', 2);

$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

CuDemo::$activeModus = !($serverName === 'snippets.cusp.de');

/* START */

ini_set('display_errors', 'on');
ini_set('html_errors', 'on');

error_reporting(E_ALL);

ini_set('max_execution_time', '360');
ini_set('max_input_time', '240');
ini_set('memory_limit', '512M');
ini_set('max_input_vars', '5500');

if ($scriptIsActive !== 1) {
    exit;
}

/**
 *
 * @return mixed
 *
 * @since version
 */
function php53() {

    return version_compare(PHP_VERSION, '5.3', '>');

}

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

/**
 * Class CuDirectoryInfo
 */
class CuDirectoryInfo
{

    private $size   = 0;
    private $name   = '';
    private $owner  = '';
    private $rights = '';
    private $path   = '';

    /**
     * @return int
     */
    public function getSize() {

        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size) {

        $this->size = (int)$size;
    }

    /**
     * @return mixed
     */
    public function getName() {

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {

        $this->name = (string)$name;
    }

    /**
     * @return string
     */
    public function getOwner() {

        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner($owner) {

        $this->owner = (string)$owner;
    }

    /**
     * @return string
     */
    public function getRights() {

        return $this->rights;
    }

    /**
     * @param string $rights
     */
    public function setRights($rights) {

        $this->rights = (string)$rights;
    }

    /**
     * @return string
     */
    public function getPath() {

        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path) {

        $this->path = (string)$path;
    }


}

/**
 * @param string $dir
 * @param string $directoryListAsString
 *
 * @return array
 */
function getDirsFromDir($dir, &$directoryListAsString) {

    $dirs        = array();
    $directories = glob($dir . '*', GLOB_ONLYDIR);

    // php 5.3
    sort($directories, SORT_STRING);

    // php 5.4 and greater
//    sort($directories, SORT_STRING | SORT_FLAG_CASE);

    foreach ($directories as $directory) {

        $cuDirectoryInfo = new CuDirectoryInfo();

        $baseName = basename($directory);

        $size = filesize($directory);

        $cuDirectoryInfo->setName($baseName);
        $cuDirectoryInfo->setSize($size);
        $cuDirectoryInfo->setPath($directory);

        $dirs[$baseName] = $cuDirectoryInfo;

        $directoryListAsString .= $baseName . "\n";

    }

    $directoryListAsString = trim($directoryListAsString);

    return $dirs;

}

$directoryListAsString  = '';
$allDirsInThisDirectory = getDirsFromDir('./', $directoryListAsString);

$actions = array(
    'zip'                    => array(
        'text'  => 'Erstellt eine Zip-Datei vom Verzeichnis - inkl. Datenbankbackup',
        'input' => array(
            'label'        => 'Welches Verzeichnis soll gepackt werden?',
            'valueDefault' => './',
        ),
        'modus' => array(
            array(
                'label' => 'über PHPZipArchiv (etwas langsamer, aber mit mehreren Servern kompatible - Datenbankbackup wird nicht erstellt)',
                'value' => 'php',
            ),
            array('label' => 'über php exec()', 'value' => 'exec'),
        ),
    ),
    'zip selected'           => array(
        'text' => 'Erstellt eine Zip-Datei aus einer Verzeichnis-Auswahl - KEIN Datenbankbackup mit dabei',

        'input-field' => array(
            'label'        => 'Verzeichnisse von hier aus gesehen angeben',
            'valueDefault' => $directoryListAsString,
        ),
        'modus'       => array(
            array(
                'label' => 'über PHPZipArchiv (etwas langsamer, aber mit mehreren Servern kompatible',
                'value' => 'php',
            ),
            array('label' => 'über php exec()', 'value' => 'exec'),
        ),
    ),
    'saveDB'                 => array('text' => 'Versucht ein Datenbank-Backup zu erstellen'),
    'unpack'                 => array('text' => 'Entpackt eine Zip Datei'),
    'restoreDB'              => array('text' => "Stelle eine Datenbank wieder her - die Datei auf dem Server muss $dbFileOnServer heißen"),
    'deleteFiles (exec)'     => array('text' => 'Remove all Files from this Dir (recursive) with exec'),
    'deleteFiles (php)'      => array('text' => 'Remove all Files from this Dir (recursive) with PHP (unlink)'),
    'setFileRightGambioShop' => array('text' => 'Try to set the configure.org.php and configure.php files from Gambio-Shops to chmod 444'),
);
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

/**
 * Class Cu_DBCredentials
 */
class Cu_DBCredentials
{

    private $dbServer   = '';
    private $dbUser     = '';
    private $dbPassword = '';
    private $dbName     = '';

    /**
     * @return string
     */
    public function getDbServer() {

        return $this->dbServer;
    }

    /**
     * @param string $dbHost
     */
    public function setDbServer($dbHost) {

        $this->dbServer = (string)$dbHost;
    }

    /**
     * @return string
     */
    public function getDbUser() {

        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     */
    public function setDbUser($dbUser) {

        $this->dbUser = (string)$dbUser;
    }

    /**
     * @return string
     */
    public function getDbPassword() {

        return $this->dbPassword;
    }

    /**
     * @param string $dbPassword
     */
    public function setDbPassword($dbPassword) {

        $this->dbPassword = (string)$dbPassword;
    }

    /**
     * @return string
     */
    public function getDbName() {

        return $this->dbName;
    }

    /**
     * @param string $dbName
     */
    public function setDbName($dbName) {

        $this->dbName = (string)$dbName;
    }

    /**
     *
     * @return bool
     *
     */
    public function validate() {

        return !($this->getDbName() === '' || $this->getDbUser() === '');

    }


}

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

/**
 * Class Cu_Backup
 */
class Cu_Backup
{

    /**
     * @param $value
     */
    public static function cuPrint_r($value) {

        $valuePrint_r = print_r($value, true);

        echo "<pre>$valuePrint_r</pre>";
    }

    /**
     * @param $dbServer
     * @param $dbUser
     * @param $dbPassword
     * @param $dbName
     *
     * @return \Cu_DBCredentials
     */
    public function cu_getCredentials(
        $dbServer,
        $dbUser,
        $dbPassword,
        $dbName
    ) {

        $configs['gambio'] = array(
            'file'          => dirname(__FILE__) . '/admin/includes/configure.php',
            'constantNames' => array(
                'dbServer'   => 'DB_SERVER',
                'dbName'     => 'DB_DATABASE',
                'dbUser'     => 'DB_SERVER_USERNAME',
                'dbPassword' => 'DB_SERVER_PASSWORD',

            ),
        );

        $configs['wordpress'] = array(
            'file'          => dirname(__FILE__) . '/wp-config.php',
            'constantNames' => array(
                'dbServer'   => 'DB_HOST',
                'dbName'     => 'DB_NAME',
                'dbUser'     => 'DB_USER',
                'dbPassword' => 'DB_PASSWORD',

            ),
        );

        /** @var array $configs */
        /** @var array $config */
        foreach ($configs as $config) {
            if (file_exists($config['file'])) {
                /** @noinspection PhpIncludeInspection */
                include_once $config['file'];
                break;
            }
        }

        $constantName_dbServer   = $config['constantNames']['dbServer'];
        $constantName_dbName     = $config['constantNames']['dbName'];
        $constantName_dbUser     = $config['constantNames']['dbUser'];
        $constantName_dbPassword = $config['constantNames']['dbPassword'];

        $dbCredentials = $this->cu_getCredentialsFromLoadedFile($constantName_dbServer,
                                                                $dbServer,
                                                                $constantName_dbName,
                                                                $dbName,
                                                                $constantName_dbUser,
                                                                $dbUser,
                                                                $constantName_dbPassword,
                                                                $dbPassword);

        if ($dbCredentials->validate() === false) {
            $dbCredentials = $this->cu_getCredentialsFromJoomla();
        }

        return $dbCredentials;

    }

    /**
     * @param $constantName_dbServer
     * @param $defaultValue_dbServer
     * @param $constantName_dbName
     * @param $defaultValue_dbName
     * @param $constantName_dhUser
     * @param $defaultValue_dbHUser
     * @param $constantName_dhPassword
     * @param $defaultValue_dbPassword
     *
     * @return \Cu_DBCredentials
     */
    public function cu_getCredentialsFromLoadedFile(
        $constantName_dbServer,
        $defaultValue_dbServer,
        $constantName_dbName,
        $defaultValue_dbName,
        $constantName_dhUser,
        $defaultValue_dbHUser,
        $constantName_dhPassword,
        $defaultValue_dbPassword
    ) {

        $dbCredentials = new Cu_DBCredentials();

        $dbServer   = defined($constantName_dbServer) ? constant($constantName_dbServer) : $defaultValue_dbServer;
        $dbName     = defined($constantName_dbName) ? constant($constantName_dbName) : $defaultValue_dbName;
        $dbUser     = defined($constantName_dhUser) ? constant($constantName_dhUser) : $defaultValue_dbHUser;
        $dbPassword = defined($constantName_dhPassword) ? constant($constantName_dhPassword) : $defaultValue_dbPassword;

        $dbCredentials->setDbServer($dbServer);
        $dbCredentials->setDbName($dbName);
        $dbCredentials->setDbUser($dbUser);
        $dbCredentials->setDbPassword($dbPassword);

        return $dbCredentials;
    }

    /**
     * @param $dir
     * @param $allFiles
     * @param $zipFileOnServer
     */
    public function zipExec($dir, &$allFiles, $zipFileOnServer) {

        /* zip - exe */

        $response = $this->runExec("zip -r $zipFileOnServer $dir");

        if ($response['result'] !== 1) {
            echo 'error zip exec: ' . $response['result'];
        }

    }

    /**
     * @param $execString
     *
     * @return array
     */
    public function runExec($execString) {

//    $execString = escapeshellarg($execString);

        $output = array('Demo modus!');
        $result = '';
        $return = '';

        self::cuPrint_r(array('ExecStr' => $execString));

        if (CuDemo::$activeModus) {

            $result = exec($execString, $output, $return);

        }

        $output = is_array($output) ? implode("\n",
                                              $output) : $output;

        $response['execStr'] = $execString;
        $response['result']  = $result;
        $response['output']  = $output;
        $response['return']  = $return;

        return $response;

    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @param string $dir
     * @param array  $allFiles
     * @param string $zipFileOnServer
     * @param bool   $setTimestamp
     *
     * @return int
     */
    public function zipPHP($dir, &$allFiles, $zipFileOnServer, $setTimestamp = true) {

        $datedZipFileOnServer = $this->createDatedZipFileOnServerName($zipFileOnServer, $setTimestamp);

        echo $datedZipFileOnServer . '<br>';

        $allFilesCount = $this->scanDirRecursive($dir, $allFiles);

        if (CuDemo::$activeModus) {

            $zip = new ZipArchive();
            echo 'ErrorCode: ' . $zip->open($datedZipFileOnServer, ZipArchive::CREATE) . ' (1 === OK)<br>';

            if ($dir !== '.' && $dir !== '..' && is_dir($dir)) {
                $zip->addEmptyDir($dir);
            }

            foreach ($allFiles as $file) {

                if (is_dir($file)) {
                    $zip->addEmptyDir($file);
                } else {

                    $zip->addFile($file);
                }
            }

            $zip->close();
        }

        return $allFilesCount;

    }

    /**
     * @param string $standardName
     * @param bool   $setTimestamp
     *
     * @return string
     */
    public function createDatedZipFileOnServerName($standardName, $setTimestamp = true) {

        $dataPart = '';

        if ($setTimestamp) {
            $dataPart = '_' . date('Y-m-d--H-i-s');
        }

        $newName = preg_replace('#.zip$#', $dataPart . '.zip', $standardName);

        return (string)$newName;

    }

    /**
     * @param string $dir
     * @param array  $allFiles
     *
     * @return int
     */
    public function scanDirRecursive($dir, &$allFiles) {

        if (php53()) {
            /** @noinspection ScandirUsageInspection */
            $files = scandir($dir);
        } else {
            $files = scandir($dir, SCANDIR_SORT_ASCENDING);
        }

        $separator = '/';
        if (substr($dir, -1) === '/') {
            $separator = '';
        }

        foreach ($files as $file) {

            if ($file === '.' || $file === '..') {
                continue;
            }

            $newFile = $dir . $separator . $file;

            if (is_dir($newFile)) {
                $allFiles[] = $newFile;
                $this->scanDirRecursive($newFile, $allFiles);
            } else {
                $allFiles[] = $newFile;
            }

        }

        return count($allFiles);

    }

    /**
     * @param string            $dbFileOnServer
     * @param \Cu_DBCredentials $cuDBCredentials
     */
    public function restoreDB($dbFileOnServer, Cu_DBCredentials $cuDBCredentials) {

        echo 'restore Backup';

        $dbUser     = $cuDBCredentials->getDbUser();
        $dbPassword = $cuDBCredentials->getDbPassword();
        $dbName     = $cuDBCredentials->getDbName();

        $execString = "mysql -u $dbUser -p$dbPassword $dbName < $dbFileOnServer";

        $response = $this->runExec($execString);

        self::cuPrint_r($response);

    }

    public function setGambioConfigureRights444() {

        $paths = array(
            './admin/includes/configure.php',
            './admin/includes/configure.org.php',
            './includes/configure.org.php',
            './includes/configure.php',
        );

        foreach ($paths as $path) {
            $this->setChmod($path, 0444);
        }

        exit;
    }

    /**
     * @param string $filePath
     * @param int    $chmodMode
     */
    protected function setChmod($filePath, $chmodMode) {

        $result = 0;

        if (CuDemo::$activeModus) {
            $result = chmod($filePath, $chmodMode);

            if ($result !== 1) {
                $this->runExec("chmod $filePath $chmodMode");
            }
        }

        echo $result . '<br>';
    }

    /**
     *
     * @return \Cu_DBCredentials
     *
     * @since version
     */
    private function cu_getCredentialsFromJoomla() {

        $dbCredentials = new Cu_DBCredentials();

        if (file_exists('configuration.php')) {

            /** @noinspection UntrustedInclusionInspection */
            require_once 'configuration.php';

            $jConfig = @new JConfig();

            if ($jConfig instanceof JConfig) {

                $dbCredentials->setDbServer($jConfig->host);
                $dbCredentials->setDbUser($jConfig->user);
                $dbCredentials->setDbPassword($jConfig->password);
                $dbCredentials->setDbName($jConfig->db);
            }
        }

        return $dbCredentials;
    }
}

function cuExit() {

    $scriptName = $_SERVER['SCRIPT_NAME'];
    $link       = "<p><a href='$scriptName'>Zum Start</a></p>";

    echo $link;

    exit;
}

/**
 * @param $message
 *
 */
function cuAbort($message) {

    die($message);
    /** @noinspection PhpUnreachableStatementInspection */
    exit;
}

$dbFileName     = 'cuBackup_' . date('Ymd_His') . '.sql';
$backupFileName = 'cuBackup_' . date('Ymd_His') . '.zip';

$cuBackup = new Cu_Backup();

$dbCredentials = $cuBackup->cu_getCredentials($dbServer, $dbUser, $dbPassword, $dbName);

if ($dbCredentials->validate() === false) {
    die('No DataBase-Credentials found');
}

/** @noinspection PhpUndefinedConstantInspection */
$mysqlBackup =
    'mysqldump -h ' .
    $dbCredentials->getDbServer() .
    ' -u ' .
    $dbCredentials->getDbUser() .
    ' -p' .
    $dbCredentials->getDbPassword() .
    ' ' .
    $dbCredentials->getDbName() .
    " > $dbFileName";

$tarUnpack = "tar -xvzf $zipFileOnServer";

$defaultDir = './';

$action           = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$actionInput      = isset($_REQUEST['actionInput']) ? $_REQUEST['actionInput'] : '';
$actionInputField = isset($_REQUEST['actionInputField']) ? $_REQUEST['actionInputField'] : '';
$actionModus      = isset($_REQUEST['actionModus']) ? $_REQUEST['actionModus'] : '';

$dir = isset($actionInput[$action]) ? $actionInput[$action] : $defaultDir;

switch ($action) {

    case 'zip':

        $cuBackup->runExec($mysqlBackup);

        $allFiles = array();

        if ($actionModus[$action] === 'php') {

            $cuBackup->zipPHP($dir, $allFiles, $zipFileOnServer, false);

        } else {

            $cuBackup->zipExec($dir, $allFiles, $zipFileOnServer);
        }

        if (CuDemo::$activeModus) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($dbFileName);
        }

        cuExit();

        break;

    case 'zip selected':

        $backupDirs = isset($actionInputField[$action]) ? $actionInputField[$action] : 0;

        if ($backupDirs) {

            $actions[$action]['input-field']['valueDefault'] = $backupDirs;

            $backupDirs = explode("\n", $backupDirs);

            foreach ($backupDirs as &$backupDir) {
                $backupDir = trim($backupDir);

                if ($backupDir === '' || $backupDir === '/') {
                    continue;
                }

                $dir = './' . $backupDir;

                if (realpath($dir)) {

                    $zipFileOnServer = __DIR__ . '/cu_BackupPart' . '.zip';

                    $allFiles = array();

                    if ($actionModus[$action] === 'php') {

                        $cuBackup->zipPHP($dir, $allFiles, $zipFileOnServer, false);

                    } else {

                        $cuBackup->zipExec($dir, $allFiles, $zipFileOnServer);
                    }
                }
            }
            unset($backupDir);

            cuExit();


        }

        break;

    case 'saveDB':

        echo $action . '<br>';

        $cuBackup->runExec($mysqlBackup);

        break;

    case 'unpack':

        echo $action . '<br>';

        if (CuDemo::$activeModus) {

            if (file_exists($zipFileOnServer)) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $zip = new ZipArchive();
                $zip->open($zipFileOnServer);
                $zip->extractTo('.');
            } else {
                echo 'file not found - it must be: ' . $zipFileOnServer;
            }
        }

        cuExit();

        break;
    case 'restoreDB':

        echo $action . '<br>';

        $cuBackup->restoreDB($dbFileOnServer, $dbCredentials);

        cuExit();

        break;
    case 'phpinfo':

        if (CuDemo::$activeModus) {
            echo __DIR__ . '<br>';

            /** @noinspection ForgottenDebugOutputInspection */
            phpinfo();

        } else {
            echo 'PHPinfo not present, because you are in DemoModus';
        }

        exit;
        break;

    case 'deleteFiles (exec)':

        echo $action . '<br>';

        $execString = 'rm -r .';
        $result     = $cuBackup->runExec($execString);

        Cu_Backup::cuPrint_r($result);

        cuExit();
        break;

    case 'deleteFiles (php)':

        echo $action . '<br>';

        $allFiles = array();
        $cuBackup->scanDirRecursive('.', $allFiles);

        foreach ($allFiles as $file) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($file);
        }

        cuExit();

        break;

    case 'setFileRightGambioShop':

        echo $action . '<br>';

        $cuBackup->setGambioConfigureRights444();

        break;

}
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pack / Unpack Webpage</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

    <!-- Latest compiled and minified CSS & JS -->
    <link rel="stylesheet" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.js"></script>

    <style>

        hr {
            margin-top    : 0;
            margin-bottom : 0;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-default">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button"
                    class="navbar-toggle collapsed"
                    data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1"
                    aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="https://www.cusp.de">cuBackup</a>
        </div>

        <p class="navbar-text">
            Scriptstart: <?php echo date('H:i:s', CU_SCRIPT_START); ?>
        </p>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
                <li><a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?action=phpinfo" target="_blank">PHPInfo</a></li>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>

<div class="container">

    <div class="row">
        <div class="col-md-12">

            <h1>Pack and Unpack</h1>

            <form action="<?php $_SERVER['SCRIPT_NAME']; ?>" method="post" enctype="application/x-www-form-urlencoded">

                <?php foreach ($actions as $actionName => $action): ?>

                    <div class="radio">
                        <label>
                            <input type="radio" name="action" value="<?php echo $actionName; ?>">
                            <?php echo $action['text']; ?>
                        </label>
                    </div>

                    <?php

                    $actionModi = isset($action['modus']) ? $action['modus'] : array();
                    ?>


                    <div class="row">
                        <div class="col-md-10 col-md-offset-1">

                            <?php

                            foreach ($actionModi as $actionModus):
                                ?>
                                <div class="radio">
                                    <label>
                                        <input type="radio"
                                               name="actionModus[<?php /** @noinspection DisconnectedForeachInstructionInspection */
                                               echo $actionName; ?>]"
                                               value="<?php echo $actionModus['value']; ?>">
                                        <?php echo $actionModus['label']; ?></label>
                                </div>
                            <?php endforeach; ?>

                            <?php if (isset($action['input-field'])): ?>

                                <div class="form-group">
                                    <label for="actionInputField[<?php echo $actionName; ?>]"><?php echo $action['input-field']['label']; ?></label>
                                    <textarea id="actionInputField[<?php echo $actionName; ?>]"
                                              class="form-control"
                                              rows="7"
                                              name="actionInputField[<?php echo $actionName; ?>]"><?php echo $action['input-field']['valueDefault']; ?></textarea>
                                </div>

                            <?php endif; ?>

                        </div>
                    </div>


                    <?php if (isset($action['input'])): ?>

                        <div class="form-group">
                            <label>
                                <?php echo $action['input']['label']; ?>
                                <input name="actionInput[<?php echo $actionName; ?>]"
                                       value="<?php echo $action['input']['valueDefault']; ?>">
                            </label>
                        </div>

                    <?php endif; ?>

                    <hr>
                <?php endforeach; ?>

                <button class="btn btn-warning">RUN!</button>
            </form>
        </div>
    </div>

</div>
</body>
</html>