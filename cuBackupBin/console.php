<?php
/*
 * Copyright by Jörg Wrase - www.Computer-Und-Sound.de
 * Date: 28.05.2018
 * Time: 01:58
 * 
 * Created by PhpStorm
 *
 */

$versionFilePath = __DIR__ . '/../cuBackup.php';

/** @noinspection AutoloadingIssuesInspection */

/**
 * Class CuFileContentManager
 */
class CuFileContentManager
{
    protected $filePath;


    /**
     * CuFileContentManager constructor.
     *
     * @param $filePath
     */
    public function __construct($filePath) {

        $this->filePath = $filePath;
    }

    /**
     * @return string
     */
    public function getContent() {

        return file_get_contents($this->filePath);

    }

    /**
     * @param $newContent
     *
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function writeContent($newContent) {

        $file = new SplFileObject($this->filePath, 'wb+');

        $file->fwrite($newContent);
    }

}

$response = exec('git status', $output, $return);
$output   = is_array($output) ? $output[0] : $output;

$versionStr    = str_replace('On branch ', '', $output);
$version       = str_replace('_release', '', $versionStr);
$newVersionStr = "\\\$version = '" . $version . "';";

$fileContentManager        = new CuFileContentManager(__DIR__ . '/../cuBackup.php');
$versionsFileContentOrigin = $fileContentManager->getContent();

$versionsFileContentNew = preg_replace('/\\$scriptIsActive[ ]*=(.*)/',
                                       '$scriptIsActive = 0;',
                                       $versionsFileContentOrigin);

$versionsFileContentNew = preg_replace('/\\$version[ ]*=(.*)/', $newVersionStr, $versionsFileContentNew);

$exitCode = 0;

if ($versionsFileContentNew !== $versionsFileContentOrigin) {
    $exitCode = 1;
    $fileContentManager->writeContent($versionsFileContentNew);
}

exit($exitCode);

