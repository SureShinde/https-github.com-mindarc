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
 * Class Mageplace_Backup_Model_Cloud_Ftp
 *
 * @method Mageplace_Backup_Model_Cloud_Ftp setFtp
 */
class Mageplace_Backup_Model_Cloud_Ftp extends Mageplace_Backup_Model_Cloud
{
    const FILE_PART_MAX_SIZE = 100;

    const CHUNK_SIZE = 5242880; /* == 5Mb */

    const USE_CURL = false;

    const HOST              = 'host';
    const PORT              = 'port';
    const USERNAME          = 'username';
    const PASSWORD          = 'password';
    const TIMEOUT           = 'timeout';
    const TIMEOUT_DISABLE   = 'timeout_disable';
    const SSL               = 'ssl';
    const PASSIVE           = 'passive';
    const PATH              = 'path';
    const FILEPARTMAXSIZE   = 'filepartmaxsize';
    const FILECHUNKSIZE     = 'filechunksize';
    const UPLOADMETHOD      = 'uploadmethod';
    const UPLOADMETHOD_CURL = 0;
    const UPLOADMETHOD_PUT  = 1;
    const UPLOADMETHOD_FTP  = 2;

    public function connect(array $config = array())
    {
        $ftp = new Varien_Io_Ftp();

        if (empty($config)) {
            $config['host']     = $this->getConfigValue(self::HOST);
            $config['port']     = (int)$this->getConfigValue(self::PORT);
            $config['user']     = $this->getConfigValue(self::USERNAME);
            $config['password'] = $this->getConfigValue(self::PASSWORD);
            $config['timeout']  = $this->getTimeOut();
            $config['ssl']      = $this->getConfigValue(self::SSL);
            $config['passive']  = $this->getConfigValue(self::PASSIVE);
            $config['path']     = $this->getConfigValue(self::PATH);
        }

        try {
            $ftp->open($config);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        $this->setFtp($ftp);

        return $this;
    }

    /**
     * @return Varien_Io_Ftp
     */
    public function getFtp()
    {
        if (!$this->_getData('ftp')) {
            $this->connect();
        }

        return $this->_getData('ftp');
    }

    /**
     * Uploads a new file
     *
     * @param string $path Target path (including filename)
     * @param string $file Either a path to a file or a stream resource
     *
     * @throws Mageplace_Backup_Exception
     * @return bool
     */
    public function putFile($path, $file)
    {
        $filename = basename($path);
        if (empty($filename)) {
            $this->_throwExeption($this->_helper->__('Error file name'));
        }

        $path      = $this->getConfigValue(self::PATH);
        $putObject = (empty($path) ? '' : $path) . '/' . $filename;

        try {
            $ftp = $this->getFtp();

            if (!$ftp->write($filename, $file)) {
                $this->_throwExeption($this->_helper->__('"%s" file is not uploaded to FTP server', $putObject));
            }

            return $putObject;

        } catch (Exception $e) {
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        return false;
    }

    public function putFileChunk($path, $handle)
    {
        if ($this->getUploadMethod() == self::UPLOADMETHOD_FTP) {
            return $this->putFile($path, $handle);
        }

        if (is_string($handle)) {
            $handle    = fopen($handle, 'rb');
            $needClose = true;
        } elseif (!is_resource($handle)) {
            $this->_throwExeption($this->_helper->__('File "%s" must be a file-resource or a string', strval($handle)));
        }

        $filename  = basename($path);
        $path      = $this->getConfigValue(self::PATH);
        $putObject = (empty($path) ? '' : $path) . '/' . $filename;

        $byte = $startByte = (float)$this->getRequestBytes();
        if ($byte > 0) {
            $this->fseek($handle, $byte);
        }

        $port = (int)$this->getConfigValue(self::PORT);
        $url  = sprintf(
            '%s://%s:%s@%s:%d/%s',
            $this->getConfigValue(self::SSL) ? 'sftp' : 'ftp',
            $this->getConfigValue(self::USERNAME),
            $this->getConfigValue(self::PASSWORD),
            $this->getConfigValue(self::HOST),
            $port ? $port : 21,
            $putObject
        );

        $chunk = $this->getChunkSize();

        while (!feof($handle)) {
            if ($this->timeIsUp()) {
                $this->setRequestBytes($byte);
                $nextChunk = true;
                break;
            }

            $part = fread($handle, $chunk);
            if ($this->getUploadMethod() == self::UPLOADMETHOD_PUT) {
                file_put_contents($url, $part, FILE_APPEND);
            } else {
                $partLength  = strlen($part);
                $chunkHandle = fopen('php://temp', 'w+b');
                fwrite($chunkHandle, $part, $partLength);
                fseek($chunkHandle, 0);

                $ch = curl_init();

                @curl_setopt($ch, CURLOPT_UPLOAD, 1);
                if(!$this->getConfigValue(self::TIMEOUT_DISABLE)) {
                    @curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeOut());
                    @curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, $this->getTimeOut());
                }
                @curl_setopt($ch, CURLOPT_URL, $url);
                @curl_setopt($ch, CURLOPT_FTPAPPEND, 1);
                @curl_setopt($ch, CURLOPT_INFILE, $chunkHandle);
                @curl_setopt($ch, CURLOPT_INFILESIZE, $partLength);
                @curl_exec($ch);

                $errorMsg    = @curl_error($ch);
                $errorNumber = @curl_errno($ch);
                @curl_close($ch);


                fclose($chunkHandle);

                if ($errorMsg || $errorNumber) {
                    Mage::log($errorMsg);
                    Mage::log($errorNumber);
                    $this->_throwExeption($this->_helper->__('Error chunk upload response') . '. ' . $errorMsg);
                }
            }

            $byte += $chunk;
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

        return $putObject;
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
        return $this->getFtp()->rm($path);
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

    public function getUploadMethod()
    {
        if ($this->_getData('upload_method') === null) {
            $this->setData('upload_method', $this->getConfigValue(self::UPLOADMETHOD));
        }

        return $this->_getData('upload_method');
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

    public function __destruct()
    {
        /* @var $ftp Varien_Io_Ftp */
        if ($ftp = $this->_getData('ftp')) {
            $ftp->close();
        }
    }
}
