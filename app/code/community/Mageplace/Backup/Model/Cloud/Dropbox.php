<?php
/**
 * Mageplace Backup
 *
 * @category       Mageplace
 * @package        Mageplace_Backup
 * @copyright      Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

/**
 * @method string getRoot()
 */
class Mageplace_Backup_Model_Cloud_Dropbox extends Mageplace_Backup_Model_Cloud
{
	const FILE_PART_MAX_SIZE = 150;

	const CHUNK_SIZE = 4194304;

	const HMAC_SHA1 = 'HMAC-SHA1';
	const RSA_SHA1  = 'RSA-SHA1';

	const OAUTH_ACCESS_TOKEN        = 'oauth_access_token';
	const OAUTH_ACCESS_TOKEN_SECRET = 'oauth_access_token_secret';

	const CONSUMER_KEY      = 'consumerKey';
	const CONSUMER_SECRET   = 'consumerSecret';
	const DROPBOX_DIRECTORY = 'appPath';
	const TIMEOUT           = 'connTimeOut';
	const FILEPARTMAXSIZE   = 'filepartmaxsize';
	const FILECHUNKSIZE     = 'filechunksize';

	const URI               = 'https://api.dropbox.com/1/';
	const URI_REQUEST_TOKEN = 'https://api.dropbox.com/1/oauth/request_token';
	const URI_ACCESS_TOKEN  = 'https://api.dropbox.com/1/oauth/access_token';
	const URI_AUTHORIZE     = 'https://www.dropbox.com/1/oauth/authorize';
	const URI_CONTENT       = 'https://api-content.dropbox.com/1/';

	const URI_SUFFIX_ACCOUNT_INFO = 'account/info';
	const URI_SUFFIX_FILES        = 'files';
	const URI_SUFFIX_FILES_PUT    = 'files_put';
	const URI_SUFFIX_CHUNK        = 'chunked_upload';
	const URI_SUFFIX_COMMIT_CHUNK = 'commit_chunked_upload';
	const URI_SUFFIX_FILE_DELETE  = 'fileops/delete';

	const PARAM_OVERWRITE = 'overwrite';
	const PARAM_UPLOAD_ID = 'upload_id';
	const PARAM_OFFSET    = 'offset';
	const PARAM_PATH      = 'path';

	const SESSION_PARAM_UPLOAD_ID = 'step_cloud_dropbox_upload_id';

	const ROOT_DROPBOX = 'dropbox';
	const ROOT_SANDBOX = 'sandbox';

	protected function _construct()
	{
		parent::_construct();

		$this->setRoot(self::ROOT_DROPBOX);
	}

	public function getRedirectUrl()
	{
		if (!$this->getOauthInfo()) {
			return null;
		}

		return $this->getConsumer()->getRedirectUrl(array('oauth_callback' => Mage::helper("adminhtml")->getUrl('*/*/callback')));
	}

	public function callback($request, $response)
	{
		if (!$this->getOauthInfo()) {
			return null;
		}

		return true;
	}

	public function getConsumer($config = null)
	{
		if (!$consumer = $this->_getData('consumer')) {
			if ($config === null) {
				$config = $this->_getConsumerConfig();
			}

			$httpClient = new Zend_Http_Client();
			$httpClient->setAdapter($this->_getAdapter());


			if ($this->getTimeOut() > 0) {
				$httpClient->setConfig(array('timeout' => $this->getTimeOut()));
			}

			$consumer = new Zend_Oauth_Consumer($config);
			$consumer->setHttpClient($httpClient);

			$this->setData('consumer', $consumer);
		}

		return $consumer;
	}

	/**
	 * @return Zend_Http_Client_Adapter_Curl
	 */
	protected function _getAdapter()
	{
		$adapter = new Zend_Http_Client_Adapter_Curl();
		#$adapter->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
		$adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, 2);

		$adapter->setCurlOption(CURLOPT_SSLVERSION, 1);

		$sslCiphersuiteList = $this->getSslCiphersuiteList();
		if (null !== $sslCiphersuiteList) {
			$this->set(CURLOPT_SSL_CIPHER_LIST, $sslCiphersuiteList);
		}
		$this->set(CURLOPT_CAINFO, Mage::getBaseDir('lib') . '/mpbackup/dropbox/certs/trusted-certs.crt');
		$this->set(CURLOPT_CAPATH, Mage::getBaseDir('lib') . '/mpbackup/dropbox/certs/');

