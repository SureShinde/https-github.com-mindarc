<?php
/**
 * Mageplace Backup
 *
 * @category       Mageplace
 * @package        Mageplace_Backup
 * @copyright      Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license        http://www.mageplace.com/disclaimer.html
 */

use Aws\S3\S3Client;

/**
 * @method Mageplace_Backup_Model_Cloud_Amazons3 setS3
 * @method Mageplace_Backup_Model_Cloud_Amazons3 setBucket
 * @method S3Client getS3
 * @method string getBucket
 */
class Mageplace_Backup_Model_Cloud_Amazons3 extends Mageplace_Backup_Model_Cloud
{
    const FILE_PART_MAX_SIZE = 100;

    const CHUNK_SIZE = 5242880; /* == 5Mb */

    const ACCESS_KEY       = 'accessKey';
    const SECRET_KEY       = 'secretKey';
    const BUCKET           = 'bucketPath';
    const BUCKET_DIRECTORY = 'appPath';
    const TIMEOUT          = 'connTimeOut';
    const FILEPARTMAXSIZE  = 'filepartmaxsize';
    const FILECHUNKSIZE    = 'filechunksize';
    const REGION           = 'region';

    const PARAM_KEY        = 'key';
    const PARAM_SECRET     = 'secret';
    const PARAM_OBJECT_URL = 'ObjectURL';
    const PARAM_UPLOAD_ID  = 'UploadId';
    const PARAM_ETAG       = 'ETag';
    const PARAM_LOCATION   = 'Location';
    const PARAM_REGION     = 'region';


    const CONFIG_PARAM_BUCKET      = 'Bucket';
    const CONFIG_PARAM_KEY         = 'Key';
    const CONFIG_PARAM_SOURCE_FILE = 'SourceFile';
    const CONFIG_PARAM_UPLOAD_ID   = 'UploadId';
    const CONFIG_PARAM_PART_NUMBER = 'PartNumber';
    const CONFIG_PARAM_PARTS       = 'Parts';
    const CONFIG_PARAM_BODY        = 'Body';
    const CONFIG_PARAM_ETAG        = 'ETag';

    const SESSION_PARAM_UPLOAD_ID   = 'step_cloud_amazons3_upload_id';
    const SESSION_PARAM_PART_NUMBER = 'step_cloud_amazons3_part_number';
    const SESSION_PARAM_PARTS       = 'step_cloud_amazons3_parts';

    protected $_autoloads;

    static $REGIONS = array(
        'us-east-1'      => 'US Standard',
        'us-west-2'      => 'US West (Oregon)',
        'us-west-1'      => 'US West (N. California)',
        'eu-west-1'      => 'EU (Ireland)',
        'eu-central-1'   => 'EU (Frankfurt)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'sa-east-1'      => 'South America (Sao Paulo)',
    );

    public function initS3()
    {
        try {
            $key    = $this->getConfigValue(self::ACCESS_KEY);
            $secret = $this->getConfigValue(self::SECRET_KEY);
            $root   = trim($this->getConfigValue(self::BUCKET), '/');
            $region = $this->getConfigValue(self::REGION);
            /*$timeOut = (int)$this->getConfigValue(self::TIMEOUT);*/

            $this->registerAwsAutoload();

            $s3 = S3Client::factory(array(
                self::PARAM_KEY    => $key,
                self::PARAM_SECRET => $secret,
                self::PARAM_REGION => $region,
            ));

            if (!$root || !$s3->doesBucketExist($root)) {
                $this->restoreAutoload();
                throw Mage::exception('Mageplace_Backup', $this->_helper->__('Bucket "%s" not available', $root));
            }

            $this->setBucket($root);

            return $s3;
        } catch (Exception $e) {
            $this->restoreAutoload();
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }
    }

