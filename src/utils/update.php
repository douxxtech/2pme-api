<?php

require_once 'config.php';

function fetchFileContent($url) {
    return file_get_contents($url);
}

function updateFile($filePath, $content) {
    file_put_contents($filePath, $content);
}

function getCurrentDate() {
    return date("d/m/Y");
}

function autoUpdate() {
    if (!isset($GLOBALS['auto_update']) || $GLOBALS['auto_update'] !== true) {
        //echo "Auto-update is not enabled.\n";
        return;
    }

    $versionFilePath = __DIR__ . '/version.txt';
    $remoteVersionUrl = 'https://raw.githubusercontent.com/douxxtech/2pme-api/refs/heads/main/src/utils/version.txt';
    $filesListUrl = 'https://raw.githubusercontent.com/douxxtech/2pme-api/refs/heads/main/src/utils/files.txt';

    if (!file_exists($versionFilePath)) {
        //die("Version file not found!\n ");
    }

    $versionFileContent = file($versionFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (count($versionFileContent) < 2) {
        //die("Version file is not in the correct format!\n");
    }

    $localVersion = $versionFileContent[0];
    $localDate = $versionFileContent[1];

    if ($localDate === getCurrentDate()) {
        //echo "Date is up-to-date. No update needed.\n";
        return;
    }

    $remoteVersionContent = fetchFileContent($remoteVersionUrl);
    $remoteVersionLines = explode("\n", trim($remoteVersionContent));
    $remoteVersion = $remoteVersionLines[0];

    if ($localVersion === $remoteVersion) {
        $versionFileContent[1] = getCurrentDate();
        updateFile($versionFilePath, implode("\n", $versionFileContent));
        //echo "Version is up-to-date. Only the date was updated.\n";
        return;
    }

    $filesListContent = fetchFileContent($filesListUrl);
    $filesToUpdate = explode("\n", trim($filesListContent));

    foreach ($filesToUpdate as $file) {
        $filePath = __DIR__ . '/../' . $file;
        $remoteFilePath = "https://raw.githubusercontent.com/douxxtech/2pme-api/refs/heads/main/src/$file";
        $remoteFileContent = fetchFileContent($remoteFilePath);
        updateFile($filePath, $remoteFileContent);
        //echo "Updated file: $file\n";
    }

    $versionFileContent[0] = $remoteVersion;
    $versionFileContent[1] = getCurrentDate();
    updateFile($versionFilePath, implode("\n", $versionFileContent));

    //echo "Update completed successfully.\n";
}

?>