		if (defined('CURLOPT_PROTOCOLS')) $this->set(CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
		if (defined('CURLOPT_REDIR_PROTOCOLS')) $this->set(CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);

		return $adapter;
	}

	protected function _getConsumerConfig()
	{
		return array(
			'consumerKey'     => $this->getConfigValue(self::CONSUMER_KEY),
			'consumerSecret'  => $this->getConfigValue(self::CONSUMER_SECRET),
			'requestTokenUrl' => self::URI_REQUEST_TOKEN,
			'accessTokenUrl'  => self::URI_ACCESS_TOKEN,
			'authorizeUrl'    => self::URI_AUTHORIZE,
			'requestScheme'   => Zend_Oauth::REQUEST_SCHEME_QUERYSTRING,
			'requestMethod'   => Zend_Oauth::GET,
			'signatureMethod' => self::HMAC_SHA1,
		);
	}

	public function getOauthInfo()
	{
		$oauth_token_secret = $oauth_token = null;

		$config   = $this->_getConsumerConfig();
		$consumer = $this->getConsumer($config);

		$session = $this->_getSession();
		/* @var $session Mageplace_Backup_Model_Session */
		if ($session->checkCloud($config)) {
			$oauth_token        = $session->getOauthToken();
			$oauth_token_secret = $session->getOauthTokenSecret();
		}

		if ($oauth_token && $oauth_token_secret) {
			$requestToken = new Zend_Oauth_Token_Request();
			$requestToken->setToken($oauth_token);
			$requestToken->setTokenSecret($oauth_token_secret);

			$accessToken = $consumer->getAccessToken($_GET, $requestToken);
			if (!($token_key = $accessToken->getToken()) || !($token_secret = $accessToken->getTokenSecret())) {
				return false;
			}

			$this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, $token_key);
			$this->saveConfigValue(self::OAUTH_ACCESS_TOKEN_SECRET, $token_secret);

			$session->setAccessToken($accessToken);

			return true;
		}

		try {
			$token_request = $consumer->getRequestToken();
		} catch (Exception $e) {
			Mage::logException($e);

			return null;
		}

		$response = $token_request->getResponse();
		parse_str($response->getBody());

		if (!$oauth_token || !$oauth_token_secret) {
			try {
				$body = Zend_Json::decode($response->getBody());
				switch ($response->getStatus()) {
					case 304:
						$error = 'Empty response body.';
						break;

					case 403:
						$error = 'Forbidden. This could mean a bad OAuth request.' . @$body["error"];
						break;

					case 404:
						$error = 'Resource at uri: ' . self::URI_REQUEST_TOKEN . ' could not be found. ' . @$body["error"];
						break;

					case 507:
						$error = 'This dropbox is full. ' . @$body["error"];
						break;
				}
				if (isset($error)) {
					$e = new Mage_Exception($error, null);
					Mage::logException($e);
					Mage::getSingleton('adminhtml/session')->addError($error);

					return null;
				}
			} catch (Exception $e) {
				Mage::logException($e);

				return null;
			}
		}

		$this->setData('consumer', $consumer);
		$this->setData('oauth_token', $oauth_token);
		$this->setData('oauth_token_secret', $oauth_token_secret);

		$session->setCloudId($config)
			->setOauthToken($oauth_token)
			->setOauthTokenSecret($oauth_token_secret);

