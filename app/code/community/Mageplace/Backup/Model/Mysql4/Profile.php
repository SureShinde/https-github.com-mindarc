<?php
/**
 * Mageplace Backup
 *
 * @category   Mageplace
 * @package    Mageplace_Backup
 * @copyright  Copyright (c) 2014 Mageplace. (http://www.mageplace.com)
 * @license    http://www.mageplace.com/disclaimer.html
 */

/**
 * Class Mageplace_Backup_Model_Mysql4_Profile
 */
class Mageplace_Backup_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    const CRON_MODEL_PATH = 'crontab/jobs/mpbackup/run/model';

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('mpbackup/profile', 'profile_id');
    }

    /**
     * Sets the creation and update timestamps
     *
     * @param Mage_Core_Model_Abstract|Mageplace_Backup_Model_Profile $object Current profile
     *
     * @return Mageplace_Backup_Model_Mysql4_Profile
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $id = $object->getProfileId();
        if (!$id) {
            $object->setProfileCreationDate(Mage::getSingleton('core/date')->gmtDate());
        }
        $object->setProfileUpdateDate(Mage::getSingleton('core/date')->gmtDate());

        if ($profile_suffix = $object->getData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_FILENAME_SUFFIX)) {
            $profile_suffix = preg_replace('/[^a-z0-9\_\-]/i', '', $profile_suffix);
            $profile_suffix = substr($profile_suffix, 0, 10);
            $object->setData(Mageplace_Backup_Model_Profile::COLUMN_BACKUP_FILENAME_SUFFIX, $profile_suffix);
        }

        if (!$profile_backup_path = $object->getData('profile_backup_path')) {
            $object->setData('profile_backup_path', Mage::getBaseDir("var") . DS . Mageplace_Backup_Helper_Data::BACKUP_DIR);
        } else {
            $object->setData('profile_backup_path', trim(rtrim($profile_backup_path, DS)));
        }

        if (!$profile_log_path = $object->getData('profile_log_path')) {
            $object->setData('profile_log_path', Mage::getBaseDir("log"));
        } else {
            $object->setData('profile_log_path', trim(rtrim($profile_log_path, DS)));
        }

        $profile_multiprocess_time = (int)trim($object->getData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME));
        if ($profile_multiprocess_time <= 0) {
            $profile_multiprocess_time = 0;
        } elseif ($profile_multiprocess_time < Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME) {
            $profile_multiprocess_time = Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME;
        }
        $object->setData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_TIME, $profile_multiprocess_time);

        $profile_multiprocess_cron_time = (int)trim($object->getData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME));
        if ($profile_multiprocess_cron_time <= 0) {
            $profile_multiprocess_cron_time = 0;
        } elseif ($profile_multiprocess_cron_time < Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME) {
            $profile_multiprocess_cron_time = Mageplace_Backup_Model_Backup::MULTI_STEP_MIN_TIME;
        }
        $object->setData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME, $profile_multiprocess_cron_time);

        if ($object->getProfileDefault()) {
            $profile_collection = Mage::getModel('mpbackup/profile')->getCollection();
            /* @var $defaut_profiles Mageplace_Backup_Model_Mysql4_Profile_Collection */
            if ($id) {
                $profile_collection->addFieldToFilter('profile_id', array('neq' => $id));
            }
            $defaut_profiles = $profile_collection
                ->addFieldToFilter('profile_default', 1)
                ->getItems();
            foreach ($defaut_profiles as $profile) {
                $profile->setProfileDefault(0)->setSkipConfigSave(true)->save();
            }
        }

        return parent::_beforeSave($object);
    }

    /**
     * Save config options
     *
     * @param    Mage_Core_Model_Abstract $object
     *
     * @return    Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $profile_id = $object->getId();
        $sessionId  = $object->getSessionId();

        if ($sessionId) {
            $excluded = Mage::helper('mpbackup')->getSession(array($sessionId))->getProfilePath();
        } else {
            $excluded = Mage::helper('mpbackup')->getSession()->getProfilePath();
        }

        $excluded_json = json_encode($excluded);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, Mageplace_Backup_Model_Config::DEFAULT_PATH, Mageplace_Backup_Model_Profile::EXCLUDED_PATH, $excluded_json);

        if ($sessionId) {
            Mage::helper('mpbackup')->getSession(array($sessionId))->clearProfilePath();
        } else {
            Mage::helper('mpbackup')->getSession()->clearProfilePath();
        }

        $tables = $object->getData('mpbackuptable');
        if (is_array($tables)) {
            $tables_inline = implode(',', $tables);
        } else {
            $tables_inline = '';
        }

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, Mageplace_Backup_Model_Config::DEFAULT_PATH, Mageplace_Backup_Model_Profile::EXCLUDED_TABLES, $tables_inline);

        $CRON_ENABLE          = Mageplace_Backup_Model_Profile::CRON_ENABLE;
        $CONST_CRON_TIME_TYPE = Mageplace_Backup_Model_Profile::CRON_TIME_TYPE;
        $CRON_TIME            = Mageplace_Backup_Model_Profile::CRON_TIME;
        $CRON_TIME_TYPE       = $CRON_TIME . Mageplace_Backup_Block_Form_Element_Crontime::SUFFIX_TYPE;

        $CRON_HOURS     = $CRON_TIME . Mageplace_Backup_Block_Form_Element_Crontime::SUFFIX_HOURS;
        $CRON_MINUTES   = $CRON_TIME . Mageplace_Backup_Block_Form_Element_Crontime::SUFFIX_MINUTES;
        $CRON_FREQUENCY = $CRON_TIME . Mageplace_Backup_Block_Form_Element_Crontime::SUFFIX_FREQUENCY;

        $CONST_CRON_EXPR = Mageplace_Backup_Model_Profile::CRON_BACKUP_EXPR;
        $CRON_EXPR       = $CRON_TIME . Mageplace_Backup_Block_Form_Element_Crontime::SUFFIX_EXPRESSION;

        $CRON_FAILURE_RUNNING = Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING;

        $CRON_SUCCESS_EMAIL           = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL;
        $CRON_SUCCESS_EMAIL_IDENTITY  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_IDENTITY;
        $CRON_SUCCESS_EMAIL_TEMPLATE  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_TEMPLATE;
        $CRON_SUCCESS_EMAIL_LOG_LEVEL = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_LOG_LEVEL;

        $CRON_ERROR_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL;
        $CRON_ERROR_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_IDENTITY;
        $CRON_ERROR_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_TEMPLATE;

        $CRON_DELETE_TYPE                          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE;
        $CRON_DELETE_TYPE_ROTATION_NUMBER          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION_NUMBER;
        $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS;

        $CRON_SUCCESS_DELETE_EMAIL          = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL;
        $CRON_SUCCESS_DELETE_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_IDENTITY;
        $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_TEMPLATE;

        $CRON_ERROR_DELETE_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL;
        $CRON_ERROR_DELETE_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_IDENTITY;
        $CRON_ERROR_DELETE_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_TEMPLATE;

        $cron_enable    = $object->getData($CRON_ENABLE);
        $cron_time_type = $object->getData($CRON_TIME_TYPE);
        $cron_hours     = $object->getData($CRON_HOURS);
        $cron_minutes   = $object->getData($CRON_MINUTES);
        $cron_frequency = $object->getData($CRON_FREQUENCY);
        $cron_expr      = $object->getData($CRON_EXPR);

        $cron_failure_running = $object->getData($CRON_FAILURE_RUNNING);
        if (!$cron_failure_running) {
            $cron_failure_running = 0;
        }

        $cron_error_email          = $object->getData($CRON_ERROR_EMAIL);
        $cron_error_email_identity = $object->getData($CRON_ERROR_EMAIL_IDENTITY);
        $cron_error_email_template = $object->getData($CRON_ERROR_EMAIL_TEMPLATE);

        $cron_delete_type                          = $object->getData($CRON_DELETE_TYPE);
        $cron_delete_type_rotation_number          = (int)$object->getData($CRON_DELETE_TYPE_ROTATION_NUMBER);
        $cron_delete_type_delete_older_than_x_days = (int)$object->getData($CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS);

        $cron_success_email           = $object->getData($CRON_SUCCESS_EMAIL);
        $cron_success_email_identity  = $object->getData($CRON_SUCCESS_EMAIL_IDENTITY);
        $cron_success_email_template  = $object->getData($CRON_SUCCESS_EMAIL_TEMPLATE);
        $cron_success_email_log_level = $object->getData($CRON_SUCCESS_EMAIL_LOG_LEVEL);

        $cron_success_delete_email          = $object->getData($CRON_SUCCESS_DELETE_EMAIL);
        $cron_success_delete_email_identity = $object->getData($CRON_SUCCESS_DELETE_EMAIL_IDENTITY);
        $cron_success_delete_email_template = $object->getData($CRON_SUCCESS_DELETE_EMAIL_TEMPLATE);

        $cron_error_delete_email          = $object->getData($CRON_ERROR_DELETE_EMAIL);
        $cron_error_delete_email_identity = $object->getData($CRON_ERROR_DELETE_EMAIL_IDENTITY);
        $cron_error_delete_email_template = $object->getData($CRON_ERROR_DELETE_EMAIL_TEMPLATE);

        $CONST_CRON_PATH = Mageplace_Backup_Model_Config::CRON_PATH;

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ENABLE, $cron_enable);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CONST_CRON_TIME_TYPE, $cron_time_type);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_TIME, $cron_hours . ',' . $cron_minutes);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_FREQUENCY, $cron_frequency);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_FAILURE_RUNNING, $cron_failure_running);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL, $cron_success_email);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_IDENTITY, $cron_success_email_identity);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_TEMPLATE, $cron_success_email_template);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_LOG_LEVEL, $cron_success_email_log_level);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_DELETE_TYPE, $cron_delete_type);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_DELETE_TYPE_ROTATION_NUMBER, $cron_delete_type_rotation_number);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS, $cron_delete_type_delete_older_than_x_days);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL, $cron_success_delete_email);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL_IDENTITY, $cron_success_delete_email_identity);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE, $cron_success_delete_email_template);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_EMAIL, $cron_error_email);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_EMAIL_IDENTITY, $cron_error_email_identity);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_EMAIL_TEMPLATE, $cron_error_email_template);

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL, $cron_error_delete_email);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL_IDENTITY, $cron_error_delete_email_identity);
        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL_TEMPLATE, $cron_error_delete_email_template);


        $frequencyDaily   = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_DAILY;
        $frequencyWeekly  = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        $frequencyMonthly = Mage_Adminhtml_Model_System_Config_Source_Cron_Frequency::CRON_MONTHLY;

        if ($cron_enable) {
            if ($cron_time_type == Mageplace_Backup_Block_Form_Element_Crontime::TYPE_DEFAULT) {
                $cronExprArray = array(
                    intval($cron_minutes), # Minute
                    intval($cron_hours), # Hour
                    ($cron_frequency == $frequencyMonthly) ? '1' : '*', # Day of the Month
                    '*', # Month of the Year
                    ($cron_frequency == $frequencyWeekly) ? '1' : '*', # Day of the Week
                );

                $cronExprString = join(' ', $cronExprArray);
            } else {
                $e = preg_split('#\s+#', $cron_expr, null, PREG_SPLIT_NO_EMPTY);
                if (sizeof($e) < 5 || sizeof($e) > 6) {
                    $cron_expr = '';
                }
                $cronExprString = $cron_expr;
            }

        } else {
            $cronExprString = '';
        }

        Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $CONST_CRON_PATH, $CONST_CRON_EXPR, $cronExprString);


        if ($object->getSkipConfigSave()) {
            return $this;
        }

        if (!$profile_cloud_app = $object->getData('profile_cloud_app')) {
            return $this;
        }

        $settings = Mage::helper('mpbackup')->getAppConfig($profile_cloud_app);
        if (empty($settings['settings'])) {
            return $this;
        }

        $config_path = Mageplace_Backup_Model_Config::CLOUD_PATH . '/' . $profile_cloud_app;
        foreach ($settings['settings'] as $name => $config) {
            if (!empty($config['skip']) || (!empty($config['type']) && (strtolower($config['type']) == 'note'))) {
                continue;
            }

            $value = '';
            if ($post_data = $object->getData($profile_cloud_app . $name)) {
                $value = $post_data;
            }

            Mage::getModel('mpbackup/config')->saveConfigValue($profile_id, $config_path, $name, $value);
        }

        return parent::_afterSave($object);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $id = $object->getId();
        if (!$id) {
            return parent::_afterLoad($object);
        }

        $CONST_DEFAULT_PATH    = Mageplace_Backup_Model_Config::DEFAULT_PATH;
        $CONST_EXCLUDED_PATH   = Mageplace_Backup_Model_Profile::EXCLUDED_PATH;
        $CONST_EXCLUDED_TABLES = Mageplace_Backup_Model_Profile::EXCLUDED_TABLES;

        $excluded_path = Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_DEFAULT_PATH, $CONST_EXCLUDED_PATH);
        $excluded_path = json_decode($excluded_path);
        if (!is_array($excluded_path)) {
            $excluded_path = array();
        }
        $object->setData($CONST_EXCLUDED_PATH, $excluded_path);

        $excluded_tables = Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_DEFAULT_PATH, $CONST_EXCLUDED_TABLES);
        $excluded_tables = explode(',', $excluded_tables);
        if (!is_array($excluded_tables)) {
            $excluded_tables = array();
        }
        $object->setData($CONST_EXCLUDED_TABLES, $excluded_tables);


        $CONST_CRON_PATH      = Mageplace_Backup_Model_Config::CRON_PATH;
        $CONST_CRON_ENABLE    = Mageplace_Backup_Model_Profile::CRON_ENABLE;
        $CONST_CRON_TIME_TYPE = Mageplace_Backup_Model_Profile::CRON_TIME_TYPE;
        $CONST_CRON_TIME      = Mageplace_Backup_Model_Profile::CRON_TIME;
        $CONST_CRON_FREQUENCY = Mageplace_Backup_Model_Profile::CRON_FREQUENCY;
        $CONST_CRON_EXPR      = Mageplace_Backup_Model_Profile::CRON_BACKUP_EXPR;

        $CONST_CRON_FAILURE_RUNNING = Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING;

        $CRON_SUCCESS_EMAIL           = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL;
        $CRON_SUCCESS_EMAIL_IDENTITY  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_IDENTITY;
        $CRON_SUCCESS_EMAIL_TEMPLATE  = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_TEMPLATE;
        $CRON_SUCCESS_EMAIL_LOG_LEVEL = Mageplace_Backup_Model_Profile::CRON_SUCCESS_EMAIL_LOG_LEVEL;

        $CONST_CRON_ERROR_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL;
        $CONST_CRON_ERROR_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_IDENTITY;
        $CONST_CRON_ERROR_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_EMAIL_TEMPLATE;

        $CRON_DELETE_TYPE                          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE;
        $CRON_DELETE_TYPE_ROTATION_NUMBER          = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_ROTATION_NUMBER;
        $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS = Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS;

        $CRON_SUCCESS_DELETE_EMAIL          = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL;
        $CRON_SUCCESS_DELETE_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_IDENTITY;
        $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_SUCCESS_DELETE_EMAIL_TEMPLATE;

        $CRON_ERROR_DELETE_EMAIL          = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL;
        $CRON_ERROR_DELETE_EMAIL_IDENTITY = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_IDENTITY;
        $CRON_ERROR_DELETE_EMAIL_TEMPLATE = Mageplace_Backup_Model_Profile::CRON_ERROR_DELETE_EMAIL_TEMPLATE;

        $object->setData($CONST_CRON_ENABLE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_ENABLE));

        $object->setData($CONST_CRON_TIME_TYPE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_TIME_TYPE));

        $time = Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_TIME) . ' ' . Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_FREQUENCY);
        $object->setData($CONST_CRON_TIME, $time);

        $object->setData($CONST_CRON_FREQUENCY, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_FREQUENCY));
        $object->setData($CONST_CRON_EXPR, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_EXPR));
        $object->setData('cron', Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_EXPR));

        $object->setData($CONST_CRON_FAILURE_RUNNING, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_FAILURE_RUNNING));

        $object->setData($CRON_SUCCESS_EMAIL, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL));
        $object->setData($CRON_SUCCESS_EMAIL_IDENTITY, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_IDENTITY));
        $object->setData($CRON_SUCCESS_EMAIL_TEMPLATE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_TEMPLATE));
        $object->setData($CRON_SUCCESS_EMAIL_LOG_LEVEL, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_EMAIL_LOG_LEVEL));

        $object->setData($CONST_CRON_ERROR_EMAIL, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_ERROR_EMAIL));
        $object->setData($CONST_CRON_ERROR_EMAIL_IDENTITY, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_ERROR_EMAIL_IDENTITY));
        $object->setData($CONST_CRON_ERROR_EMAIL_TEMPLATE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CONST_CRON_ERROR_EMAIL_TEMPLATE));

        $object->setData($CRON_DELETE_TYPE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_DELETE_TYPE));
        $object->setData($CRON_DELETE_TYPE_ROTATION_NUMBER, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_DELETE_TYPE_ROTATION_NUMBER));
        $object->setData($CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS));

        $object->setData($CRON_SUCCESS_DELETE_EMAIL, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL));
        $object->setData($CRON_SUCCESS_DELETE_EMAIL_IDENTITY, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL_IDENTITY));
        $object->setData($CRON_SUCCESS_DELETE_EMAIL_TEMPLATE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_SUCCESS_DELETE_EMAIL_TEMPLATE));

        $object->setData($CRON_ERROR_DELETE_EMAIL, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL));
        $object->setData($CRON_ERROR_DELETE_EMAIL_IDENTITY, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL_IDENTITY));
        $object->setData($CRON_ERROR_DELETE_EMAIL_TEMPLATE, Mage::getModel('mpbackup/config')->getConfigValues($id, $CONST_CRON_PATH, $CRON_ERROR_DELETE_EMAIL_TEMPLATE));

        if (!$object->getData('profile_backup_path')) {
            $object->setData('profile_backup_path', Mage::getBaseDir("var") . DS . Mageplace_Backup_Helper_Data::BACKUP_DIR);
        }

        if (!$object->getData('profile_log_path')) {
            $object->setData('profile_log_path', Mage::getBaseDir("log"));
        }

        return parent::_afterLoad($object);
    }
}
