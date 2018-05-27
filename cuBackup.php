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

/* Enter some db-Credentials here - if no other credentials will be found, this will be used */
/** @noinspection PhpUnreachableStatementInspection */
$dbServer   = '';
$dbUser     = '';
$dbPassword = '';
$dbName     = '';

$zipFileOnServer   = 'cuBackup.zip'; // File on Server to unpack
$tarGzFileOnServer = 'cuBackup.tar.gz'; // File on Server for tar.gz
$dbFileOnServer    = 'cuBackup.sql'; // File on Server to for db

$version = '1.2.1';

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

/*DemoModus*/
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
/** @noinspection PhpUndefinedClassInspection */

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

/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('display_errors', 'on');
/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('html_errors', 'on');

error_reporting(E_ALL);

/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('max_execution_time', '360');
/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('max_input_time', '240');
/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('memory_limit', '512M');
/** @noinspection PhpUsageOfSilenceOperatorInspection */
@ini_set('max_input_vars', '5500');

if ($scriptIsActive !== 1) {
    exit;
}
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
/** @noinspection PhpUndefinedClassInspection */

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

    $dirs        = [];
    $directories = glob($dir . '*', GLOB_ONLYDIR);

    sort($directories, SORT_STRING | SORT_FLAG_CASE);

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

$actions = [
    'test exec'              => ['text' => 'Check if php-exec is possible (belongs to the server)'],
    'zip'                    => [
        'text'  => 'Create a zip-file from whole directory and all subdirectories - database backup included',
        'input' => [
            'label'        => 'Wich directorie should be zipped?',
            'valueDefault' => './',
        ],
        'modus' => [
            [
                'label' => 'Try it with PHP-ZipArchive - slower and can run into servertimeout. But use this if php-exex is not possible',
                'value' => 'php',
            ],
            ['label' => 'Try with php-exec. If you got a timeout-error please wait: php-exec runs longer than the php-script!',
             'value' => 'exec'],
        ],
    ],
    'zip selected'           => [
        'text' => 'Creates an ZipFile from directory (with all subdirectories) without Database-backup. Use this if you got an timeout-error with php-ZipArchive (and php-exe is not possible) OR the file is to big.',

        'input-field' => [
            'label'        => 'Directorypath from this file',
            'valueDefault' => $directoryListAsString,
        ],
        'modus'       => [
            [
                'label' => 'Try with php-ZipArchive (see above)',
                'value' => 'php',
            ],
            ['label' => 'Try with php-exec', 'value' => 'exec'],
        ],
    ],
    'saveDB'                 => ['text' => 'Try to create an Databasebackup'],
    'unpack'                 => ['text' => 'Trys to extract the file ' . $zipFileOnServer],
    'restoreDB'              => ['text' => 'Trys to restore Database from file ' . $dbFileOnServer],
    'deleteFiles (exec)'     => ['text' => 'Remove all Files from this Dir (recursive) with php-exec'],
    'deleteFiles (php)'      => ['text' => 'Remove all Files from this Dir (recursive) with PHP (unlink)'],
    'setFileRightGambioShop' => ['text' => 'Try to set the configure.org.php and configure.php files from Gambio-Shops to chmod 444'],
];
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
/** @noinspection PhpUndefinedClassInspection */

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


}

/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
/** @noinspection PhpUndefinedClassInspection */

/**
 * Class Cu_Backup
 */
class Cu_Backup
{

    protected $sqlFileName;

    /**
     * @param $dbServer
     * @param $dbUser
     * @param $dbPassword
     * @param $dbName
     *
     * @return \Cu_DBCredentials
     */
    public function cu_getCredentials(
        $dbServerDefaultValue,
        $dbUserDefaultValue,
        $dbPasswordDefaultValue,
        $dbNameDefaultValue
    ) {

        $configs = [];

        $configs['gambio'] = [
            'file'          => __DIR__ . '/admin/includes/configure.php',
            'constantNames' => [
                'dbServer'   => 'DB_SERVER',
                'dbName'     => 'DB_DATABASE',
                'dbUser'     => 'DB_SERVER_USERNAME',
                'dbPassword' => 'DB_SERVER_PASSWORD',

            ],
        ];

        $configs['wordpress'] = [
            'file'          => __DIR__ . '/wp-config.php',
            'constantNames' => [
                'dbServer'   => 'DB_HOST',
                'dbName'     => 'DB_NAME',
                'dbUser'     => 'DB_USER',
                'dbPassword' => 'DB_PASSWORD',

            ],
        ];

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
                                                                $dbServerDefaultValue,
                                                                $constantName_dbName,
                                                                $dbNameDefaultValue,
                                                                $constantName_dbUser,
                                                                $dbUserDefaultValue,
                                                                $constantName_dbPassword,
                                                                $dbPasswordDefaultValue);

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

