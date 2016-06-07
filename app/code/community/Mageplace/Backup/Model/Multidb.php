<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */
class Mageplace_Backup_Model_Multidb extends Mageplace_Backup_Model_File
{
	protected $_handle;
	
	protected function _construct()
	{
		$this->_baseDir = Mage::getBaseDir();
		$this->_helper = Mage::helper('mpbackup');
	}
	
	public function setFilename($filename)
	{
		if ($filename == '') {
			$filename = time();
			$this->setData('filename', $filename);
		} else {
			$this->setData('filename', $filename);
		}
		return $this;
	}
	
	public function getFilename($ext=null, $suffix=null)
	{
		return 'mp'.$this->getData('filename').'_'.$this->getData('type') . '.gz' . ($suffix ? $this->_helper->__("_part%s", $suffix) : '');
	}
	
	public function open($temp=true)
	{
		$filepath = $this->getFullName();
		$this->_handler = fopen($filepath, 'ab');
		return $this;
	}
	
	public function write($string)
	{
		if (is_null($this->_handler)) {
            Mage::exception('Mage_Backup', Mage::helper('backup')->__('Backup file handler was unspecified.'));
        }

        try {
            fwrite($this->_handler, $string);
        }
        catch (Exception $e) {
            Mage::exception('Mage_Backup', Mage::helper('backup')->__('An error occurred while writing to the backup file "%s".', $this->getFileName()));
        }

        return $this;
	}
	
	public function writeGz()
	{		
		$resource = gzopen($this->getFullName('.gz'), 'wb');
		gzwrite($resource, file_get_contents($this->getFullName()));		
		unlink($this->getFullName());		
	}
	
	protected function getFullName($ext = '')
	{
		return $this->getData('path') . DS . 'mp' . $this->getData('filename') .'_'.$this->getData('type'). $ext;
	}
	
	public function close()
	{
		fclose($this->_handler);
        $this->_handler = null;

        return $this;
	}
	
	public function getFileLocation()
	{
		return $this->getFullName('.gz');
	}
	/*public function getFileLocation()
	{
		return $this->getPath() . DS . $this->getFileName();
	}
	
	public function getMainFileName()
	{
		return $this->getData('filename');
	}*/
}