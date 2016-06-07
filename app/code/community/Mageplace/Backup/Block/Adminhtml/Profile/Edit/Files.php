<?php
/**
 * Mageplace Backup
 *
 * @category    Mageplace
 * @package     Mageplace_Backup
 * @copyright   Copyright (c) 2013 Mageplace. (http://www.mageplace.com)
 * @license     http://www.mageplace.com/disclaimer.html
 */

class Mageplace_Backup_Block_Adminhtml_Profile_Edit_Files extends Mage_Core_Block_Template
{
	protected $_dirs	= array();
	protected $_files	= array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setTemplate('mpbackup/files.phtml');
	}
	
	public function parseDir($dir=null)
	{
		if(!empty($this->_dirs) || !empty($this->_files)) {
			return $this;
		}
		
		if(is_null($dir)) {
			$dir = Mage::getBaseDir();
		} else {
			$dir = Mage::getBaseDir().$dir;
		}

		if(!is_readable($dir)) {
			return $this;
		}
		
		$root_dir = Mage::getBaseDir();
		
		$excluded = Mage::getModel('mpbackup/profile')->getSessionProfileExcluded($this->_getSessionId());		
		foreach(new DirectoryIterator($dir) as $diritem) {
			if($diritem->isDot()) {
				continue;
			}
			
			$filename = str_replace($root_dir, '', $diritem->getFilename());
			$pathname = str_replace($root_dir, '', $diritem->getPathname());
			if($diritem->isDir()) {
				$path = array(
					'filename'	=> $filename,
					'pathname'	=> $pathname,
					'checked'	=> in_array($pathname, $excluded),
				);
				$this->_dirs[] = $path;
			} else if($diritem->isFile()) {
				$file = array(
					'filename'	=> $filename,
					'pathname'	=> $pathname,
					'checked'	=> in_array($pathname, $excluded),
				);
				$this->_files[] = $file;
			}
		}
		
		usort($this->_dirs, array(&$this, 'sort')); 
		usort($this->_files, array(&$this, 'sort')); 
		
		return $this;
	}

	public function getDirs($dir=null)
	{
		if(is_null($dir)) {
			$dir = $this->getCurrentDir();
		}
		
		$this->parseDir($dir);
		
		return $this->_dirs;
	}

	public function getFiles($dir=null)
	{
		if(is_null($dir)) {
			$dir = $this->getCurrentDir();
		}
		
		$this->parseDir($dir);
		
		return $this->_files;
	}
	
	public function getDirSeparator()
	{
		static $separator = null;
		
		if(is_null($separator)) {
			$dir = $this->getCurrentDir();
			if(strpos($dir, '\\') !== FALSE) {
				$separator = '\\';
			} else if(strpos($dir, '/') !== FALSE) {
				$separator = '/';
			} else {
				$dir = $this->getBaseDir();
				if(strpos($dir, '\\') !== FALSE) {
					$separator = '\\';
				} else if(strpos($dir, '/') !== FALSE) {
					$separator = '/';
				}
			}
		}
		
		return $separator;
	}
	
	public function getCurrentDirArray()
	{
		$dir = $this->getCurrentDir();
		if(!$dir) {
			return array();
		}
		
		$separator = $this->getDirSeparator();
		$dirs = explode($separator, trim($dir, $separator));
		
		if(!is_array($dirs)) {
			return array();
		}
		
		return $dirs;
	}
	
	public function getExcludedItems()
	{
		return Mage::helper('mpbackup')->getSession(array($this->_getSessionId()))->getProfilePath();
	}
	
	public function getExcludedItemsSortedArray()
	{
		$forsort = Mage::helper('mpbackup')->getSession(array($this->_getSessionId()))->getProfilePath();
		natsort($forsort);
		return $forsort;
	}
	
	public function sort($a, $b)
	{
		$al = strtolower($a['filename']);
		$bl = strtolower($b['filename']);

		return strcmp($al, $bl);
	} 
        
	protected function _getProfileModel()
	{
		return Mage::registry('mpbackup_profile');
	}	
	
	protected function _getSessionId()
	{
		static $sessId;
		
		if(is_null($sessId)) {
			if($sessId = $this->getSessionId()) {
			} else if($this->_getProfileModel()) {
				$sessId = Mage::registry('mpbackup_profile')->getSessionId();			
			}	
		}
		
		return $sessId;
	}
}