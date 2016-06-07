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
 * Class Mageplace_Backup_Helper_Email
 */
class Mageplace_Backup_Helper_Email extends Mage_Core_Helper_Abstract
{
    const DELETE_STAT_DELETED = 'deleted';
    const DELETE_STAT_ERRORS  = 'errors';
    const DELETE_STAT_BACKUPS = 'backups';

    /**
     * Send backup success email
     *
     * @param Mageplace_Backup_Model_Profile $profile
     * @param Mageplace_Backup_Model_Backup  $backup
     */
    public function sendSuccessEmail($profile, $backup)
    {
        try {
            if (!($profile instanceof Mageplace_Backup_Model_Profile)) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Profile object is wrong'));
            }

            if (!$profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL)) {
                Mage::log('MPBackup send success email not selected');
            } else {
                $logLevel = $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_LOG_LEVEL);
                $logs     = $backup->getLogs($logLevel);

                /* @var Mage_Core_Model_Translate $translate */
                $translate = Mage::getSingleton('core/translate');
                $translate->setTranslateInline(false);

                /* @var Mage_Core_Model_Email_Template $emailTemplate */
                $emailTemplate = Mage::getModel('core/email_template');
                $emailTemplate->setDesignConfig(array('area' => 'backend'))->sendTransactional(
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_TEMPLATE),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_IDENTITY),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL),
                    null,
                    array(
                        'profile_id'   => $profile->getId(),
                        'profile_name' => $profile->getProfileName(),
                        'backup_id'    => $backup->getId(),
                        'logs'         => join("\n", $logs)
                    )
                );

                $translate->setTranslateInline(true);
            }

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('MPBackup send success email has errors: ' . $e->getMessage());
        }
    }

    /**
     * Send backup errors email
     *
     * @param Mageplace_Backup_Model_Profile $profile
     * @param array|string                   $errors
     */
    public function sendErrorsEmail($profile, $errors)
    {
        try {
            if (!($profile instanceof Mageplace_Backup_Model_Profile)) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Profile object is wrong'));
            }

            if (empty($errors) || (!is_array($errors) && !is_string($errors))) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Errors list is empty'));
            }

            if (!$profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL)) {
                Mage::log('MPBackup send error email not selected');
            } else {
                /* @var Mage_Core_Model_Translate $translate */
                $translate = Mage::getSingleton('core/translate');
                $translate->setTranslateInline(false);

                /* @var Mage_Core_Model_Email_Template $emailTemplate */
                $emailTemplate = Mage::getModel('core/email_template');
                $emailTemplate->setDesignConfig(array('area' => 'backend'))->sendTransactional(
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_TEMPLATE),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_IDENTITY),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL),
                    null,
                    array(
                        'profile_id'   => $profile->getId(),
                        'profile_name' => $profile->getProfileName(),
                        'warnings'     => is_array($errors) ? join("\n", $errors) : $errors,
                    )
                );

                $translate->setTranslateInline(true);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('MPBackup send error email has errors: ' . $e->getMessage());
        }
    }

    /**
     * Send backup success delete email
     *
     * @param Mageplace_Backup_Model_Profile $profile
     * @param array                          $stat
     */
    public function sendSuccessDeleteEmail($profile, $stat)
    {
        try {
            if (!($profile instanceof Mageplace_Backup_Model_Profile)) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Profile object is wrong'));
            }

            if (!$profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL)) {
                Mage::log('MPBackup delete send success email not selected');
            } else {
                /* @var Mage_Core_Model_Translate $translate */
                $translate = Mage::getSingleton('core/translate');
                $translate->setTranslateInline(false);

                /* @var Mage_Core_Model_Email_Template $emailTemplate */
                $emailTemplate = Mage::getModel('core/email_template');
                $emailTemplate->setDesignConfig(array('area' => 'backend'))->sendTransactional(
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_TEMPLATE),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_IDENTITY),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL),
                    null,
                    array(
                        'profile_id'   => $profile->getId(),
                        'profile_name' => $profile->getProfileName(),
                        'backups'      => join("\n", $stat[self::DELETE_STAT_BACKUPS]),
                        'errors'       => !empty($stat[self::DELETE_STAT_ERRORS]) ? join("\n", $stat[self::DELETE_STAT_ERRORS]) : Mage::helper('mpbackup')->__('No errors'),
                    )
                );

                $translate->setTranslateInline(true);
            }

        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('MPBackup delete send success email has errors: ' . $e->getMessage());
        }
    }

    /**
     * Send backup success delete email
     *
     * @param Mageplace_Backup_Model_Profile $profile
     * @param array|string                   $errors
     */
    public function sendErrorDeleteEmail($profile, $errors)
    {
        try {
            if (!($profile instanceof Mageplace_Backup_Model_Profile)) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Profile object is wrong'));
            }

            if (empty($errors) || (!is_array($errors) && !is_string($errors))) {
                throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Errors list is empty'));
            }

            if (!$profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL)) {
                Mage::log('MPBackup send error email not selected');
            } else {
                /* @var Mage_Core_Model_Translate $translate */
                $translate = Mage::getSingleton('core/translate');
                $translate->setTranslateInline(false);

                /* @var Mage_Core_Model_Email_Template $emailTemplate */
                $emailTemplate = Mage::getModel('core/email_template');
                $emailTemplate->setDesignConfig(array('area' => 'backend'))->sendTransactional(
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_TEMPLATE),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_IDENTITY),
                    $profile->getData(Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL),
                    null,
                    array(
                        'profile_id'   => $profile->getId(),
                        'profile_name' => $profile->getProfileName(),
                        'warnings'     => is_array($errors) ? join("\n", $errors) : $errors,
                    )
                );

                $translate->setTranslateInline(true);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('MPBackup send error email has errors: ' . $e->getMessage());
        }
    }
}