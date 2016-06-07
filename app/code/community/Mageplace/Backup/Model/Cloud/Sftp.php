<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

set_include_path(get_include_path() . PATH_SEPARATOR . Mage::helper('mpbackup')->getLibDir() . DS . 'phpseclib');

require_once 'Net' . DS . 'SFTP.php';

/**
 * Class Mageplace_Backup_Model_Cloud_Sftp
 */
class Mageplace_Backup_Model_Cloud_Sftp extends Mageplace_Backup_Model_Cloud
{
    const DEFAULT_TIMEOUT = 10;
    const DEFAULT_PORT    = 22;

    const FILE_PART_MAX_SIZE = 100;

    const CHUNK_SIZE = 5242880; /* == 5Mb */

    const HOST            = 'host';
    const PORT            = 'port';
    const USERNAME        = 'username';
    const PASSWORD        = 'password';
    const TIMEOUT         = 'timeout';
    const SSL             = 'ssl';
    const PASSIVE         = 'passive';
    const PATH            = 'path';
    const FILEPARTMAXSIZE = 'filepartmaxsize';
    const FILECHUNKSIZE   = 'filechunksize';

    /**
     * @param array $config
     *
     * @throws Mageplace_Backup_Exception|Mage_Core_Exception
     * @return Net_SFTP
     */
    public function getSftp(array $config = array())
    {
        if ($this->_getData('sftp') === null) {
            $host = $this->getConfigValue(self::HOST);
            $port = (int)$this->getConfigValue(self::PORT);
            if ($port <= 0) {
                $port = self::DEFAULT_PORT;
            }

            $sftp = new Net_SFTP($host, $port, $this->getTimeOut());
            if (!$sftp->login($this->getConfigValue(self::USERNAME), $this->getConfigValue(self::PASSWORD))) {
                $this->_throwExeption($this->_helper->__("SFTP connection error: unable to open connection as %s@%s:%d", $this->getConfigValue(self::USERNAME), $host, $port));
            }

            if ($path = rtrim($this->getConfigValue(self::PATH), '/')) {
                if (!$sftp->chdir($path)) {
                    $this->_throwExeption($this->_helper->__('SFTP connection error: invalid path'));
                }
            }

            $this->setData('sftp', $sftp);
        }

        return $this->_getData('sftp');
    }

    /**
     * Uploads a new file
     *
     * @param string          $name   Target path (including filename)
     * @param resource|string $handle Either a path to a file or a stream resource
     *
     * @throws Mageplace_Backup_Exception|Mage_Core_Exception
     * @return bool
     */
    public function putFile($name, $handle)
    {
        if (is_string($handle)) {
            $handle    = fopen($handle, 'rb');
            $needClose = true;
        } elseif (!is_resource($handle)) {
            $this->_throwExeption($this->_helper->__('SFTP connection error: file "%s" must be a file-resource or a string', strval($handle)));
        }

        $filename = basename($name);

        $sftp = $this->getSftp();

        try {
            $chunk = $this->getChunkSize();
            while (!feof($handle)) {
                $content = fread($handle, $chunk);
                $sftp->put($filename, $content, NET_SFTP_RESUME);
            }

        } catch (Exception $e) {
            if (isset($needClose)) {
                fclose($handle);
            }
            Mage::logException($e);
            $this->_throwExeption($e->getMessage());
        }

        fclose($handle);

        $path      = rtrim($this->getConfigValue(self::PATH), '/');
        $putObject = (empty($path) ? '' : $path) . '/' . $filename;

        return $putObject;
    }

    public function putFileChunk($path, $handle)
    {
        if (is_string($handle)) {
            $handle    = fopen($handle, 'rb');
            $needClose = true;
        } elseif (!is_resource($handle)) {
            $this->_throwExeption($this->_helper->__('File "%s" must be a file-resource or a string', strval($handle)));
        }

        $sftp  = $this->getSftp();
        $chunk = $this->getChunkSize();

        $filename  = basename($path);
        $path      = $this->getConfigValue(self::PATH);
        $putObject = (empty($path) ? '' : $path) . '/' . $filename;

        $byte = $startByte = (float)$this->getRequestBytes();
        if ($byte > 0) {
            $this->fseek($handle, $byte);
        }

        while (!feof($handle)) {
            if ($this->timeIsUp()) {
                $this->setRequestBytes($byte);
                $nextChunk = true;
                break;
            }

            $content = fread($handle, $chunk);
            $sftp->put($filename, $content, NET_SFTP_RESUME);

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
        return $this->getSftp()->delete($path);
    }

    public function getTimeOut()
    {
        if ($this->_getData('time_out') === null) {
            if ($this->getBackup() instanceof Mageplace_Backup_Model_Backup && $this->getBackup()->isTimeLimitMultiStep()) {
                $this->setData('time_out', $this->getBackup()->getTimeLimit());
            } else {
                $timeOut = (int)$this->getConfigValue(self::TIMEOUT);
                $this->setData('time_out', $timeOut > 0 ? $timeOut : self::DEFAULT_TIMEOUT);
            }
        }

        return $this->_getData('time_out');
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
}
