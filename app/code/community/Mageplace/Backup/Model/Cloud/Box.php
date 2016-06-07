<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Cloud_Box
 * @method Mageplace_Backup_Model_Cloud_Box setBackupFolder
 * @method Mageplace_Backup_Model_Cloud_Box setClient
 * @method Zend_Http_Client getClient
 */
class Mageplace_Backup_Model_Cloud_Box extends Mageplace_Backup_Model_Cloud
{
    const FILE_PART_MAX_SIZE             = 100;
    const STATE_STRING_LENGTH            = 20;
    const ENC_JSON                       = 'application/json';
    const TIMEOUT                        = 'timeout';
    const TIMEOUT_DEFAULT                = 10;
    const CURLOPTIONS                    = 'curloptions';
    const CLIENT_ID                      = 'client_id';
    const CLIENT_SECRET                  = 'client_secret';
    const FILEPARTMAXSIZE                = 'filepartmaxsize';
    const BOX_DIRECTORY                  = 'appPath';
    const SESSION_PARAM_STATE            = 'mpbackup_box_state';
    const PARAM_RAW_BODY                 = 'raw_body';
    const PARAM_ERROR                    = 'error';
    const PARAM_ERROR_DESCRIPTION        = 'error_description';
    const PARAM_STATE                    = 'state';
    const PARAM_CODE                     = 'code';
    const PARAM_GRANT_TYPE               = 'grant_type';
    const PARAM_GRANT_TYPE_AUTH_CODE     = 'authorization_code';
    const PARAM_GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';
    const PARAM_ACCESS_TOKEN             = 'access_token';
    const PARAM_REFRESH_TOKEN            = 'refresh_token';
    const PARAM_EXPIRES_IN               = 'expires_in';
    const PARAM_ID                       = 'id';
    const PARAM_NAME                     = 'name';
    const PARAM_PARENT                   = 'parent';
    const PARAM_FOLDER_ENTRIES           = 'entries';
    const PARAM_FOLDER_TOTAL_COUNT       = 'total_count';
    const PARAM_FILENAME                 = 'filename';
    const PARAM_PARENT_ID                = 'parent_id';
    const PARAM_TOTAL_COUNT              = 'total_count';
    const PARAM_ENTRIES                  = 'entries';
    const PARAM_SHA1                     = 'sha1';
    const ERROR_ACCESS_DENIED            = 'access_denied';
    const OAUTH_ACCESS_TOKEN             = 'oauth_access_token';
    const OAUTH_REFRESH_TOKEN            = 'oauth_refresh_token';
    const HEADER_PARAM_ACCEPT_ENCODING   = 'accept-encoding';
    const HEADER_PARAM_AUTHORIZATION     = 'Authorization';
    const HEADER_AUTHORIZATION_TEMPLATE  = 'Bearer %s';
    const URI_AUTHORIZE_TEMPLATE         = '%1$s?response_type=%2$s&client_id=%3$s&state=%4$s&redirect_uri=%5$s';
    const URI_AUTHORIZE                  = 'https://www.box.com/api/oauth2/authorize';
    const URI_ACCESS_TOKEN               = 'https://www.box.com/api/oauth2/token';
    const URI_USER_ME                    = 'https://api.box.com/2.0/users/me';
    const URI_FOLDERS                    = 'https://api.box.com/2.0/folders';
    const URI_FOLDER_ITEMS               = 'https://api.box.com/2.0/folders/%s/items';
    const URI_FILE_UPLOAD                = 'https://upload.box.com/api/2.0/files/content';
    const URI_FILE_DELETE                = 'https://api.box.com/2.0/files/%s';
    const STATUS_SUCCESS                 = 200;
    const STATUS_CREATED                 = 201;
    const STATUS_ACCEPTED                = 202;
    const STATUS_NO_CONTENT              = 204;

    static $STATUS_SUCCESS = array(
        self::STATUS_SUCCESS,
        self::STATUS_CREATED,
        self::STATUS_ACCEPTED,
        self::STATUS_NO_CONTENT
    );


    /**
     * Get max size of file for upload to cloud server (Mb)
     *
     * @return float
     */
    public function getMaxSize()
    {
        $filePartMaxSize = floatval($this->getConfigValue(self::FILEPARTMAXSIZE));

        return floatval($filePartMaxSize ? $filePartMaxSize : self::FILE_PART_MAX_SIZE);
    }

    public function getRedirectUrl()
    {
        $callback = $this->getCallbackUrl(true);

        return sprintf(self::URI_AUTHORIZE_TEMPLATE,
            self::URI_AUTHORIZE,
            'code',
            $this->getConfigValue(self::CLIENT_ID),
            $this->getStateParam(),
            $callback
        );
    }

