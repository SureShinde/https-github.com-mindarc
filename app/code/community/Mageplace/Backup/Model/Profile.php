<?php
/**
 * Mageplace Magesocial
 *
 * @category   Mageplace
 * @package    Mageplace_Magesocial
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Profile
 *
 * @method string getProfileName
 * @method string|null getProfileLogLevel
 * @method int|null getProfileMultiprocessEnable
 * @method int|null getProfileRequestPeriod
 * @method int|null getProfileLocalCopy
 *
 * @method Mageplace_Backup_Model_Profile setProfileName
 * @method Mageplace_Backup_Model_Profile setProfileLogLevel
 * @method Mageplace_Backup_Model_Profile setProfileMultiprocessEnable
 * @method Mageplace_Backup_Model_Profile setProfileRequestPeriod
 * @method Mageplace_Backup_Model_Profile setProfileLocalCopy
 */
class Mageplace_Backup_Model_Profile extends Mage_Core_Model_Abstract
{
    const COLUMN_NAME                      = 'profile_name';
    const COLUMN_BACKUP_FILENAME_SUFFIX    = 'profile_backup_filename_suffix';
    const COLUMN_LOCAL_COPY                = 'profile_local_copy';
    const COLUMN_BACKUP_PATH               = 'profile_backup_path';
    const COLUMN_BACKUP_ERROR_DELETE_LOCAL = 'profile_backup_error_delete_local';
    const COLUMN_BACKUP_ERROR_DELETE_CLOUD = 'profile_backup_error_delete_cloud';
    const COLUMN_MULTIPROCESS_ENABLE       = 'profile_multiprocess_enable';
    const COLUMN_MULTIPROCESS_CRON_ENABLE  = 'profile_multiprocess_cron_enable';
    const COLUMN_MULTIPROCESS_TIME         = 'profile_multiprocess_time';
    const COLUMN_MULTIPROCESS_CRON_TIME    = 'profile_multiprocess_cron_time';
    const COLUMN_AUTH_ENABLE               = 'profile_auth_enable';
    const COLUMN_AUTH_USER                 = 'profile_auth_user';
    const COLUMN_AUTH_PASSWORD             = 'profile_auth_password';
    const COLUMN_DISABLE_CACHE             = 'profile_disable_cache';

    const TYPE_DBFILES = 'dbfiles';
    const TYPE_FILES   = 'files';
    const TYPE_DB      = 'db';

    const EXCLUDED_PATH   = 'excluded_path';
    const EXCLUDED_TABLES = 'excluded_tables';

    const CRON_ENABLE      = 'cron_enable';
    const CRON_BACKUP_EXPR = 'cron_backup_expr';
    const CRON_TIME_TYPE   = 'cron_time_type';
    const CRON_TIME        = 'cron_time';
    const CRON_FREQUENCY   = 'cron_time_frequency';

    const CRON_FAILURE_RUNNING         = 'cron_failure_running';
    const CRON_FAILURE_RUNNING_DEFAULT = 120;

    const CRON_SUCCESS_EMAIL           = 'cron_success_email';
    const CRON_SUCCESS_EMAIL_IDENTITY  = 'cron_success_email_identity';
    const CRON_SUCCESS_EMAIL_TEMPLATE  = 'cron_success_email_template';
    const CRON_SUCCESS_EMAIL_LOG_LEVEL = 'cron_success_email_log_level';

    const CRON_DELETE_TYPE                          = 'cron_delete_type';
    const CRON_DELETE_TYPE_ROTATION                 = 'rotation';
    const CRON_DELETE_TYPE_DELETE_OLD               = 'delete_old';
    const CRON_DELETE_TYPE_ROTATION_NUMBER          = 'cron_delete_type_rotation_number';
    const CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS = 'cron_delete_type_delete_older_than_x_days';
    const CRON_ROTATION_EXPR                        = 'cron_rotation_expr';

    const CRON_SUCCESS_DELETE_EMAIL          = 'cron_success_delete_email';
    const CRON_SUCCESS_DELETE_EMAIL_IDENTITY = 'cron_success_delete_email_identity';
    const CRON_SUCCESS_DELETE_EMAIL_TEMPLATE = 'cron_success_delete_email_template';

    const CRON_ERROR_EMAIL          = 'cron_error_email';
    const CRON_ERROR_EMAIL_IDENTITY = 'cron_error_email_identity';
    const CRON_ERROR_EMAIL_TEMPLATE = 'cron_error_email_template';

    const CRON_ERROR_DELETE_EMAIL          = 'cron_error_delete_email';
    const CRON_ERROR_DELETE_EMAIL_IDENTITY = 'cron_error_delete_email_identity';
    const CRON_ERROR_DELETE_EMAIL_TEMPLATE = 'cron_error_delete_email_template';

    /**
     * Constructor
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init('mpbackup/profile');
    }

    public function getName()
    {
        return $this->getProfileName();
    }

    public function getDefault()
    {
        $config_items = $this->getCollection()->addFilter('profile_default', 1)->getItems();

        return (empty($config_items) || !is_array($config_items) ? $this : array_pop($config_items));
    }

    public function getSessionProfileExcluded($sessionId = null)
    {
        if (is_null($sessionId)) {
            $sessionId = $this->getSessionId();
        }

        if ($sessionId) {
            $excluded = Mage::helper('mpbackup')->getSession(array($sessionId))->getProfilePath();
        } else {
            $excluded = Mage::helper('mpbackup')->getSession()->getProfilePath();
        }

        if (!is_array($excluded)) {
            $excluded = array();
        }

        return $excluded;
    }

    public function getExcludedPath()
    {
        $excluded = $this->_getData(self::EXCLUDED_PATH);
        if (!is_array($excluded)) {
            $excluded = array();
        }

        return $excluded;
    }

    public function getExcludedTables()
    {
        $excluded = $this->_getData(self::EXCLUDED_TABLES);
        if (!is_array($excluded)) {
            $excluded = array();
        }

        return $excluded;
    }
}