		return true;
	}

	public function getAccessToken($reset_session = false)
	{
		if (!$accessToken = $this->_getData('access_token')) {
			$session = $this->_getSession();
			if ($reset_session) {
				$session->unsAccessToken();
			} else {
				$accessToken = $session->getAccessToken();
			}

			if (empty($accessToken)) {
				$oauth_token        = $this->getConfigValue(self::OAUTH_ACCESS_TOKEN);
				$oauth_token_secret = $this->getConfigValue(self::OAUTH_ACCESS_TOKEN_SECRET);
				if (!$oauth_token || !$oauth_token_secret) {
					return null;
				}

				$accessToken = new Zend_Oauth_Token_Access();
				$accessToken->setToken($oauth_token);
				$accessToken->setTokenSecret($oauth_token_secret);

				$session->setAccessToken($accessToken);
			}

			$this->setAccessToken($accessToken);
		}

		return $accessToken;
	}

	public function checkConnection()
	{
		try {
			$info = $this->getAccountInfo();
		} catch (Exception $e) {
			$this->getAccessToken(true);
			try {
				$info = $this->getAccountInfo();
			} catch (Exception $e) {
				return false;
			}
		}


		if (!empty($info['uid'])) {
			return true;
		}

		return false;
	}

	public function resetAuthData()
	{
		$this->saveConfigValue(self::OAUTH_ACCESS_TOKEN, '');
		$this->saveConfigValue(self::OAUTH_ACCESS_TOKEN_SECRET, '');

		$this->_getSession()->unsetData();

		return $this;
	}

    /**
     * Process request to Dropbox API
     *
     * @param $uri
     * @param array $arguments
     * @param string $method
     * @param null $httpHeaders
     * @param null $requestScheme
     * @return array
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     * @throws Zend_Json_Exception
     * @throws Zend_Uri_Exception
     */
	public function process($uri, $arguments = array(), $method = Zend_Oauth::GET, $httpHeaders = null, $requestScheme = null)
	{
		$token = $this->getAccessToken();
		/* @var $token Zend_Oauth_Token_Access */
		if (!($token instanceof Zend_Oauth_Token_Access)) {
			return array();
		}


		$oauthOptions = array(
			'consumerKey'     => $this->getConfigValue(self::CONSUMER_KEY),
			'consumerSecret'  => $this->getConfigValue(self::CONSUMER_SECRET),
			'signatureMethod' => self::HMAC_SHA1,
		);

		/* @var Zend_Oauth_Client $oauthClient */
		$oauthClient = $token->getHttpClient($oauthOptions);
		if ($requestScheme !== null) {
			$oauthClient->setRequestScheme($requestScheme);
		}
		if ($this->getTimeOut() > 0) {
			$oauthClient->setConfig(array('timeout' => $this->getTimeOut()));
		}

		$oauthClient->setAdapter($this->_getAdapter());
		$oauthClient->setMethod($method);
		$oauthClient->setUri($uri);

		if (is_array($arguments)) {
			if ($method == Zend_Oauth::GET) {
				$method = "setParameterGet";
			} else {
				$method = "setParameterPost";
			}

			foreach ($arguments as $param => $value) {
				$oauthClient->$method($param, $value);
			}

		} elseif (is_string($arguments)) {
			preg_match("/\?file=(.*)$/i", $uri, $matches);
			if (isset($matches[1])) {
				$uri      = str_replace($matches[0], "", $uri);
				$filename = $matches[1];
				$uri      = Zend_Uri::factory($uri);
				if (method_exists($uri, 'addReplaceQueryParameters')) {
					$uri->addReplaceQueryParameters(array("file" => $filename));
				} else {
					$this->addReplaceQueryParameters($uri, array("file" => $filename));
				}
				$oauthClient->setParameterGet("file", $filename);
			}
			$oauthClient->setUri($uri);
			$oauthClient->setRawData($arguments);
		} elseif (is_resource($arguments)) {
			$oauthClient->setRawData($arguments);
		}

		if (is_array($httpHeaders)) {
			foreach ($httpHeaders as $k => $v) {
				$oauthClient->setHeaders($k, $v);
			}
		}

		$response = $oauthClient->request();
		$body     = Zend_Json::decode($response->getBody());

		switch ($response->getStatus()) {
			case 304 :
				return array();
				break;

			case 403 :
				$error = 'Forbidden. This could mean a bad OAuth request, or a file or folder already existing at the target location. Error: ' . @$body["error"];
				break;

			case 404 :
				$error = 'Resource at uri: ' . $uri . ' could not be found. Error: ' . @$body["error"];
				break;

			case 507 :
				$error = 'This dropbox is full. Error: ' . @$body["error"];
				break;
		}

		if (is_array($body) && !empty($body["error"])) {
			$error = $body["error"];
		}

		if (isset($error)) {
			$e = Mage::exception('Mageplace_Backup', $error);
			Mage::logException($e);
			throw $e;
		}

		return $body;
	}

	/**
	 * Returns information about the current dropbox account
	 *
	 * @return array
	 */
	public function getAccountInfo()
	{
		return $this->process(self::URI . self::URI_SUFFIX_ACCOUNT_INFO);
	}

	/**
	 * Uploads a new file
	 *
	 * @param string $path Target path (including filename)
	 * @param string $file Either a path to a file or a stream resource
	 *
	 * @return string
	 */
	public function putFile($path, $file)
	{
		if (is_string($file)) {
			$file = fopen($file, 'rb');
		} elseif (!is_resource($file)) {
			Mage::throwException($this->_helper->__('File "%s" must be a file-resource or a string', strval($file)));
		}

		$filename        = basename($path);
		$root            = $this->getRoot();
		$file_cloud_path = trim($this->getConfigValue(self::DROPBOX_DIRECTORY), '/');
		$file_cloud_path = str_replace(' ', '_', trim($file_cloud_path));
		$result          = $this->multipartProcess(self::URI_CONTENT . self::URI_SUFFIX_FILES . '/' . $root . '/' . $file_cloud_path, $file, $filename);
		if (empty($result['bytes'])) {
			return false;
		}

		return $file_cloud_path . '/' . $filename;
	}

	/**
	 * This method is used to generate multipart POST requests for file upload
	 *
	 * @param string $uri
	 * @param resource $file
	 * @param string $filename
	 *
	 * @return bool
	 */
	protected function multipartProcess($uri, $file, $filename)
	{
		$boundary = 'eiiHUH23EFef23f65jk8979jakhJKH8934JGGggVtE5675rcvuwcf7w6e2DB56e6dc6DYD';

		$headers = array(
			'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
		);

		$body = "--" . $boundary . "\r\n";
		$body .= "Content-Disposition: form-data; name=file; filename=" . rawurldecode($filename) . "\r\n";
		$body .= "Content-type: application/octet-stream\r\n";
		$body .= "\r\n";
		$body .= stream_get_contents($file);
		$body .= "\r\n";
		$body .= "--" . $boundary . "--";

		// Dropbox requires the filename to also be part of the regular arguments, so it becomes
		// part of the signature.
		$uri .= '?file=' . $filename;

		return $this->process($uri, $body, Zend_Oauth::POST, $headers);
	}

	public function putFileChunk($path, $handle)
	{
		if (is_string($handle)) {
			$handle    = fopen($handle, 'rb');
			$needClose = true;
		} elseif (!is_resource($handle)) {
			Mage::throwException($this->_helper->__('File "%s" must be a file-resource or a string', strval($handle)));
		}

		$byte = $startByte = (float)$this->getRequestBytes();
		if ($byte > 0) {
			$this->fseek($handle, $byte);
		}

		$uploadId = $this->getRequestUploadId();

		while (!feof($handle)) {
			if ($this->timeIsUp()) {
				$this->setRequestBytes($byte);
				$this->setRequestUploadId($uploadId);
				$nextChunk = true;
				break;
			}

			$part        = fread($handle, $this->getChunkSize());
			$partLength  = strlen($part);
			$chunkHandle = fopen('php://temp', 'w+b');
			fwrite($chunkHandle, $part, $partLength);
			fseek($chunkHandle, 0);

			$params = array(
				self::PARAM_UPLOAD_ID => $uploadId,
				self::PARAM_OFFSET    => $byte,
			);
			$uri    = self::URI_CONTENT . self::URI_SUFFIX_CHUNK . '?' . http_build_query($params, '', '&');

			$response = $this->process($uri, $chunkHandle, Zend_Oauth::PUT, null, Zend_Oauth::REQUEST_SCHEME_QUERYSTRING);

			fclose($chunkHandle);

			if (!is_array($response) || empty($response[self::PARAM_UPLOAD_ID])) {
				Mage::log($response);
				$this->_throwExeption($this->_helper->__('Error chunk upload response'));
			}

			$uploadId = $response[self::PARAM_UPLOAD_ID];

			$byte += $this->getChunkSize();
		}

		if (!$fileSize = $this->getRequestFileSize()) {
			$fileSize = $this->filesize($handle);
		}

		if (isset($needClose)) {
			fclose($handle);
		}

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


		return $this->commitChunkPutFile($path, $uploadId);
	}

	protected function commitChunkPutFile($path, $uploadId)
	{
		$filename      = basename($path);
		$root          = $this->getRoot();
		$fileCloudPath = trim($this->getConfigValue(self::DROPBOX_DIRECTORY), '/');
		$fileCloudPath = str_replace(' ', '_', trim($fileCloudPath));

		$params = array(
			self::PARAM_OVERWRITE => 1,
			self::PARAM_UPLOAD_ID => $uploadId
		);

		$response = $this->process(self::URI_CONTENT . self::URI_SUFFIX_COMMIT_CHUNK . '/' . $root . '/' . $fileCloudPath . '/' . $filename, $params, Zend_Oauth::POST);

		$this->clearRequestParams();

		return @$response[self::PARAM_PATH];
	}

	/**
	 * Magento version < 1.4.2
	 */
	public function addReplaceQueryParameters(&$uri, $queryParams)
	{
		$queryParams = array_merge($this->getQueryAsArray($uri), $queryParams);

		return $uri->setQuery($queryParams);
	}

	public function getQueryAsArray(&$uri)
	{
		$query       = $uri->getQuery();
		$querryArray = array();
		if ($query !== false) {
			parse_str($query, $querryArray);
		}

		return $querryArray;
	}

	/**
	 * Delete file
	 *
	 * @param string $path Target path (including filename)
	 *
	 * @return bool
	 */
	public function deleteFile($path)
	{
		$result = $this->process(self::URI . self::URI_SUFFIX_FILE_DELETE, array('path' => $path, 'root' => $this->getRoot()));

		return !empty($result['is_deleted']);
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

	public function getTimeOut()
	{
		if ($this->_getData('time_out') === null) {
			if ($this->getBackup() instanceof Mageplace_Backup_Model_Backup && $this->getBackup()->isTimeLimitMultiStep()) {
				$this->setData('time_out', $this->getBackup()->getTimeLimit());
			} else {
				$this->setData('time_out', (int)$this->getConfigValue(self::TIMEOUT));
			}
		}

		return $this->_getData('time_out');
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

    protected function getSessionUploadId()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_UPLOAD_ID);
    }

    protected function getRequestUploadId()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_UPLOAD_ID);
    }

	protected function setSessionUploadId($uploadId)
	{
		$this->_getSession()->setData(self::SESSION_PARAM_UPLOAD_ID, $uploadId);

		return $this;
	}

    protected function setRequestUploadId($uploadId)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_UPLOAD_ID => $uploadId));

        return $this;
    }

    protected function clearSessionParams()
    {
        parent::clearSessionParams();

        $this->_getSession()->unsetData(self::SESSION_PARAM_UPLOAD_ID);
    }

    protected function clearRequestParams()
    {
        parent::clearRequestParams();

        $this->setRequestUploadId(null);
    }

	protected function getSslCiphersuiteList()
	{
		$curlVersion    = curl_version();
		$curlSslBackend = $curlVersion['ssl_version'];
		if (substr_compare($curlSslBackend, "NSS/", 0, strlen("NSS/")) === 0) {
			$sslCiphersuiteList = null;
		} else {
			$sslCiphersuiteList =
				'ECDHE-RSA-AES256-GCM-SHA384:' .
				'ECDHE-RSA-AES128-GCM-SHA256:' .
				'ECDHE-RSA-AES256-SHA384:' .
				'ECDHE-RSA-AES128-SHA256:' .
				'ECDHE-RSA-AES256-SHA:' .
				'ECDHE-RSA-AES128-SHA:' .
				'ECDHE-RSA-RC4-SHA:' .
				'DHE-RSA-AES256-GCM-SHA384:' .
				'DHE-RSA-AES128-GCM-SHA256:' .
				'DHE-RSA-AES256-SHA256:' .
				'DHE-RSA-AES128-SHA256:' .
				'DHE-RSA-AES256-SHA:' .
				'DHE-RSA-AES128-SHA:' .
				'AES256-GCM-SHA384:' .
				'AES128-GCM-SHA256:' .
				'AES256-SHA256:' .
				'AES128-SHA256:' .
				'AES256-SHA:' .
				'AES128-SHA';
		}

		return $sslCiphersuiteList;
	}
}
