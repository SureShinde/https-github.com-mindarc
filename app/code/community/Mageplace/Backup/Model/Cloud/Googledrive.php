<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

include_once Mage::helper('mpbackup')->getLibDir() . DS . 'google' . DS . 'google-api-php-client' . DS . 'src' . DS . 'Google_Client.php';
include_once Mage::helper('mpbackup')->getLibDir() . DS . 'google' . DS . 'google-api-php-client' . DS . 'src' . DS . 'contrib' . DS . 'Google_DriveService.php';

/**
 * Class Mageplace_Backup_Model_Cloud_Googledrive
 * @method Mageplace_Backup_Model_Cloud_Googledrive setBackupFolder
 */
class Mageplace_Backup_Model_Cloud_Googledrive extends Mageplace_Backup_Model_Cloud
{
    const FILE_PART_MAX_SIZE = 100;

    const CHUNK_SIZE = 1048576; /* == 1Mb */

    const SESSION_PARAM_URL       = 'step_cloud_googledrive_url';
    const SESSION_PARAM_MIME_TYPE = 'step_cloud_googledrive_mimetype';
    const SESSION_PARAM_PARENT_ID = 'step_cloud_googledrive_parent_id';

    const CLIENT_ID       = 'client_id';
    const CLIENT_SECRET   = 'client_secret';
    const APP_PATH        = 'appPath';
    const FILEPARTMAXSIZE = 'filepartmaxsize';
    const FILECHUNKSIZE   = 'filechunksize';

    const OAUTH_ACCESS_TOKEN  = 'oauth_access_token';
    const OAUTH_REFRESH_TOKEN = 'oauth_refresh_token';
    const PARAM_ACCESS_TOKEN  = 'access_token';
    const PARAM_REFRESH_TOKEN = 'refresh_token';
    const PARAM_ROOTFOLDERID  = 'rootFolderId';

    const MIME_TYPE_GOOGLE_FOLDER = 'application/vnd.google-apps.folder';
    const MIME_TYPE_GOOGLE_FILES  = 'application/vnd.google-apps.file';
    const MIME_TYPE_TGZ           = 'application/x-tgz';
    const MIME_TYPE_GZIP          = 'application/x-gzip';

    static $SCOPES = array(
        'https://www.googleapis.com/auth/drive'
    );

    protected $_useObjects = true;

    /**
     * @return Google_Client
     */
    public function getClient()
    {
        if (!$this->getConfigValue(self::CLIENT_ID) || !$this->getConfigValue(self::CLIENT_SECRET)) {
            $this->_throwExeption($this->_helper->__('Wrong application settings'));
        }

        if (!$this->_getData('client')) {
            $client = new Google_Client();
            $client->setClientId($this->getConfigValue(self::CLIENT_ID));
            $client->setClientSecret($this->getConfigValue(self::CLIENT_SECRET));
            $client->setRedirectUri($this->getCallbackUrl()); /*'urn:ietf:wg:oauth:2.0:oob'*/
            $client->setScopes(self::$SCOPES);
            $this->setData('client', $client);
        }

        return $this->_getData('client');
    }

    public function getService($useObjects = true)
    {
        if (!$this->_getData('service' . ($useObjects ? '_client_use_objects' : ''))) {
            if (!$this->getAccessToken()) {
                $this->_throwExeption($this->_helper->__("Error access token"));
            }

            $this->getClient()->setUseObjects($useObjects);
            $service = new Google_DriveService($this->getClient());
            $this->setData('service_' . ($useObjects ? '_client_use_objects' : ''), $service);
        }

        return $this->_getData('service_' . ($useObjects ? '_client_use_objects' : ''));
    }

    public function getRedirectUrl()
    {
        return $this->getClient()->createAuthUrl();
    }


    public function callback($request, $response)
    {
        try {
            $tokenJson = $this->getClient()->authenticate();
            $token     = Zend_Json::decode($tokenJson);
        } catch (Exception $e) {
            $this->resetAuthData();
            Mage::log($e);
            $this->_throwExeption($e->getMessage());
        }

        if (!$token) {
            $this->resetAuthData();
            Mage::log($request->getParams());
            Mage::log($tokenJson);
            $this->_throwExeption($this->_helper->__("Error callback code"));
        }


        $this->setAccessToken($token);

        return true;
    }

