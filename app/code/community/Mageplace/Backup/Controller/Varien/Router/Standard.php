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
 * Class Mageplace_Backup_Controller_Varien_Router_Standard
 */
class Mageplace_Backup_Controller_Varien_Router_Standard extends Mage_Core_Controller_Varien_Router_Standard
{
    const CONTROLLER_BACKUP   = 'backup';
    const CONTROLLER_PROGRESS = 'progress';

    protected static $ALLOWED_ACTIONS = array(
        self::CONTROLLER_BACKUP   => array('backup', 'finish', 'cancel', 'checkMemoryLimit', 'wrapper'),
        self::CONTROLLER_PROGRESS => array('start', 'stage'),
    );

    public function match(Zend_Controller_Request_Http $request)
    {
        $path = explode('/', trim($request->getPathInfo(), '/'));
        if ($path[0] != Mageplace_Backup_Helper_Const::NAME
            || !array_key_exists($path[1], self::$ALLOWED_ACTIONS)
            || !in_array($path[2], self::$ALLOWED_ACTIONS[$path[1]])
        ) {
            return parent::match($request);
        }

        if (!$this->isOwnOriginUrl($request)) {
            Mage::log('MPBACKUP WRONG OWN ORIGIN URL');

            return false;
        }

        Mage::setIsDeveloperMode(true);
		@error_reporting(E_ALL ^ E_NOTICE);

        require_once 'Mageplace/Backup/controllers/' . ucfirst($path[1]) . 'Controller.php';

        $controllerClassName = 'Mageplace_Backup_' . ucfirst($path[1]) . 'Controller';

        /** @var Mageplace_Backup_BackupController|Mageplace_Backup_ProgressController $controllerInstance */
        $controllerInstance = Mage::getControllerInstance(
            $controllerClassName,
            $request,
            $this->getFront()->getResponse()
        );

        $request->setDispatched(true);

        Mage::getSingleton('mpbackup/session',
            array(
                'sid'  => $request->getParam(Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM),
                'name' => $controllerInstance->getSessionNamespace() . '_' . $path[1]
            )
        )->start();

        $actionMethodName = $controllerInstance->getActionMethodName($path[2]);
        $controllerInstance->$actionMethodName();

        return true;
    }
	
    public function isOwnOriginUrl($request)
    {
        $storeDomains = array();
        $referer = parse_url($request->getServer('HTTP_REFERER'), PHP_URL_HOST);
        foreach (Mage::app()->getStores() as $store) {
            $storeDomains[] = parse_url($store->getBaseUrl(), PHP_URL_HOST);
            $storeDomains[] = parse_url($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true), PHP_URL_HOST);
        }
        $storeDomains = array_unique($storeDomains);
        if (empty($referer) || in_array($referer, $storeDomains)) {
            return true;
        }
        return false;
    }
}