    /**
     * Uploads a new file
     *
     * @param string $filename Target path (including filename)
     * @param string $filePath Either a path to a file or a stream resource
     *
     * @return bool
     */
    public function putFile($filename, $filePath)
    {
        $filePath = realpath($filePath);
        if (!file_exists($filePath)) {
            $this->_throwExeption($this->_helper->__('File "%s" doesn\'t exist', strval($filePath)));
        }

        $dirInBucket   = trim($this->getConfigValue(self::BUCKET_DIRECTORY), '/');
        $filename      = basename($filename);
        $putObjectPath = $dirInBucket . '/' . $filename;

        $s3 = $this->initS3();

        $bucket = $this->getBucket();

        try {
            $response = $s3->putObject(array(
                self::CONFIG_PARAM_BUCKET      => $bucket,
                self::CONFIG_PARAM_KEY         => $putObjectPath,
                self::CONFIG_PARAM_SOURCE_FILE => $filePath,
            ));
        } catch (Exception $e) {
            $this->restoreAutoload();
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        $this->restoreAutoload();

        if (empty($response[self::PARAM_OBJECT_URL])) {
            Mage::logException(Mage::exception('Mageplace_Backup', $this->_helper->__('Object "%s" not uploaded to cloud server', $bucket . '/' . $putObjectPath)));
        }

        $this->_addAdditionalInfo(array(
            self::CONFIG_PARAM_BUCKET => $bucket,
            self::CONFIG_PARAM_KEY    => $putObjectPath
        ));

        return $bucket . '/' . $putObjectPath;
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

        $dirInBucket   = trim($this->getConfigValue(self::BUCKET_DIRECTORY), '/');
        $filename      = basename($filename);
        $putObjectPath = $dirInBucket . '/' . $filename;

        if (!$fileSize = $this->getRequestFileSize()) {
            $fileSize = $this->filesize($file);
        }

        $byte = $startByte = (float)$this->getRequestBytes();

        $isNextStep = $byte > 0;
        if ($isNextStep) {
            $this->fseek($handle, $byte);
            $uploadId   = $this->getRequestUploadId();
            $partNumber = $this->getRequestPartNumber();
            $parts      = $this->getRequestParts();
        } else {
            $partNumber = 1;
            $parts      = array();
        }

        $s3     = $this->initS3();
        $bucket = $this->getBucket();
        if (!$isNextStep) {
            try {
                $response = $s3->createMultipartUpload(array(
                    self::CONFIG_PARAM_BUCKET => $bucket,
                    self::CONFIG_PARAM_KEY    => $putObjectPath,
                ));
            } catch (Exception $e) {
                $this->restoreAutoload();
                Mage::logException($e);
                $this->_throwExeption($e->getMessage());
            }

            if (empty($response[self::PARAM_UPLOAD_ID])) {
                $this->restoreAutoload();
                Mage::log($response);
                $this->_throwExeption($this->_helper->__('Error chunk upload response'));
            }

            $uploadId = $response[self::PARAM_UPLOAD_ID];
        }

        if (empty($uploadId)) {
            $this->restoreAutoload();
            $this->_throwExeption($this->_helper->__('Error chunk upload id'));
        }

        while (!feof($handle)) {
            if ($this->timeIsUp()) {
                $this->setRequestBytes($byte);
                $this->setRequestUploadId($uploadId);
                $this->setRequestPartNumber($partNumber);
                $this->setRequestParts($parts);
                $nextChunk = true;
                break;
            }

            $chunk = fread($handle, $chunkSize);

            try {
                $response = $s3->uploadPart(array(
                    self::CONFIG_PARAM_BUCKET      => $bucket,
                    self::CONFIG_PARAM_KEY         => $putObjectPath,
                    self::CONFIG_PARAM_UPLOAD_ID   => $uploadId,
                    self::CONFIG_PARAM_PART_NUMBER => $partNumber,
                    self::CONFIG_PARAM_BODY        => $chunk,
                ));
            } catch (Exception $e) {
                fclose($handle);
                $this->restoreAutoload();
                $this->clearRequestParams();
                Mage::logException($e);
                $this->_throwExeption($e->getMessage());
            }

            if (!isset($response) || empty($response[self::PARAM_ETAG])) {
                $this->restoreAutoload();
                fclose($handle);
                $this->clearRequestParams();
                $this->_throwExeption($this->_helper->__('Error chunk upload part response'));
            }

            $parts[] = array(
                self::CONFIG_PARAM_PART_NUMBER => $partNumber,
                self::CONFIG_PARAM_ETAG        => $response[self::PARAM_ETAG],
            );

            ++$partNumber;
            $byte += $chunkSize;
        }

        fclose($handle);

        $this->restoreAutoload();

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

        $this->registerAwsAutoload();

        try {
            $response = $s3->completeMultipartUpload(array(
                self::CONFIG_PARAM_BUCKET    => $bucket,
                self::CONFIG_PARAM_KEY       => $putObjectPath,
                self::CONFIG_PARAM_UPLOAD_ID => $uploadId,
                self::CONFIG_PARAM_PARTS     => $parts,
            ));
        } catch (Exception $e) {
            $this->restoreAutoload();
            $this->clearRequestParams();
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        $this->restoreAutoload();

        if (!isset($response) || empty($response[self::PARAM_LOCATION])) {
            $this->_throwExeption($this->_helper->__('Error chunk upload complete response'));
        }

        $this->_addAdditionalInfo(array(
            self::CONFIG_PARAM_BUCKET => $bucket,
            self::CONFIG_PARAM_KEY    => $putObjectPath
        ));

        $this->clearRequestParams();

        return $bucket . '/' . $putObjectPath;
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
        if (empty($this->_additionalInfo) || !is_array($this->_additionalInfo)) {
            $this->_throwExeption($this->_helper->__('Wrong additional backup data'));
        }

        $params = array_shift($this->_additionalInfo);
        if (empty($params) || !is_array($params)) {
            $this->_throwExeption($this->_helper->__('Wrong additional backup data'));
        }

        $s3 = $this->initS3();

        try {
            $s3->deleteObject($params);
        } catch (Exception $e) {
            $this->restoreAutoload();
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        $this->restoreAutoload();

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

    protected function getSessionPartNumber()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_PART_NUMBER);
    }

    protected function getRequestPartNumber()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_PART_NUMBER);
    }