    public function getStateParam($set = true)
    {
        $session = $this->_getSession();

        $state = $session->getData(self::SESSION_PARAM_STATE);
        if (is_null($state) && $set) {
            $state = Mage::helper('core')->getRandomString(self::STATE_STRING_LENGTH);
            $session->setData(self::SESSION_PARAM_STATE, $state);
        }

        return $state;
    }

    public function resetAuthData()
    {
        $this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, '');
        $this->saveConfigValue(self::OAUTH_REFRESH_TOKEN, '');

        $this->unsetData('access_token');

        $this->_getSession()->unsetData();

        return $this;
    }

    public function callback($request, $response)
    {
        $error = $request->getParam(self::PARAM_ERROR);
        if ($error) {
            Mage::log($request->getParams());
            $this->resetAuthData();
            $this->_throwExeption($request->getParam(self::PARAM_ERROR_DESCRIPTION) . " ($error)");
        }

        $state = $request->getParam(self::PARAM_STATE);
        $check = $this->checkStateParam($state, true);
        if (!$check) {
            Mage::log($request->getParams());
            $this->resetAuthData();
            $this->_throwExeption($this->_helper->__("Error callback state check"));
        }

        $code = $request->getParam(self::PARAM_CODE);
        if (!$code) {
            Mage::log($request->getParams());
            $this->resetAuthData();
            $this->_throwExeption($this->_helper->__("Error callback code"));
        }

        $this->getAccessToken($code);

        return true;
    }

    public function resetStateParam()
    {
        $this->_getSession()->unsetData(self::SESSION_PARAM_STATE);
    }

    public function checkStateParam($state, $reset = false)
    {
        $origState = $this->getStateParam(false);

        if ($reset) {
            $this->resetStateParam();
        }

        return $origState == $state;
    }

    /**
     * @param null $code Use null if you have a refresh token
     *
     * @return mixed
     */
    public function getAccessToken($code = null)
    {
        if (!is_null($code) || !($accessToken = $this->_getData('access_token'))) {
            $params = array(
                self::CLIENT_ID     => $this->getConfigValue(self::CLIENT_ID),
                self::CLIENT_SECRET => $this->getConfigValue(self::CLIENT_SECRET),
            );

            if (is_null($code)) {
                $params[self::PARAM_GRANT_TYPE]    = self::PARAM_GRANT_TYPE_REFRESH_TOKEN;
                $params[self::PARAM_REFRESH_TOKEN] = $this->getConfigValue(self::OAUTH_REFRESH_TOKEN);
                if (empty($params[self::PARAM_REFRESH_TOKEN])) {
                    Mage::log($params);
                    $this->_throwExeption($this->_helper->__("Error refresh token"));
                }
            } else {
                $params[self::PARAM_GRANT_TYPE] = self::PARAM_GRANT_TYPE_AUTH_CODE;
                $params[self::PARAM_CODE]       = $code;
            }

            $response = $this->getResponseBody(self::URI_ACCESS_TOKEN, $params, Zend_Http_Client::POST, null, false);

            if (empty($response[self::PARAM_ACCESS_TOKEN]) || empty($response[self::PARAM_REFRESH_TOKEN])) {
                Mage::log($response);
                $this->_throwExeption($this->_helper->__("Error tokens"));
            }

            $accessToken = $response[self::PARAM_ACCESS_TOKEN];

            $this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, $response[self::PARAM_ACCESS_TOKEN]);
            $this->saveConfigValue(self::OAUTH_REFRESH_TOKEN, $response[self::PARAM_REFRESH_TOKEN]);

            $this->setData('access_token', $response[self::PARAM_ACCESS_TOKEN]);
        }

        return $accessToken;
    }

    /**
     * @param string     $url
     * @param array|null $params
     * @param string     $method
     * @param array|null $addHeaders
     * @param bool       $auth
     *
     * @return mixed
     * @throws
     */
    public function getResponseBody($url, $params = null, $method = Zend_Http_Client::GET, $addHeaders = null, $auth = true)
    {
        try {
            if (is_array($addHeaders) && !empty($addHeaders[Zend_Http_Client::CONTENT_TYPE]) && $addHeaders[Zend_Http_Client::CONTENT_TYPE] == self::ENC_JSON) {
                $isJson = true;
                unset($addHeaders[Zend_Http_Client::CONTENT_TYPE]);
            } else {
                $isJson = false;
            }

            $timeOut = (int)$this->getConfigValue(self::TIMEOUT);
            if (!$timeOut) {
                $timeOut = self::TIMEOUT_DEFAULT;
            }

            $headers = array(
                'accept-encoding' => '',
            );

            if (!empty($addHeaders) && is_array($addHeaders)) {
                $headers = array_merge($headers, $addHeaders);
            }


            if ((is_null($params) || (is_array($params) && empty($params[self::PARAM_ACCESS_TOKEN]))) && $auth && !array_key_exists(self::HEADER_PARAM_AUTHORIZATION, $headers)) {
                //Possible loop if param $auth = true because getAccessToken method call current method too
                if (!$accessToken = $this->getAccessToken()) {
                    $this->_throwExeption($this->_helper->__("Error header auth tokens"));
                }

                $headers[self::HEADER_PARAM_AUTHORIZATION] = sprintf(self::HEADER_AUTHORIZATION_TEMPLATE, $accessToken);
            }

            if ($method == Zend_Http_Client::GET) {
                $setParameterMethod = 'setParameterGet';
            } else {
                $setParameterMethod = 'setParameterPost';
            }

            $client = new Zend_Http_Client();
            $client->setAdapter('Zend_Http_Client_Adapter_Curl');
            $this->setClient($client);
            $client->setUri($url)
                ->setHeaders($headers)
                ->setMethod($method)
                ->setConfig(array(
                    self::TIMEOUT     => $timeOut,
                    self::CURLOPTIONS => array(
                        CURLINFO_HEADER_OUT    => true,
                        CURLOPT_SSL_VERIFYPEER => false
                    )
                ));


            if (!is_null($params)) {
                if ($isJson) {
                    $params = Zend_Json::encode($params);
                    $client->setRawData($params, self::ENC_JSON);
                } else {
                    if (!empty($params[self::PARAM_RAW_BODY])) {
                        $client->setRawData($params[self::PARAM_RAW_BODY]);
                        unset($params[self::PARAM_RAW_BODY]);
                    }

                    if (!empty($params[self::PARAM_FILENAME])) {
                        $filename = basename($params[self::PARAM_FILENAME]);
                        $client->setFileUpload($params[self::PARAM_FILENAME], $filename);
                        unset($params[self::PARAM_FILENAME]);
                    }

                    if (!empty($params)) {
                        $client->$setParameterMethod($params);
                    }
                }
            }

            /**
             * @var $response Zend_Http_Response
             */
            $response = $client->request();
            if (!isset($response) || !is_object($response)) {
                throw Mage::exception('Mageplace_Backup', $this->_helper->__("Error response object"));
            }

            if (!in_array($response->getStatus(), self::$STATUS_SUCCESS)) {
                if (!empty($body[self::PARAM_ERROR_DESCRIPTION])) {
                    $message = $body[self::PARAM_ERROR_DESCRIPTION];
                } else {
                    if ($response->getMessage()) {
                        $message = $response->getMessage();
                    } else {
                        $message = $this->_helper->__("Error response message");
                    }
                }

                throw Mage::exception('Mageplace_Backup', $message);
            }

            if ($response->getStatus() != self::STATUS_NO_CONTENT) {
                $rawBody = $response->getRawBody();
                if (empty($rawBody)) {
                    throw Mage::exception('Mageplace_Backup', $this->_helper->__("Error response body"));
                }

                $body = Zend_Json::decode($rawBody);
                if (empty($body)) {
                    throw Mage::exception('Mageplace_Backup', $this->_helper->__("Error decode body"));
                }
            } else {
                $body = true;
            }

            return $body;

        } catch (Exception $e) {
            Mage::logException($e);
            if (isset($client)) {
                Mage::log($client);
            }
            $this->_throwExeption($e->getMessage());
        }

        return null;
    }

    public function checkConnection()
    {
        try {
            $info = $this->getAccountInfo();
        } catch (Exception $e) {
            return false;
        }

        if (is_array($info) && !empty($info[self::PARAM_ID])) {
            return true;
        }

        return false;
    }

    /**
     * Returns information about the current box account
     *
     * @return null|array
     */
    public function getAccountInfo()
    {
        return $this->getResponseBody(self::URI_USER_ME);
    }

    public function getFolder($folderId = 0)
    {
        $path = sprintf(self::URI_FOLDER_ITEMS, $folderId);
        try {
            return $this->getResponseBody($path);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int          $parent
     * @param string|array $folderName
     *
     * @return array
     */
    public function createFolder($parent, $folderName)
    {
        if (is_array($folderName)) {
            $newFolder = null;
            $parentId  = $parent;
            foreach ($folderName as $name) {
                $newFolder = $this->createFolder($parentId, $name);
                if (empty($newFolder) || empty($newFolder[self::PARAM_ID])) {
                    $this->_throwExeption($this->_helper->__("Error during create the folder '%s'", $name));
                }
                $parentId = $newFolder[self::PARAM_ID];
            }

            return $newFolder;
        }

        $params = array(
            self::PARAM_NAME   => strval($folderName),
            self::PARAM_PARENT => array(
                self::PARAM_ID => $parent
            )
        );

        $header = array(
            Zend_Http_Client::CONTENT_TYPE => self::ENC_JSON
        );

        return $this->getResponseBody(self::URI_FOLDERS, $params, Zend_Http_Client::POST, $header, true);
    }

    public function getBackupFolderId($parent, $dirs)
    {
        foreach ($dirs as $i => $d) {
            $folder = $this->getFolder($parent);
            if (!is_array($folder)) {
                $dirParams = $this->createFolder($parent, array_slice($dirs, $i));
                $parent    = $dirParams[self::PARAM_ID];
                break;
            }

            if (empty($folder) || empty($folder[self::PARAM_FOLDER_ENTRIES]) || !is_array($folder[self::PARAM_FOLDER_ENTRIES])) {
                $dirParams = $this->createFolder($parent, array_slice($dirs, $i));
                $parent    = $dirParams[self::PARAM_ID];
                break;
            }

            foreach ($folder[self::PARAM_FOLDER_ENTRIES] as $folderEntry) {
                if (!empty($folderEntry[self::PARAM_NAME]) && $folderEntry[self::PARAM_NAME] == $d) {
                    $newParent = $folderEntry[self::PARAM_ID];
                    break;
                }
            }

            if (isset($newParent)) {
                $parent = $newParent;
                unset($newParent);
            } else {
                $dirParams = $this->createFolder($parent, array_slice($dirs, $i));
                $parent    = $dirParams[self::PARAM_ID];
                break;
            }
        }

        return $parent;
    }

    public function getBackupFolder()
    {
        $dir = $this->_getData('backup_folder');
        if (is_null($dir)) {
            $box_dir_nat = $this->getConfigValue(self::BOX_DIRECTORY);
            $box_dir     = str_replace(array('\\'), array('/'), $box_dir_nat);
            $box_dir     = trim($box_dir, '\\/');
            if (!$box_dir) {
                $dir = 0;
            } else {
                $dirs = explode('/', $box_dir);
                $dir  = $this->getBackupFolderId(0, $dirs);
            }

            if (!$dir && $box_dir_nat) {
                $this->saveConfigValue(self::BOX_DIRECTORY, '');
            } else {
                if ($box_dir_nat != $box_dir) {
                    $this->saveConfigValue(self::BOX_DIRECTORY, $box_dir);
                }
            }

            $this->setBackupFolder($dir);
        }

        return $dir;
    }


    /**
     * Uploads a new file
     *
     * @param string $path Target path (including filename)
     * @param string $file Either a path to a file or a stream resource
     *
     * @return string
     * @throws
     */
    public function putFile($path, $file)
    {
        $file = realpath($file);
        if (!file_exists($file)) {
            $this->_throwExeption($this->_helper->__('File "%s" doesn\'t exist', strval($file)));
        }

        $box_dir  = $this->getBackupFolder();
        $filename = basename($path);
        $result   = $this->multipartProcess($box_dir, $file);
        if (empty($result) || !is_array($result) || empty($result[self::PARAM_ENTRIES]) || !is_array($result[self::PARAM_ENTRIES])) {
            Mage::log('Box application result: ' . print_r($result, true));

            return false;
        }

        $entry  = array_shift($result[self::PARAM_ENTRIES]);
        $fileId = array(
            self::PARAM_ID   => $entry[self::PARAM_ID],
            self::PARAM_SHA1 => $entry[self::PARAM_SHA1]
        );

        $file_cloud_path = $this->getConfigValue(self::BOX_DIRECTORY);
        $returnPath      = $file_cloud_path . '/' . $filename;

        $this->_addAdditionalInfo($fileId, $returnPath);

        return $returnPath;
    }

    /**
     * This method is used to generate multipart POST requests for file upload
     *
     * @param string $parentId
     * @param string $file
     *
     * @return mixed
     * @throws
     */
    protected function multipartProcess($parentId, $file)
    {
        $params = array(
            self::PARAM_FILENAME  => $file,
            self::PARAM_PARENT_ID => $parentId,
        );

        return $this->getResponseBody(self::URI_FILE_UPLOAD, $params, Zend_Http_Client::POST);
    }


    /**
     * Delete cloud file
     *
     * @param string $path Target path (including file id)
     *
     * @return bool
     */
    public function deleteFile($path)
    {
        if (empty($this->_additionalInfo) || !is_array($this->_additionalInfo) || empty($this->_additionalInfo[$path])) {
            $this->_throwExeption($this->_helper->__('Wrong additional backup data'));
        }

        $url = sprintf(self::URI_FILE_DELETE, $this->_additionalInfo[$path][self::PARAM_ID]);

        return $this->getResponseBody($url, null, Zend_Http_Client::DELETE);
    }
}
