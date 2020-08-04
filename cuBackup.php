<?php
/*
 * Copyright by Jörg Wrase - www.Computer-Und-Sound.de
 *
 */

//Backup Script
//
//This Script will search for Wordpress, Joomla! and Gambio, DB-credentials. If not found, it will use the
// credentials from below

/* ************************/

// 0 => script will only return a blank page, nothing done (switched off) || 1 = will run
$scriptIsActive = 0;

/* Enter some db-Credentials here - if no other credentials will be found, this will be used */
$dbServer   = '';
$dbUser     = '';
$dbPassword = '';
$dbName     = '';

$zipFileOnServer   = 'cuBackup.zip'; // File on Server to unpack
$tarGzFileOnServer = 'cuBackup.tar.gz'; // File on Server for tar.gz
$dbFileOnServer    = 'cuBackup.sql'; // File on Server to for db


// End Edit **********************************************************
// End Edit **********************************************************
// End Edit **********************************************************

define('CU_BACKUP_VERSION', '2.0.0');

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

define('CU_SCRIPT_START', time());
define('CU_SCRIPT_MAX_TIME', 2);

$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

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

$isDefaultClass = 'cuB__default';

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
    public function getSize()
    {

        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {

        $this->size = (int)$size;
    }

    /**
     * @return mixed
     */
    public function getName()
    {

        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {

        $this->name = (string)$name;
    }

    /**
     * @return string
     */
    public function getOwner()
    {

        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner($owner)
    {

        $this->owner = (string)$owner;
    }

    /**
     * @return string
     */
    public function getRights()
    {

        return $this->rights;
    }

    /**
     * @param string $rights
     */
    public function setRights($rights)
    {

        $this->rights = (string)$rights;
    }

    /**
     * @return string
     */
    public function getPath()
    {

        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {

        $this->path = (string)$path;
    }


}

/**
 * @param string $dir
 * @param string $directoryListAsString
 *
 * @return array
 */
function getDirsFromDir($dir, &$directoryListAsString)
{

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
        'text'  => 'Create a zip-file from whole directory and all subdirectories - database backup included
        <br><strong>In most cases, one of these two options is the best choice</strong>',
        'input' => [
            'label'        => 'Wich directory should be zipped?',
            'valueDefault' => './',
        ],
        'modus' => [
            [
                'label' => 'Try it with PHP-ZipArchive - slower and can run into server-timeout. 
                But use this if php-exec is not possible',
                'value' => 'php',
            ],
            ['label' => 'Try with php-exec. If you got a timeout-error please wait: php-exec runs 
            longer than the php-script!',
             'value' => 'exec'],
        ],
        'isDefault' => true
    ],
    'zip selected'           => [
        'text' => 'Creates an ZipFile from directory (with all subdirectories) without Database-backup. 
        Use this if you got an timeout-error with php-ZipArchive (and php-exe is not possible) OR the file is to big.',

        'input - field' => [
            'label'        => 'Wich directory should be zipped? Each Directory in one line',
            'valueDefault' => '',
        ],
        'modus'         => [
            [
                'label' => 'Try with php-ZipArchive (see above)',
                'value' => 'php',
            ],
            ['label' => 'Try with php-exec', 'value' => 'exec'],
        ],
    ],
    'saveDB'                 => ['text' => 'Try to create an Database-backup'],
    'unpack'                 => ['text' => 'Try to extract the file ' . $zipFileOnServer],
    'restoreDB'              => ['text' => 'Try to restore Database from file ' . $dbFileOnServer],
    'deleteFiles (exec)'     => ['text' => 'Remove all Files from this Dir (recursive) with php-exec'],
    'deleteFiles (php)'      => ['text' => 'Remove all Files from this Dir (recursive) with PHP (unlink)'],
    'setFileRightGambioShop' => ['text' => 'Try to set the configure.org.php and configure.php files from 
                                            Gambio-Shops to chmod 444'],
];

define('ERROR_STATUS_INFO', 'info');
define('ERROR_STATUS_WARNING', 'warning');
define('ERROR_STATUS_DANGER', 'danger');

class Output
{
    protected static $message = '';


    public static function add($content, $withBr = true)
    {

        self::$message .= $withBr ? $content . '<br>' : $content;
    }

    public static function addParagraphHeading($content)
    {

        self::add("<h4>" . $content . "</h4>");

    }

    public static function addStrong($content)
    {

        self::add("<strong>" . $content . "</strong>");

    }

    public static function addErrorMessage($error)
    {

        self::add("<h3 style='color: red;' >" . $error . "</h3>");

    }

    public static function addError(Exception $catcher)
    {

        $message = $catcher->getMessage() . ' in ' . $catcher->getFile() . ':' . $catcher->getLine();
        self::addErrorMessage($message);

        $stack = $catcher->getTraceAsString();
        self::add($stack);

    }

    /**
     * @param string[] $multiLinesContent
     */
    public static function addMultiLines(array $multiLinesContent)
    {

        $content = implode("\n", $multiLinesContent);
        self::add($content);
    }

    public static function getMessage()
    {

        return self::$message;
    }

    public static function reset()
    {

        self::$message = '';
    }

    public static function hasContent()
    {

        return self::$message !== '';
    }


}

/**
 * Class CuError
 */
class CuError
{

    protected $errorMessage = '';
    protected $errorStatus  = '';

    /**
     * CuError constructor.
     *
     * @param string $errorMessage
     * @param string $errorStatus
     */
    public function __construct($errorMessage, $errorStatus = ERROR_STATUS_DANGER)
    {

        $this->errorMessage = $errorMessage;
        $this->errorStatus  = $errorStatus;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {

        return $this->errorMessage;
    }

    /**
     * @return string
     */
    public function getErrorStatus()
    {

        return $this->errorStatus;
    }


}

/**
 * Class CuErrorContainer
 */
class CuErrorContainer
{

    protected static $errors = [];

    /**
     * @param string $message
     * @param string $errorStatus
     */
    public static function createAndAdd($message, $errorStatus = ERROR_STATUS_DANGER)
    {

        $error = new CuError($message, $errorStatus);

        self::add($error);

    }

    /**
     * @param CuError $cuError
     */
    public static function add(CuError $cuError)
    {

        self::$errors[] = $cuError;

    }

    /**
     * @return CuError|null
     */
    public static function shift()
    {

        return array_shift(self::$errors);

    }


    /**
     * @return CuError[]
     */
    public static function getErrors()
    {

        return self::$errors;
    }

    /**
     * @return bool
     */
    public static function hasErrors()
    {

        return count(self::$errors) > 0;
    }

}


/**
 * Class Cu_DBCredentials
 */
class DbCredentials
{

    private $dbServer   = '';
    private $dbUser     = '';
    private $dbPassword = '';
    private $dbName     = '';

    /**
     * @return string
     */
    public function getDbServer()
    {

        return $this->dbServer;
    }

    /**
     * @param string $dbHost
     */
    public function setDbServer($dbHost)
    {

        $this->dbServer = (string)$dbHost;
    }

    /**
     * @return string
     */
    public function getDbUser()
    {

        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     */
    public function setDbUser($dbUser)
    {

        $this->dbUser = (string)$dbUser;
    }

    /**
     * @return string
     */
    public function getDbPassword()
    {

        return $this->dbPassword;
    }

    /**
     * @param string $dbPassword
     */
    public function setDbPassword($dbPassword)
    {

        $this->dbPassword = (string)$dbPassword;
    }

    /**
     * @return string
     */
    public function getDbName()
    {

        return $this->dbName;
    }

    /**
     * @param string $dbName
     */
    public function setDbName($dbName)
    {

        $this->dbName = (string)$dbName;
    }


}

/**
 * Class Cu_Backup
 */
class Backup
{

    protected $sqlFileName;

    public static function cuPrint_r($value, $htmlEntities = true)
    {

        $messageRaw = print_r($value, true);

        $message = $htmlEntities ? htmlentities($messageRaw) : $messageRaw;

        Output::add("$message\n");
    }

    /**
     * @param $dbServerDefaultValue
     * @param $dbUserDefaultValue
     * @param $dbPasswordDefaultValue
     * @param $dbNameDefaultValue
     *
     * @return DbCredentials
     */
    public function cu_getCredentials(
        $dbServerDefaultValue,
        $dbUserDefaultValue,
        $dbPasswordDefaultValue,
        $dbNameDefaultValue
    )
    {

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
     * @return DbCredentials
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
    )
    {

        $dbCredentials = new DbCredentials();

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
    public function tarGz($tarGzFileOnServer)
    {

        $this->runExec("tar -vczf $tarGzFileOnServer ./.");

    }

    /**
     * @param      $execString
     *
     * @param bool $printResult
     *
     * @return array
     */
    public function runExec($execString, $printResult = true)
    {

        $return = '';

        self::cuPrint_r(['ExecStr' => $execString]);
        $output = '';

        $result = exec($execString, $output, $return);

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
     * @param string $dir
     * @param string $zipFileOnServer
     */
    public function zipExec($dir, $zipFileOnServer)
    {

        /* zip - exe */

        $response = $this->runExec("zip -r $zipFileOnServer $dir");

        if ($response['result'] !== 1) {
            Output::add('error zip exec: ' . $response['result']);
        }

    }

    /**
     * @param string $dir
     * @param array  $allFiles
     * @param string $zipFileOnServer
     * @param bool   $setTimestamp
     *
     * @return int
     */
    public function zipPHP($dir, &$allFiles, $zipFileOnServer)
    {

        Output::add($zipFileOnServer . '<br>');

        $allFilesCount = $this->scanDirRecursive($dir, $allFiles);

        $zip = new ZipArchive();
        Output::add('ErrorCode: ' . $zip->open($zipFileOnServer, ZipArchive::CREATE) . ' (1 === OK)<br>');

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


        return $allFilesCount;

    }

    /**
     * @param string $dir
     * @param array  $allFiles
     *
     * @return int
     */
    public function scanDirRecursive($dir, &$allFiles)
    {

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
     * @param string        $dbFileOnServer
     * @param DbCredentials $cuDBCredentials
     */
    public function restoreDB($dbFileOnServer, DbCredentials $cuDBCredentials)
    {

        Output::add('restore Backup');

        $dbUser     = $cuDBCredentials->getDbUser();
        $dbPassword = $cuDBCredentials->getDbPassword();
        $dbName     = $cuDBCredentials->getDbName();

        $execString = "mysql -u $dbUser -p$dbPassword $dbName < $dbFileOnServer";

        $response = $this->runExec($execString);

        self::cuPrint_r($response);

    }

    public function setGambioConfigureRights444()
    {

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

    public function deleteSqlBackupFile()
    {

        if ($this->sqlFileName && file_exists($this->sqlFileName)) {
            $this->removeFile($this->sqlFileName);
        }
    }

    /**
     * @param string $filePath
     */
    public function removeFile($filePath)
    {

        if (file_exists($filePath)) {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($filePath);
            $this->runExec('rm $filePath');
        }
    }

    /**
     * @param string $sqlFileName
     */
    public function setSqlFileName($sqlFileName)
    {

        $this->sqlFileName = $sqlFileName;
    }

    /**
     * @param string $directoryToKill
     */
    public function removeFilesRecursive($directoryToKill)
    {

        Output::add("<br>Remove directory: $directoryToKill<br>");

        $filesInThisDir = scandir($directoryToKill, SCANDIR_SORT_ASCENDING);

        self::cuPrint_r(['Files found: ' => $filesInThisDir]);

        foreach ($filesInThisDir as $file) {

            if ($file === '.' || $file === '..') {
                continue;
            }

            if ($file === 'cuBackup.php' || $file === 'cuBackup.zip') {
                continue;
            }

            $item = $directoryToKill . DIRECTORY_SEPARATOR . $file;

            Output::add("Delete $item <br>");


            try {
                if (is_dir($item) === true) {

                    $this->removeFilesRecursive($item . DIRECTORY_SEPARATOR);

                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    if (@rmdir($item) !== true) {
                        $exceptionMessage = "Not able to remove directory $item";
                        throw new RuntimeException($exceptionMessage, 3);
                    }

                } else {
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    /** @noinspection PhpUsageOfSilenceOperatorInspection */
                    if (is_file($item) === true && @unlink($item) !== true) {
                        $exceptionMessage = "Not able to unlink file $item";
                        throw new RuntimeException($exceptionMessage, 3);
                    }
                }
            } catch (Exception $exception) {

                switch ($exception->getCode()) {
                    case 1:
                        $status = ERROR_STATUS_INFO;
                        break;
                    case 2:
                        $status = ERROR_STATUS_WARNING;
                        break;
                    default:
                        $status = ERROR_STATUS_DANGER;
                }

                CuErrorContainer::createAndAdd($exception->getMessage(), $status);

            }
        }
    }

    /**
     * @param string $filePath
     * @param int    $chmodMode
     */
    protected function setChmod($filePath, $chmodMode)
    {

        if (file_exists($filePath) === false) {
            throw new RuntimeException("File $filePath not found");
        }

        $result = chmod($filePath, $chmodMode);

        if ($result !== 1) {
            $this->runExec("chmod $filePath $chmodMode");
        }

        Output::add($result . '<br>');
    }
}

function cuExit()
{

    $scriptName = $_SERVER['SCRIPT_NAME'];
    $link       = "<p><a href=\"$scriptName\">Zum Start</a></p>";

    echo $link;

    exit;
}

$action           = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$actionInput      = isset($_REQUEST['actionInput']) ? $_REQUEST['actionInput'] : '';
$actionInputField = isset($_REQUEST['actionInputField']) ? $_REQUEST['actionInputField'] : '';
$actionModus      = isset($_REQUEST['actionModus']) ? $_REQUEST['actionModus'] : '';

$dbFileName = 'cuBackup_' . date('Ymd_His') . '.sql';

$cuBackup = new Backup();

$dbCredentials = $cuBackup->cu_getCredentials($dbServer, $dbUser, $dbPassword, $dbName);

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


try {


    switch ($action) {

        case 'test exec':

            Output::addParagraphHeading('Action: ' . $action);

            Output::add("This will try to run the 'dir' command with php exec.");
            Output::addStrong('This will work on most windows machines, but it is not sure, that it will work
            on Linux, too');
            Output::addStrong('It will only check, if it is allowed to run an exec command on Linux');
            Output::addStrong("If the 'dir' command will work, it is not made sure, that other 
            commands will work, too");

            $cuBackup->runExec('dir');

            break;

        case 'tar':

            Output::addParagraphHeading('Action: ' . $action);

            $cuBackup->runExec($mysqlBackup);
            $cuBackup->setSqlFileName($dbFileName);
            $cuBackup->tarGz($tarGzFileOnServer);
            $cuBackup->deleteSqlBackupFile();
            break;

        case 'zip':

            Output::addParagraphHeading('Action: ' . $action);

            $cuBackup->runExec($mysqlBackup);
            $cuBackup->setSqlFileName($dbFileName);

            $allFiles = [];

            if ($actionModus[$action] === 'php') {

                $cuBackup->zipPHP($dir, $allFiles, $zipFileOnServer);

            } else {

                $cuBackup->zipExec($dir, $zipFileOnServer);
            }

            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            @unlink($dbFileName);

            break;

        case 'zip selected':

            Output::addParagraphHeading('Action: ' . $action);

            $backupDirs = isset($actionInputField[$action]) ? $actionInputField[$action] : 0;

            if ($backupDirs) {

                $actions[$action]['input - field']['valueDefault'] = $backupDirs;

                $backupDirs = explode("\n", $backupDirs);

                $counter = 10;

                foreach ($backupDirs as &$backupDir) {
                    $backupDir = trim($backupDir);

                    if ($backupDir === '' || $backupDir === ' / ') {
                        continue;
                    }

                    $dir = './' . $backupDir;

                    if (realpath($dir)) {

                        $zipFileOnServerName     = $counter . '_' . $zipFileOnServer;
                        $zipFileOnServerFullPath = __DIR__ . '/' . $zipFileOnServerName;

                        Output::addParagraphHeading("Zip $zipFileOnServer to $zipFileOnServerName");

                        $counter++;

                        $allFiles = [];

                        if ($actionModus[$action] === 'php') {

                            $cuBackup->zipPHP($dir, $allFiles, $zipFileOnServerFullPath);
                            Output::addStrong("Files: ");
                            Output::addMultiLines($allFiles);

                        } else {

                            $cuBackup->zipExec($dir, $zipFileOnServerFullPath);
                            Output::addStrong("Files: ");
                            Output::addMultiLines($allFiles);
                        }
                    } else {
                        Output::addErrorMessage("Path $dir not found");
                    }
                }
                unset($backupDir);

            }

            break;

        case 'saveDB':

            Output::addParagraphHeading('Action: ' . $action);

            $cuBackup->runExec($mysqlBackup);
            $cuBackup->setSqlFileName($dbFileName);

            break;

        case 'unpack':

            Output::addParagraphHeading('Action: ' . $action);

            if (file_exists($zipFileOnServer)) {
                $zip = new ZipArchive();
                $zip->open($zipFileOnServer);
                $zip->extractTo('.');
            } else {
                Output::addErrorMessage('File to unpack not found - it must be: ' . $zipFileOnServer);
            }

            break;
        case 'restoreDB':

            Output::addParagraphHeading('Action: ' . $action);

            $cuBackup->restoreDB($dbFileOnServer, $dbCredentials);

            break;
        case 'phpinfo':

            echo __DIR__ . ' <br>';

            /** @noinspection ForgottenDebugOutputInspection */
            phpinfo();

            exit;

        case 'deleteFiles (exec)':

            Output::addParagraphHeading('Action: ' . $action);

            $execString = 'rm -rf . ';
            $result     = $cuBackup->runExec($execString);

            Backup::cuPrint_r($result);

            break;

        case 'deleteFiles (php)':

            Output::addParagraphHeading('Action: ' . $action);

            $thisDir = dirname($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $_SERVER['PHP_SELF']);

            $cuBackup->removeFilesRecursive($thisDir);

            break;

        case 'setFileRightGambioShop':

            Output::addParagraphHeading('Action: ' . $action);

            $cuBackup->setGambioConfigureRights444();

            break;

    }

} catch (Exception $catcher) {
    Output::addError($catcher);
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

    <script src="http://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
          integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk"
          crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
            integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
            integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
            crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"
            integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI"
            crossorigin="anonymous"></script>


    <style>

        hr {
            margin-top:    0;
            margin-bottom: 0;
        }

        .cuB__default {
            background: #32f811;
        }

    </style>

    <!--suppress JSUnresolvedFunction, JSUnusedGlobalSymbols -->
    <script type="text/javascript">


        $(function () {

            function removeAllChecked() {

                $("input[type=radio]").each(function () {
                    this.checked = false;
                });

            }

            $("input").on("change", function () {

                var parentName = $(this).data("parent-name");
                removeAllChecked();
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
                <span class="navbar-text strong">v<?php echo CU_BACKUP_VERSION; ?></span>
            </li>
        </ul>

    </div>
</nav>

<div class="container">

    <?php if (CuErrorContainer::hasErrors()): ?>

        <div class="row">
            <div class="col-md">

                <?php while ($error = CuErrorContainer::shift()): ?>

                    <div class="alert alert-<?php echo $error->getErrorStatus(); ?>">
                        <?php echo $error->getErrorMessage(); ?></div>

                <?php endwhile; ?>

            </div>
        </div>

    <?php endif; ?>

    <?php if (Output::hasContent()): ?>

        <div class="row">
            <div class="col-md">

                <h3>Result from Action: <?php echo $action ? (string)$action : 'unknown'; ?></h3>

                <div class="alert alert-primary">
                    <pre><?php echo Output::getMessage(); ?></pre>
                </div>

            </div>
        </div>

    <?php endif; ?>


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
                    <li>PHP has a time-limit for scripts: normal with php-exec this doesn't matter.</li>
                </ul>

                <p>So first try to run php-exec if possible. Because of the script time-out from php it could be that
                   you got an error on this page (timeout BUT the process continues! If the process is still running,
                   you will see the file on the server is becoming bigger.</p>

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
        <div class="col">

            <form action="<?php $_SERVER['SCRIPT_NAME']; ?>" method="post" enctype="application/x-www-form-urlencoded">

                <?php
                foreach ($actions as $actionName => $action):

                    /** @var bool $isDefault */
                    $isDefault = isset($action['isDefault']) ? $action['isDefault'] : false;

                ?>

                    <div class="form-group form-check <?php echo ($isDefault ? $isDefaultClass : '') ?>">
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
                                               data-parent-name="<?php
                                               echo $actionName; ?>"
                                               name="actionModus[<?php
                                               echo $actionName; ?>]"
                                               value="<?php echo $actionModus['value']; ?>">
                                        <?php echo $actionModus['label']; ?></label>
                                </div>
                            <?php endforeach; ?>

                            <?php if (isset($action['input - field'])): ?>

                                <div class="form-group">
                                    <label for="actionInputField[<?php echo $actionName; ?>]">
                                        <?php echo $action['input - field']['label']; ?></label>
                                    <textarea id="actionInputField[<?php echo $actionName; ?>]"
                                              class="form-control"
                                              rows="7"
                                              name="actionInputField[<?php echo $actionName; ?>]"><?php
                                        echo $action['input - field']['valueDefault']; ?></textarea>
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


                <div class="row">
                    <div class="col mb-5 mt-3 pb-5">
                        <button class="btn btn-danger">RUN!</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
</body>
</html>