    public function setAccessToken($token, $hasRefreshToken = true)
    {
        if (empty($token[self::PARAM_ACCESS_TOKEN]) || ($hasRefreshToken && empty($token[self::PARAM_REFRESH_TOKEN]))) {
            Mage::log($token);
            $this->_throwExeption($this->_helper->__("Error tokens"));
        }

        $this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, $token[self::PARAM_ACCESS_TOKEN]);
        if ($hasRefreshToken) {
            $this->saveConfigValue(self::OAUTH_REFRESH_TOKEN, $token[self::PARAM_REFRESH_TOKEN]);
        }

        $this->setData('access_token', $token[self::PARAM_ACCESS_TOKEN]);

        return $this;
    }

    public function getAccessToken()
    {
        if (!$this->_getData('access_token')) {
            if (!$this->getConfigValue(self::OAUTH_REFRESH_TOKEN)) {
                $this->_throwExeption($this->_helper->__("Error refresh token"));
            }

            $this->getClient()->refreshToken($this->getConfigValue(self::OAUTH_REFRESH_TOKEN));
            $this->setAccessToken(Zend_Json::decode($this->getClient()->getAccessToken()), false);
        }

        return $this->_getData('access_token');
    }

    public function resetAuthData()
    {
        $this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, '');
        $this->saveConfigValue(self::OAUTH_REFRESH_TOKEN, '');

        $this->unsetData('access_token');

        $this->_getSession()->unsetData();

        return $this;
    }

    public function getAboutInfo()
    {
        return $this->getService()->about->get();
    }

    public function checkConnection()
    {
        try {
            $this->getRootId();

            return true;
        } catch (Exception $e) {
        }

        return false;
    }

    public function getRootId()
    {
        $info = $this->getAboutInfo();
        if (!is_object($info) || empty($info->{self::PARAM_ROOTFOLDERID})) {
            Mage::log($info);
            $this->_throwExeption($this->_helper->__("Error root id"));
        }

        return $info->{self::PARAM_ROOTFOLDERID};
    }

    public function createFolder($parent, $folderName)
    {
        if (is_array($folderName)) {
            $newFolder = null;
            $parentId  = $parent;
            foreach ($folderName as $name) {
                $newFolder = $this->createFolder($parentId, $name);
                if (empty($newFolder) || !is_object($newFolder) || empty($newFolder->id)) {
                    $this->_throwExeption($this->_helper->__("Error during create the folder '%s'", $name));
                }
                $parentId = $newFolder->id;
            }

            return $newFolder;
        }

        $objFolder = new Google_DriveFile();
        $objFolder->setTitle($folderName);
        $objFolder->setMimeType(self::MIME_TYPE_GOOGLE_FOLDER);

        $objParent = new Google_ParentReference();
        $objParent->setId($parent);
        $objFolder->setParents(array($objParent));

        return $this->getService()->files->insert($objFolder);
    }

    public function getFolderChildren($folderId)
    {
        $result    = array();
        $pageToken = null;

        do {
            try {
                $parameters           = array();
                $parameters['q']      = 'mimeType = "' . self::MIME_TYPE_GOOGLE_FOLDER . '" and "' . $folderId . '" in parents and trashed = false';
                $parameters['fields'] = 'items(id,title),nextPageToken';
                if ($pageToken) {
                    $parameters['pageToken'] = $pageToken;
                }

                $children = $this->getService()->files->listFiles($parameters);

                $result    = array_merge($result, $children->getItems());
                $pageToken = $children->getNextPageToken();
            } catch (Exception $e) {
                Mage::log($e);
                break;
            }
        } while ($pageToken);

        return $result;
    }

    public function getBackupFolderId($parent, $dirs)
    {
        foreach ($dirs as $i => $d) {
            $children = $this->getFolderChildren($parent);
            if (!is_array($children) || empty($children)) {
                $newFolder = $this->createFolder($parent, array_slice($dirs, $i));
                $parent    = $newFolder->id;
                break;
            }

            foreach ($children as $child) {
                if (is_object($child) && !empty($child->title) && $child->title == $d) {
                    $newParent = $child->id;
                    break;
                }
            }

            if (isset($newParent)) {
                $parent = $newParent;
                unset($newParent);
            } else {
                $newFolder = $this->createFolder($parent, array_slice($dirs, $i));
                $parent    = $newFolder->id;
                break;
            }
        }

        return $parent;
    }

    public function getBackupFolder()
    {
        $dir = $this->_getData('backup_folder');
        if (is_null($dir)) {
            $box_dir_nat = $this->getConfigValue(self::APP_PATH);
            $box_dir     = str_replace(array('\\'), array('/'), $box_dir_nat);
            $box_dir     = trim($box_dir, '\\/');
            if (!$box_dir) {
                $dir = $this->getRootId();
            } else {
                $dirs = explode('/', $box_dir);
                $dir  = $this->getBackupFolderId($this->getRootId(), $dirs);
            }

            if (!$dir && $box_dir_nat) {
                $this->saveConfigValue(self::APP_PATH, '');
            } else {
                if ($box_dir_nat != $box_dir) {
                    $this->saveConfigValue(self::APP_PATH, $box_dir);
                }
            }

            $this->setBackupFolder($dir);
        }

        return $dir;
    }

    public function putFile($name, $file)
    {
        $file = realpath($file);
        if (!file_exists($file)) {
            $this->_throwExeption($this->_helper->__('File "%s" doesn\'t exist', strval($file)));
        }

        $filename = basename($name);

        $fileObject = new Google_DriveFile();
        $fileObject->setTitle($filename);

        if (substr(".tar.gz", -7)) {
            $mimeType = self::MIME_TYPE_TGZ;
        } else {
            if (substr(".gz", -3)) {
                $mimeType = self::MIME_TYPE_GZIP;
            } else {
                $mimeType = self::MIME_TYPE_GOOGLE_FILES;
            }
        }

        $parentId = $this->getBackupFolder();
        if ($parentId != null) {
            $parent = new Google_ParentReference();
            $parent->setId($parentId);
            $fileObject->setParents(array($parent));
        }

        $data = file_get_contents($file);

        $createdFile = $this->getService()->files->insert(
            $fileObject,
            array(
                'mimeType' => $mimeType,
                'data'     => $data,
            )
        );

        $file_cloud_path = $this->getConfigValue(self::APP_PATH);
        $returnPath      = $file_cloud_path . '/' . $filename;

        $this->_addAdditionalInfo($createdFile->getId(), $returnPath);

        return $returnPath;
    }

    public function putFileChunk($name, $file)
    {
        $file = realpath($file);
        if (!file_exists($file)) {
            $this->_throwExeption($this->_helper->__('File "%s" doesn\'t exist', strval($file)));
        }

        $handle    = fopen($file, "rb");
        $filename  = basename($name);
        $chunkSize = $this->getChunkSize();

        $fileObject = new Google_DriveFile();
        $fileObject->setTitle($filename);

        if (!$mimeType = $this->getRequestMimeType()) {
            if (substr(".tar.gz", -7)) {
                $mimeType = self::MIME_TYPE_TGZ;
            } else {
                if (substr(".gz", -3)) {
                    $mimeType = self::MIME_TYPE_GZIP;
                } else {
                    $mimeType = self::MIME_TYPE_GOOGLE_FILES;
                }
            }
            $this->setRequestMimeType($mimeType);
        }

        if (!$parentId = $this->getRequestParentId()) {
            $parentId = $this->getBackupFolder();
            $this->setRequestParentId($parentId);
        }

        if ($parentId != null) {
            $parent = new Google_ParentReference();
            $parent->setId($parentId);
            $fileObject->setParents(array($parent));
        }

        $media = new Google_MediaFileUpload($mimeType, null, true, $chunkSize);

        if (!$fileSize = $this->getRequestFileSize()) {
            $fileSize = $this->filesize($file);
        }
        $media->setFileSize($fileSize);

        $byte = $startByte = (float)$this->getRequestBytes();
        if ($byte > 0) {
            $this->fseek($handle, $byte);
            $media->resumeUri = $this->getRequestUrl();
            $media->progress  = $byte;
        }

        /**
         * @var Google_HttpRequest $httpRequest
         * @see Google_FilesServiceResource::insert
         */
        $httpRequest = $this->getService(false)->files->insert(
            $fileObject,
            array(
                'mimeType'    => $mimeType,
                'mediaUpload' => $media
            )
        );

        while (!feof($handle)) {
            if ($this->timeIsUp()) {
                $this->setRequestBytes($byte);
                $this->setRequestUrl($media->resumeUri);
                $nextChunk = true;
                break;
            }

            $chunk        = fread($handle, $chunkSize);
            $uploadStatus = $media->nextChunk($httpRequest, $chunk);

            $byte += $chunkSize;
        }

        fclose($handle);

        $locale = Mage::app()->getLocale()->getLocale();

        if (isset($nextChunk)) {
            $this->_addBackupProcessMessage($this->_helper->__(
                'Bytes from %1$s to %2$s were added (total: %3$s)',
                Zend_Locale_Format::toNumber($startByte, array('precision' => 0, 'locale' => $locale)),
                Zend_Locale_Format::toNumber($byte, array('precision' => 0, 'locale' => $locale)),
                Zend_Locale_Format::toNumber($fileSize, array('precision' => 0, 'locale' => $locale))
            ));

            return false;
        }

        $this->_addBackupProcessMessage($this->_helper->__(
            'Bytes from %1$s to %2$s were added (total: %3$s)',
            Zend_Locale_Format::toNumber($startByte, array('precision' => 0, 'locale' => $locale)),
            Zend_Locale_Format::toNumber($fileSize, array('precision' => 0, 'locale' => $locale)),
            Zend_Locale_Format::toNumber($fileSize, array('precision' => 0, 'locale' => $locale))
        ));


        if (!isset($uploadStatus) || !is_array($uploadStatus) || empty($uploadStatus['id'])) {
            $this->_throwExeption($this->_helper->__('Error chunk upload response'));
        }

        $fileCloudPath = $this->getConfigValue(self::APP_PATH);
        $returnPath    = $fileCloudPath . '/' . $filename;

        $this->_addAdditionalInfo($uploadStatus['id'], $returnPath);

        $this->clearRequestParams();

        return $returnPath;
    }

    public function deleteFile($path)
    {
        if (empty($this->_additionalInfo) || !is_array($this->_additionalInfo) || empty($this->_additionalInfo[$path])) {
            $this->_throwExeption($this->_helper->__('Wrong additional backup data'));
        }

        try {
            $this->getService()->files->delete($this->_additionalInfo[$path]);
        } catch (Exception $e) {
            Mage::log($e);

            return false;
        }

        return true;
    }

    /**
     * Get max size of file for upload to cloud server (Mb)
     *
     * @return float
     */
    public function getMaxSize()
    {
        $filePartMaxSize = floatval($this->getConfigValue(self::FILEPARTMAXSIZE));
        if ($filePartMaxSize <= 0) {
            return 0;
        } elseif ($filePartMaxSize > self::FILE_PART_MAX_SIZE) {
            return self::FILE_PART_MAX_SIZE;
        } else {
            return $filePartMaxSize;
        }
    }

    public function getChunkSize()
    {
        if ($this->_getData('chunk_size') === null) {
            $chunk = (float)$this->getConfigValue(self::FILECHUNKSIZE);
            if ($chunk <= 0) {
                $chunk = self::CHUNK_SIZE;
            }
            $this->setData('chunk_size', $chunk);
        }

        return $this->_getData('chunk_size');
    }

    protected function getSessionUrl()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_URL);
    }

    protected function getRequestUrl()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_URL);
    }

    protected function setSessionUrl($url)
    {
        $this->_getSession()->setData(self::SESSION_PARAM_URL, $url);

        return $this;
    }

    protected function setRequestUrl($url)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_URL => $url));

        return $this;
    }

    protected function getSessionMimeType()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_MIME_TYPE);
    }

    protected function getRequestMimeType()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_MIME_TYPE);
    }

    protected function setSessionMimeType($mimeType)
    {
        $this->_getSession()->setData(self::SESSION_PARAM_MIME_TYPE, $mimeType);

        return $this;
    }

    protected function setRequestMimeType($mimeType)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_MIME_TYPE => $mimeType));

        return $this;
    }

    protected function getSessionParentId()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_PARENT_ID);
    }

    protected function getRequestParentId()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_PARENT_ID);
    }

    protected function setSessionParentId($parentId)
    {
        $this->_getSession()->setData(self::SESSION_PARAM_PARENT_ID, $parentId);

        return $this;
    }

    protected function setRequestParentId($parentId)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_PARENT_ID => $parentId));

        return $this;
    }

    protected function clearSessionParams()
    {
        parent::clearSessionParams();

        $this->_getSession()->unsetData(self::SESSION_PARAM_URL);
        $this->_getSession()->unsetData(self::SESSION_PARAM_MIME_TYPE);
        $this->_getSession()->unsetData(self::SESSION_PARAM_PARENT_ID);
    }

    protected function clearRequestParams()
    {
        parent::clearRequestParams();

        $this->setRequestUrl(null);
        $this->setRequestMimeType(null);
        $this->setRequestParentId(null);
    }
}