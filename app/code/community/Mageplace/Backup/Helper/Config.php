<?php

/**
 * Class Mageplace_Backup_Helper_Config
 */
class Mageplace_Backup_Helper_Config extends Mage_Core_Helper_Abstract
{
	const XML_PATH_MODULE_PREFIX = 'mpbackup';

	const XML_PATH_GENERAL_SINGLE_MODE = 'general/cron_single_mode';

	public function isSingleModeEnabled()
	{
		return $this->getConfig(self::XML_PATH_GENERAL_SINGLE_MODE, true);
	}

	public function getConfig($path, $flag = false)
	{
		$xmlPath = self::XML_PATH_MODULE_PREFIX . '/' . $path;

		if ($flag) {
			return Mage::getStoreConfigFlag($xmlPath);
		}

		return Mage::getStoreConfig($xmlPath);
	}
}