        $dbServer = defined($constantName_dbServer) ? constant($constantName_dbServer) : $defaultValue_dbServer;
        $dbName   = defined($constantName_dbName) ? constant($constantName_dbName) : $defaultValue_dbName;
        $dbUser   = defined($constantName_dhUser) ? constant($constantName_dhUser) : $defaultValue_dbHUser;
        $dbPassword
                  = defined($constantName_dhPassword) ? constant($constantName_dhPassword) : $defaultValue_dbPassword;

        $dbCredentials->setDbServer($dbServer);
        $dbCredentials->setDbName($dbName);
        $dbCredentials->setDbUser($dbUser);
        $dbCredentials->setDbPassword($dbPassword);

        return $dbCredentials;
    }

    /**
     * @param string $tarGzFileOnServer
     */
    public function tarGz($tarGzFileOnServer) {

        $this->runExec("tar -vczf $tarGzFileOnServer ./.");

    }

    /**
     * @param      $execString
     *
     * @param bool $printResult
     *
     * @return array
     */
    public function runExec($execString, $printResult = true) {

        $result = '';
        $return = '';

        self::cuPrint_r(['ExecStr' => $execString]);

        if (CuDemo::$activeModus) {

            $result = exec($execString, $output, $return);

        }

        $output = is_array($output) ? implode("\n",
                                              $output) : $output;

        $response['execStr'] = $execString;
        $response['result']  = $result;
        $response['output']  = $output;
        $response['return']  = $return;

        if ($printResult) {
            self::cuPrint_r($response);
        }

        return $response;

    }

    /**
     * @param $value
     */
    public static function cuPrint_r($value) {

        $valuePrint_r = print_r($value, true);

        echo "<pre>$valuePrint_r</pre>";
    }

    /**
     * @param string $dir
     * @param array  $allFiles
     * @param string $zipFileOnServer
     */
    public function zipExec($dir, &$allFiles, $zipFileOnServer) {

        /* zip - exe */

        $response = $this->runExec("zip -r $zipFileOnServer $dir");

        if ($response['result'] !== 1) {
            echo 'error zip exec: ' . $response['result'];
        }

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

        $files = scandir($dir, SCANDIR_SORT_ASCENDING);

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

        $paths = [
            './admin/includes/configure.php',
            './admin/includes/configure.org.php',
            './includes/configure.org.php',
            './includes/configure.php',
        ];

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

    public function deleteSqlBackupFile() {

        if ($this->sqlFileName && file_exists($this->sqlFileName)) {
            $this->removeFile($this->sqlFileName);
        }
    }

    /**
     * @param string $filePath
     */
    public function removeFile($filePath) {

        if (file_exists($filePath)) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($filePath);
            $this->runExec('rm $filePath');
        }
    }

    /**
     * @param string $sqlFileName
     */
    public function setSqlFileName($sqlFileName) {

        $this->sqlFileName = $sqlFileName;
    }

}

function cuExit() {

    $scriptName = $_SERVER['SCRIPT_NAME'];
    $link       = "<p><a href=\"$scriptName\">Zum Start</a></p>";

    echo $link;

    exit;
}

/**
 * @param $message
 */
function cuAbort($message) {

    die($message);
}

$action           = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$actionInput      = isset($_REQUEST['actionInput']) ? $_REQUEST['actionInput'] : '';
$actionInputField = isset($_REQUEST['actionInputField']) ? $_REQUEST['actionInputField'] : '';
$actionModus      = isset($_REQUEST['actionModus']) ? $_REQUEST['actionModus'] : '';

$dbFileName = 'cuBackup_' . date('Ymd_His') . '.sql';

