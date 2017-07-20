<?php

/**
 * Creates a Directory by chained path.
 * If path does not exist, creates the path too.
 * PHP mkdir() cannot create a subdirectory if upper directory does not exist.
 */
function mkChainedDirIfNotExists($fullPath)
{
    $fullPath = normalizePath($fullPath);

    $pathParts = explode(DIRECTORY_SEPARATOR, $fullPath);
    $chain     = [];
    foreach ($pathParts as $dir) {
        if (empty($dir))
            continue;
        $chain[]   = $dir;
        $chainPath = implode(DIRECTORY_SEPARATOR, $chain);
        if (!is_dir($chainPath)) {
            mkdir($chainPath);
        }
    }

    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0777, TRUE);
    }
}

/**
 * Path adaptation for Windows AND (*nix OR Mac).
 * Normalize path string for various path separators.
 */
function normalizePath($path)
{
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

    return $path;
}

/**
 * Remove even not empty directories.
 * PHP rmdir() cannot delete not empty directory.
 */
function rmDirCompletely($path)
{
    foreach (scandir($path) as $file) {
        if ('.' === $file || '..' === $file)
            continue;
        $curPath = $path . DIRECTORY_SEPARATOR . $file;
        if (is_dir($curPath))
            rmDirCompletely($curPath);
        else unlink($curPath);
    }
    rmdir($path);
}

/**
 * Check if file exists in a directory.
 * If yes: add microtime suffix to file name until name becomes unique.
 * @return string file name.
 */
function unifyFileName($dir, $fileName)
{
    $dir            = normalizePath($dir);
    $uniqueFileName = $fileName;
    $repeat         = TRUE;
    do {
        $dirFile = $dir . DIRECTORY_SEPARATOR . $uniqueFileName;
        if (file_exists($dirFile)) {

            // Build suffix
            list($usec, $sec) = explode(" ", microtime());
            $suffix = $sec;
            $suffix .= '-';
            $suffix .= str_replace(['.', ','], '', $usec);

            // Build new file name
            $fileParts   = pathinfo($fileName);
            $newFileName = '';
            $newFileName .= $fileParts['filename'];
            $newFileName .= '-';
            $newFileName .= $suffix;
            $newFileName .= (isset($fileParts['extension'])) ? '.' . $fileParts['extension'] : '';

            $uniqueFileName = $newFileName;
        } else {
            $repeat = FALSE;
        }
    } while ($repeat);

    return $uniqueFileName;
}

/**
 * Retrieve file extension in upper case
 * or empty string '';
 */
function fileEXT($filePath)
{
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);

    return strtolower($extension);
}

function mkFileIfNotExists($path)
{
    $path = normalizePath($path);
    if (!file_exists($path)) {
        $pathInfo = pathinfo($path);
        $dir      = $pathInfo['dirname'];
        mkChainedDirIfNotExists($dir);
        if (FALSE === file_put_contents($path, NULL)) {
            throw new \Exception("Unable to create file {$pathInfo}");
        }
    }

    return realpath($path);
}

/**
 * @see buildClassNameFromBlocks
 */
function buildPathFromBlocks()
{
    $args = func_get_args();
    $blocks  = [];
    foreach ($args as $block) {
        if (is_array($block)) {
            $blocks = array_merge($blocks, $block);
        } else {
            $blocks[] = $block;
        }
    }

    $pp = [];
    foreach ($blocks as $i => $block) {
        $b = normalizePath($block);
        $b = trim($b, DIRECTORY_SEPARATOR);
        $pp[] = $b;
    }

    $path = implode(DIRECTORY_SEPARATOR, $pp);

    return $path;
}

function giveFile($path)
{
    if (FALSE === ($realPath = realpath($path))) {
        throw new \ErrorException("File {$path} does not exist.");
    }

    if (!file_exists($realPath)) {
        throw new \ErrorException("File {$path} does not exist.");
    }

    $pathInfo = pathinfo($realPath);
    $fileSize = filesize($realPath);
    $ext      = $pathInfo['extension'];
    $baseName = $pathInfo['basename'];
    $mimeyObj = new \Mimey\MimeTypes;
    $mimeType = $mimeyObj->getMimeType($ext);

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $baseName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $fileSize);
    readfile($realPath);
    exit;
}