<?php
// @link http://alinazero/egFileUpload
namespace alina\mvc\controller;

use alina\Message;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\FS;
use alina\utils\Request;
use Intervention\Image\ImageManager;

class FileUpload
{
    protected $resp;
    protected $targetDir     = '';
    protected $max           = 0;
    protected $currentAmount = 0;
    protected $left          = 0;

    public function __construct()
    {
        AlinaRejectIfNotLoggedIn();
    }

    public function actionCommon()
    {
        $vd = NULL;
        if (Request::isPostPutDelete()) {
            $vd = $this->processUpload();
        }
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    public function actionCkEditor()
    {
        $resp = $this->processUpload();
        $vd   = (object)[
            'uploaded'    => $resp->uploaded,
            'fileName'    => $resp->uploaded ? $resp->fileName[0] : '',
            'newFileName' => $resp->uploaded ? $resp->newFileName[0] : '',
            'url'         => $resp->uploaded ? $resp->url[0] : '',
        ];
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayoutMiddled.php');
    }

    ##################################################
    #region Utils
    protected function processUpload()
    {
        #####
        AlinaResponseSuccess(0);
        $stateSuccess = FALSE;
        $this->resp   = (object)[
            'uploaded'    => 0,
            'fileName'    => [],
            'newFileName' => [],
            'url'         => [],
        ];
        #####
        if (CurrentUser::obj()->isLoggedIn()) {
            if (isset($_FILES[ALINA_FILE_UPLOAD_KEY])) {
                $FILE_CONTAINER = $_FILES[ALINA_FILE_UPLOAD_KEY];
                $targetDir      = $this->destinationDir();
                if (!$this->processWatch()) {
                    return $this->resp;
                }
                $counterUploadedFiles = 0;
                foreach ($FILE_CONTAINER["error"] as $i => $error) {
                    if ($error == UPLOAD_ERR_OK) {
                        $sourceFileFullPath  = $FILE_CONTAINER["tmp_name"][$i];
                        $sourceFileCleanName = $FILE_CONTAINER["name"][$i];
                        $newFileCleanName    = md5_file($sourceFileFullPath);
                        $ext                 = FS::fileEXT($sourceFileCleanName);
                        #####
                        if (!$this->isExtAllowed($ext)) {
                            Message::setDanger("%s is not uploaded", [$sourceFileCleanName]);
                            $stateSuccess = FALSE;
                            continue;
                        }
                        #####
                        $this->resp->fileName[]    = $sourceFileCleanName;
                        $this->resp->newFileName[] = $newFileName = "{$newFileCleanName}.{$ext}";
                        $targetFile                = FS::buildPathFromBlocks($targetDir, $newFileName);
                        $muf                       = move_uploaded_file($sourceFileFullPath, $targetFile);
                        if ($muf) {
                            #####
                            if ($this->isImage($targetFile)) {
                                $targetFile = $this->processImageCompression($targetFile);
                            }
                            #####
                            $webPath = $this->webPath($targetFile);
                            //Message::set("Uploaded: $webPath");
                            $this->resp->url[]    = $webPath;
                            $this->resp->uploaded = ++$counterUploadedFiles;
                        }
                    }
                } //end foreach
                #####
                if ($counterUploadedFiles) {
                    $stateSuccess  = TRUE;
                    $max           = $this->getMax();
                    $currentAmount = $this->getCurrentAmount();
                    $left          = $max == -1 ? 'Unlimited' : $max - $currentAmount;
                    $this->left    = $left;
                    Message::setSuccess("Uploaded!");
                    Message::setInfo("Already uploaded: %s.", [$currentAmount]);
                    Message::setInfo("Left to upload: %s.", [$left]);
                }
                #####
            }
        }
        #####
        #####
        if (!$stateSuccess) {
            AlinaResponseSuccess(0);
            Message::setDanger('Upload failed');
        }
        else {
            AlinaResponseSuccess(1);
        }
        #####
        #####
        return $this->resp;
    }

    protected function processWatch()
    {
        $targetDir = $this->destinationDir();
        $max       = $this->getMax();
        if ($max == -1) {
            return TRUE;
        }
        $currentAmount = $this->getCurrentAmount($targetDir);
        if ($currentAmount >= $max) {
            Message::setDanger("File upload limit exceeded. Already uploaded %s files", [$currentAmount]);

            return FALSE;
        }

        return TRUE;
    }

    public function getMax($uid = NULL)
    {
        if (empty($uid)) {
            $CU = CurrentUser::obj();
        }
        else {
            $CU        = new user();
            $CU->id    = $uid;
            $CU->alias = "user_{$uid}";
        }
        $cfg = AlinaCfg('watcher/fileUpload/max');
        /*[
            'registered' => 10,
            'admin'      => 0,
            'moderator'  => 1000,
            'privileged' => 0,
        ];*/
        $max = 0;
        if ($CU->hasRole('privileged')) {
            $max = $cfg['privileged'];
        }
        else if ($CU->hasRole('admin')) {
            $max = $cfg['admin'];
        }
        else if ($CU->hasRole('moderator')) {
            $max = $cfg['moderator'];
        }
        else if ($CU->hasRole('registered')) {
            $max = $cfg['registered'];
        }
        $this->max = $max;

        return $this->max;
    }

    protected function getCurrentAmount($targetDir = NULL)
    {
        if (empty($targetDir)) {
            $targetDir = $this->destinationDir();
        }
        $this->currentAmount = FS::countFilesInDir($targetDir);

        return $this->currentAmount;
    }

    protected function processImageCompression($realPath)
    {
        $manager = new ImageManager(['driver' => 'imagick']);
        $image   = $manager
            ->make($realPath);
        if ($image->width() > 1000) {
            $image->widen(1000);
        }
        $image
            ->save($realPath);

        return $realPath;
    }

    protected function processFileModel()
    {
    }

    protected function destinationDir($uid = NULL)
    {
        if (empty($uid)) {
            $uid = CurrentUser::obj()->id ?: 0;
        }
        $blocks = [
            AlinaCfg('fileUploadDir'),
            $uid,
        ];
        $res    = FS::buildPathFromBlocks($blocks);
        FS::mkChainedDirIfNotExists($res);
        $this->targetDir = $res;

        return $res;
    }

    protected function webPath($filePath)
    {
        $res      = '';
        $filePath = FS::normalizePath($filePath);
        $webPath  = FS::normalizePath(ALINA_WEB_PATH);
        $relPath  = str_replace($webPath, '', $filePath);
        $blocks   = [
            Request::obj()->DOMAIN,
            $relPath,
        ];
        $res      = '//' . FS::buildPathFromBlocks($blocks);
        $res      = str_replace('\\', '/', $res);

        return $res;
    }

    protected function extOfImages()
    {
        return [
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif',
        ];
    }

    protected function allowedExtensions()
    {
        return array_merge([], $this->extOfImages());
    }

    protected function isImage($sourcePath)
    {
        return in_array(FS::fileEXT($sourcePath), $this->extOfImages());
    }

    protected function isExtAllowed($ext)
    {
        return
            in_array(mb_strtolower($ext), $this->allowedExtensions());
    }

    #endregion Utils
    ##################################################
}