$cuBackup = new Cu_Backup();

$dbCredentials = $cuBackup->cu_getCredentials($dbServer, $dbUser, $dbPassword, $dbName);

/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection SpellCheckingInspection */
$mysqlBackup
    = 'mysqldump -h ' .
      $dbCredentials->getDbServer() .
      ' -u ' .
      $dbCredentials->getDbUser() .
      ' -p' .
      $dbCredentials->getDbPassword() .
      ' ' .
      $dbCredentials->getDbName() .
      " > $dbFileName";

/** @noinspection SpellCheckingInspection */
$tarUnpack = "tar -xvzf $zipFileOnServer";

$defaultDir = ' ./';

$dir = isset($actionInput[$action]) ? $actionInput[$action] : $defaultDir;

switch ($action) {

    case 'test exec':
        $response = $cuBackup->runExec('dir');

        Cu_Backup::cuPrint_r($response);

        break;

    case 'tar':
        $cuBackup->runExec($mysqlBackup);
        $cuBackup->setSqlFileName($dbFileName);
        $cuBackup->tarGz($tarGzFileOnServer);
        $cuBackup->deleteSqlBackupFile();
        break;

    case 'zip':

        $cuBackup->runExec($mysqlBackup);
        $cuBackup->setSqlFileName($dbFileName);

        $allFiles = [];

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

            $actions[$action]['input - field']['valueDefault'] = $backupDirs;

            $backupDirs = explode("\n", $backupDirs);

            foreach ($backupDirs as &$backupDir) {
                $backupDir = trim($backupDir);

                if ($backupDir === '' || $backupDir === ' / ') {
                    continue;
                }

                $dir = ' ./' . $backupDir;

                if (realpath($dir)) {

                    $zipFileOnServer = __DIR__ . ' / cu_BackupPart' . ' . zip';

                    $allFiles = [];

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

        echo $action . ' <br>';

        $cuBackup->runExec($mysqlBackup);
        $cuBackup->setSqlFileName($dbFileName);

        break;

    case 'unpack':

        echo $action . ' < br>';

        if (CuDemo::$activeModus) {

            if (file_exists($zipFileOnServer)) {
                /** @noinspection PhpUsageOfSilenceOperatorInspection */
                $zip = new ZipArchive();
                $zip->open($zipFileOnServer);
                $zip->extractTo(' . ');
            } else {
                echo 'file not found - it must be: ' . $zipFileOnServer;
            }
        }

        cuExit();

        break;
    case 'restoreDB':

        echo $action . ' <br>';

        $cuBackup->restoreDB($dbFileOnServer, $dbCredentials);

        cuExit();

        break;
    case 'phpinfo':

        if (CuDemo::$activeModus) {
            echo __DIR__ . ' <br>';

            /** @noinspection ForgottenDebugOutputInspection */
            phpinfo();

        } else {
            echo 'PHPinfo not present, because you are in DemoModus';
        }

        exit;
        break;

    case 'deleteFiles(exec)':

        echo $action . ' < br>';

        $execString = 'rm - r . ';
        $result     = $cuBackup->runExec($execString);

        Cu_Backup::cuPrint_r($result);

        cuExit();
        break;

    case 'deleteFiles(php)':

        echo $action . ' < br>';

        $allFiles = [];
        $cuBackup->scanDirRecursive(' . ', $allFiles);

        foreach ($allFiles as $file) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($file);
        }

        cuExit();

        break;

    case 'setFileRightGambioShop':

        echo $action . ' < br>';

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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

    <link rel="stylesheet" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.js"></script>

    <style>

        hr {
            margin-top:    0;
            margin-bottom: 0;
        }

    </style>

    <script type="text/javascript">


        $(function () {

            function removeAllChecked() {

                $("input[type=radio]").each(function () {
                    this.checked = false;
                });

            }

            $("input").on("change", function () {

                removeAllChecked();
                var parentName = $(this).data("parent-name");
                $("input[data-level=0][value='" + parentName + "']").click();
                this.checked = true;
            });

        });

    </script>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Service from Jörg Wrase - cusp.de</a>
    <button class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#">cuBackup<span class="sr-only">(current)</span></a>
            </li>
        </ul>

        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="?action=phpinfo" class="nav-link" target="_blank">PHPInfo</a>
            </li>
            <li class="nav-item">
                <span class="navbar-text">v<?php echo $version; ?></span>
            </li>
        </ul>

    </div>
</nav>

<div class="container">

    <div class="row">
        <div class="col-md-12">

            <div class="alert alert-info">

                <h2>How to...</h2>

                <p>There are two ways how this script can try to make a backup:</p>

                <ol>
                    <li>With the php exec - function</li>
                    <li>With the php ZipArchive - function</li>
                </ol>

                <p><strong>PHP exec:</strong>: Not all servers do allow this. The advantages are:</p>

                <ul>
                    <li>Fast</li>
                    <li>PHP has a time-limit for scripts: normaly with php-exec this doesn't matter.</li>
                </ul>

                <p>So first try to run php-exec if possible. Because of the script time-out from php it could be that
                   you got an error on this page (timeout BUT the process continues! If the process is still running,
                   you will see the file on the server is becomming bigger.</p>

            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <p>
                <button class="btn btn-primary"
                        type="button"
                        data-toggle="collapse"
                        data-target="#collapseDBInfo"
                        aria-expanded="false"
                        aria-controls="collapseDBInfo">
                    Show Database Info
                </button>
            </p>

            <div class="alert alert-warning collapse" id="collapseDBInfo">
                <h4>Found credentials:</h4>

                <dl class="link_list dl-horizontal">
                    <dt>Server</dt>
                    <dd><?php echo $dbCredentials->getDbServer(); ?></dd>

                    <dt>Datenbankname</dt>
                    <dd><?php echo $dbCredentials->getDbName(); ?></dd>

                    <dt>Username</dt>
                    <dd><?php echo $dbCredentials->getDbUser(); ?></dd>

                    <dt>Passwort</dt>
                    <dd><?php echo $dbCredentials->getDbPassword(); ?></dd>
                </dl>

            </div>


        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <h1>Pack and Unpack</h1>

            <form action="<?php $_SERVER['SCRIPT_NAME']; ?>" method="post" enctype="application/x-www-form-urlencoded">

                <?php foreach ($actions as $actionName => $action): ?>

                    <div class="form-group form-check">
                        <label>
                            <input class="form-check-input"
                                   type="radio"
                                   name="action"
                                   data-level="0"
                                   value="<?php echo $actionName; ?>">
                            <?php echo $action['text']; ?>

                            <?php

                            $actionModi = isset($action['modus']) ? $action['modus'] : [];

                            foreach ($actionModi as $actionModus):
                                ?>
                                <div class="form-group form-check">
                                    <label>
                                        <input type="radio"
                                               data-level="1"
                                               data-parent-name="<?php /** @noinspection DisconnectedForeachInstructionInspection */
                                               echo $actionName; ?>"
                                               name="actionModus[<?php /** @noinspection DisconnectedForeachInstructionInspection */
                                               echo $actionName; ?>]"
                                               value="<?php echo $actionModus['value']; ?>">
                                        <?php echo $actionModus['label']; ?></label>
                                </div>
                            <?php endforeach; ?>

                            <?php if (isset($action['input - field'])): ?>

                                <div class="form-group">
                                    <label for="actionInputField[<?php echo $actionName; ?>]"><?php echo $action['input -
                                                                                                                  field']['label']; ?></label>
                                    <textarea id="actionInputField[<?php echo $actionName; ?>]"
                                              class="form-control"
                                              rows="7"
                                              name="actionInputField[<?php echo $actionName; ?>]"><?php echo $action['input -
                                                                                                                      field']['valueDefault']; ?></textarea>
                                </div>

                            <?php endif; ?>


                            <?php if (isset($action['input'])): ?>

                                <div class="form-group">
                                    <label>
                                        <?php echo $action['input']['label']; ?>
                                        <input type="text"
                                               name="actionInput[<?php echo $actionName; ?>]"
                                               value="<?php echo $action['input']['valueDefault']; ?>">
                                    </label>
                                </div>

                            <?php endif; ?>


                        </label>
                    </div>


                    <hr>
                <?php endforeach; ?>

                <button class="btn btn-danger">RUN!</button>
            </form>
        </div>
    </div>

</div>
</body>
</html>