<?php
require 'app/Mage.php';

if (!Mage::isInstalled()) {
	echo "Application is not installed yet, please complete install wizard first.";
	exit;
}

// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME']     = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

Mage::log('Cronbackup started', null, 'mpbackupcron.log');

try {
	Mage::getSingleton('mpbackup/cron')
		->setData('single_mode', true)
		->run();
} catch (Exception $e) {
	Mage::printException($e);
	exit(1);
}