    protected function setSessionPartNumber($partNumber)
    {
        $this->_getSession()->setData(self::SESSION_PARAM_PART_NUMBER, $partNumber);

        return $this;
    }

    protected function setRequestPartNumber($partNumber)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_PART_NUMBER => $partNumber));

        return $this;
    }

    protected function getSessionParts()
    {
        return $this->_getSession()->getData(self::SESSION_PARAM_PARTS);
    }

    protected function getRequestParts()
    {
        return $this->getBackup()->getStepCloudData(self::SESSION_PARAM_PARTS);
    }

    protected function setSessionParts($parts)
    {
        $this->_getSession()->setData(self::SESSION_PARAM_PARTS, $parts);

        return $this;
    }

    protected function setRequestParts($parts)
    {
        $this->getBackup()->addStepCloudData(array(self::SESSION_PARAM_PARTS => $parts));

        return $this;
    }

    protected function clearSessionParams()
    {
        parent::clearSessionParams();

        $this->_getSession()->unsetData(self::SESSION_PARAM_UPLOAD_ID);
        $this->_getSession()->unsetData(self::SESSION_PARAM_PARTS);
        $this->_getSession()->unsetData(self::SESSION_PARAM_PART_NUMBER);
    }

    protected function clearRequestParams()
    {
        parent::clearRequestParams();

        $this->setRequestUploadId(null);
        $this->setRequestPartNumber(null);
        $this->setRequestParts(null);
    }

    protected function registerAwsAutoload()
    {
        if ($this->_autoloads !== null) {
            return;
        }

        $libDir = $this->_helper->getLibDir();

        $this->_autoloads = spl_autoload_functions();
        foreach ($this->_autoloads as $autoload) {
            spl_autoload_unregister($autoload);
        }

        require $libDir . DS . 'amazon' . DS . 'AWSSDK' . DS . 'aws-autoloader.php';
    }

    protected function restoreAutoload()
    {
        if ($this->_autoloads === null) {
            return;
        }

        $autoloads = spl_autoload_functions();
        foreach ($autoloads as $autoload) {
            spl_autoload_unregister($autoload);
        }
        unset($autoload);

        foreach ($this->_autoloads as $autoload) {
            spl_autoload_register($autoload);
        }
        unset($autoload);

        $this->_autoloads = null;

    }
}
