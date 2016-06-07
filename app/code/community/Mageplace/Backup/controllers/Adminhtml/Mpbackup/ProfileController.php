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
 * Class Mageplace_Backup_Adminhtml_Mpbackup_ProfileController
 */
class Mageplace_Backup_Adminhtml_Mpbackup_ProfileController extends Mage_Adminhtml_Controller_Action
{
    const MAX_LOOP = 20;

    protected $_loop = false;

    /**
     * Initialization of current view - add's breadcrumps and the current menu status
     *
     * @return Mageplace_Backup_Adminhtml_Mpbackup_ProfileController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('mpbackup/profile')
            ->_title($this->__('MagePlace Backup'));

        return $this;
    }

    /**
     * Displays the profiles overview grid.
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_title($this->__('Manage Profiles'))
            ->_addContent($this->getLayout()->createBlock('mpbackup/adminhtml_profile'))
            ->renderLayout();
    }

    /**
     * Displays the new profile form
     */
    public function newAction()
    {
        Mage::register('mpbackupBeforeForward', 'new');
        $this->_forward('edit');
    }

    /**
     * Displays the new profile form or the edit profile form.
     */
    public function editAction()
    {
        $id    = (int)$this->getRequest()->getParam('profile_id');
        $model = Mage::getModel('mpbackup/profile');
        /* @var $model Mageplace_Backup_Model_Profile */

        $tab = $this->getRequest()->getParam('tab');

        $storage = null;
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->_getSession()->addError($this->__('This profile does not exist'));
                $this->_redirect('*/*/index');

                return;
            }

            $storage = Mage::helper('mpbackup')->getCloudApplication($model);
        }

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
            $time = $model->getData(Mageplace_Backup_Model_Profile::CRON_TIME);
            if (is_array($time)) {
                $model->setData(Mageplace_Backup_Model_Profile::CRON_TIME, implode(',', $time));
            }
        }

        $sessId = $id . '_' . Mage::helper('core')->getRandomString(16);
        $model->setSessionId($sessId);
        Mage::helper('mpbackup')->getSession(array($sessId))->initProfilePath($model);
        Mage::register('mpbackup_profile', $model);

        Mage::register('mpbackup_storage', $storage);

        $title = $id ? $this->__('Edit Profile') : $this->__('New Profile');
        $this->_initAction()
            ->_title($title)
            ->_addBreadcrumb($title, $title)
            ->_addContent($this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit', 'mpbackup_adminhtml_profile_edit'))
            ->_addLeft($this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_tabs'))
            ->renderLayout();
    }

    /**
     * Action that does the actual saving process and redirects back to overview
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (!array_key_exists('profile_local_copy', $data)) {
                $data['profile_local_copy'] = 1;
            }

            $model = Mage::getModel('mpbackup/profile');
            /* @var $model Mageplace_Backup_Model_Profile */
            $model->setData($data);

            try {
                $model->save();

                if (!file_exists($model->profile_backup_path) && !@mkdir($model->profile_backup_path)) {
                    $this->_getSession()->addError($this->__("Can't create backup directory"));
                } elseif (file_exists($model->profile_backup_path) && !is_writable($model->profile_backup_path)) {
                    $this->_getSession()->addError($this->__("Backup directory is not writable"));
                }

                if (!file_exists($model->profile_log_path) && !@mkdir($model->profile_log_path)) {
                    $this->_getSession()->addError($this->__("Can't create log directory"));
                } elseif (file_exists($model->profile_log_path) && !is_writable($model->profile_log_path)) {
                    $this->_getSession()->addError($this->__("Log directory is not writable"));
                }

                $this->_getSession()->addSuccess($this->__('Profile was successfully saved'));
                $this->_getSession()->setFormData(false);
                $profile_id = $model->getId();
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array(
                        'profile_id' => $profile_id,
                        '_current'   => true
                    ));

                    return;
                } else {
                    if ($this->getRequest()->getParam('authorize')) {
                        /* Session must be initiated before resetAuthData !!! */
                        $session = Mage::helper('mpbackup')->getSession($profile_id, true);

                        $cloud_storage = Mage::helper('mpbackup')->getCloudApplication($model);
                        if (!is_object($cloud_storage)) {
                            $this->_getSession()->addError($this->__("Can't authorize application. Check storage application settings."));
                            $this->_redirect('*/*/edit', array('profile_id' => $profile_id));

                            return;
                        }

                        $cloud_storage->resetAuthData();

                        $session->setCloudStorage(serialize($cloud_storage));
                        $session->setRedirect($this->getUrl('*/*/edit', array('profile_id' => $profile_id)));

                        Mage::register('mpbackup_cloud_storage', $cloud_storage);

                        if ($cloud_storage->needAuthorize()) {
                            $this->_forward('auth', 'mpbackup_backup');

                            return;
                        }
                    }
                }
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $e->getMessage());
                $this->_getSession()->setFormData($data);
                $this->_redirect('*/*/edit', array('profile_id' => $this->getRequest()->getParam('profile_id')));

                return;
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Action that does the actual delete process and redirects back to overview
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('profile_id')) {
            try {
                $model = Mage::getModel('mpbackup/profile');
                $model->load($id);
                $model->delete();

                $this->_getSession()->addSuccess($this->__('Profile was successfully deleted'));
                $this->_redirect('*/*/index');

                return;

            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('profile_id' => $id));

                return;
            }
        }

        $this->_getSession()->addError($this->__('Unable to find a Profile to delete'));

        $this->_redirect('*/*/index');
    }

    /**
     * Ajax action for get application settings
     */
    public function loadSettingsAction()
    {
        try {
            $block = $this->getLayout()
                ->createBlock(
                    'mpbackup/adminhtml_settings',
                    '',
                    array(
                        'profile_id' => $this->getRequest()->getParam('profile_id'),
                        'cloud_app'  => $this->getRequest()->getParam('profile_cloud_app'),
                    )
                );

            $this->getResponse()->setBody($block->toHtml());
        } catch (Exception $e) {
            Mage::logException($e);
            echo '<p style="color:red; font-weight:bold;">' . $e->getMessage() . '</p>';
        }
    }

    /**
     * Ajax action for add/delete path to list of excluded paths
     */
    public function excludePathAction()
    {
        $all       = (bool)$this->getRequest()->getParam('all');
        $sessionId = $this->getRequest()->getParam('session_id');
        if ($all) {
            Mage::helper('mpbackup')->getSession(array($sessionId))->clearProfilePath();

            return true;
        }

        $path  = $this->getRequest()->getParam('path');
        $isAdd = (bool)$this->getRequest()->getParam('checked');

        Mage::helper('mpbackup')->getSession(array($sessionId))->addProfilePath($path, $isAdd);
    }

    /**
     * Ajax action for add/delete path to list of excluded paths
     */
    public function excludeAllAction()
    {
        $paths     = explode('`', $this->getRequest()->getParam('paths'));
        $sessionId = $this->getRequest()->getParam('session_id');
        if (!is_array($paths)) {
            return false;
        }

        foreach ($paths as $path) {
            Mage::helper('mpbackup')->getSession(array($sessionId))->addProfilePath($path, 1);
        }
    }

    /**
     * Ajax action for get paths list
     */
    public function pathAction()
    {
        $sessionId = $this->getRequest()->getParam('session_id');
        $path      = $this->getRequest()->getParam('path');
        $up        = $this->getRequest()->getParam('up');
        if ($up) {
            $path = dirname($path);
        }

        echo $this->getLayout()->createBlock('mpbackup/adminhtml_profile_edit_files')->setSessionId($sessionId)->setCurrentDir($path)->toHtml();
    }

    public function getSizeAction()
    {
        $baseDir = Mage::getBaseDir();

        $dirs = array();
        if ($this->getRequest()->getParam('dirs')) {
            $dirsNames = explode('`', $this->getRequest()->getParam('dirs'));
            if (is_array($dirsNames)) {
                foreach ($dirsNames as $dirsName) {
                    $size = $this->getDirSize($baseDir . $dirsName);
                    if (!is_null($size)) {
                        $size = $this->_getBytes($size);
                        if ($this->_loop) {
                            $dirs[$dirsName] = "Bigger then " . $size;
                            $this->_loop     = false;
                        } else {
                            $dirs[$dirsName] = $size;
                        }
                    } else {
                        $dirs[$dirsName] = '---';
                    }
                }
            }
        }

        $files = array();
        if ($this->getRequest()->getParam('files')) {
            $fileNames = explode('`', $this->getRequest()->getParam('files'));
            if (is_array($fileNames)) {
                foreach ($fileNames as $fileName) {
                    $files[$fileName] = $this->_getBytes(Mageplace_Backup_Model_File::filesize($baseDir . $fileName));
                }
            }
        }

        echo Zend_Json::encode(array('dirs' => $dirs, 'files' => $files));
    }

    public function getfilesizeAction()
    {
        $fileName = $this->getRequest()->getParam('filepath');
        echo $this->_getBytes(Mageplace_Backup_Model_File::filesize($fileName));
        die();
    }

    public function getdirsizeAction()
    {
        $path   = $this->getRequest()->getParam('path');
        $result = $this->getDirSize($path);

        if (!is_null($result)) {
            echo $this->_getBytes($result);
        } else {
            echo '---';
        }

        die();
    }

    public function getDirSize($path, $loop = 0)
    {
        $fileSize = 0;
        $dir      = scandir($path);

        foreach ($dir as $file) {
            if (($file != '.') && ($file != '..')) {
                if (is_link($path . DS . $file)) {
                    continue;
                } elseif (is_dir($path . DS . $file)) {
                    if ($loop >= self::MAX_LOOP) {
                        $this->_loop = true;
                        continue;
                    } else {
                        $fileSize += (float)$this->getDirSize($path . DS . $file, $loop + 1);
                    }
                } else {
                    $fileSize += Mageplace_Backup_Model_File::filesize($path . DS . $file);
                }
            }
        }

        return $fileSize;
    }

    public function gettablesizeAction()
    {
        $tname  = $this->getRequest()->getParam('tname');
        $tnames = $this->getRequest()->getParam('tnames');

        if ($tname) {
            echo $this->_getTableSize($tname);
        } else {
            if ($tnames) {
                $tnames = explode('`', $tnames);
                if (is_array($tnames)) {
                    $sizes = array();
                    foreach ($tnames as $tname) {
                        $sizes[$tname] = $this->_getTableSize($tname);
                    }

                    echo Zend_Json::encode($sizes);
                }
            }
        }

        die();
    }

    protected function _getTableSize($table)
    {
        $db     = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $db->query("SHOW TABLE STATUS LIKE '" . $table . "'");
        $row    = $result->fetch(PDO::FETCH_ASSOC);
        $size   = (float)$row['Data_length'] + $row['Index_length'];

        return $this->_getBytes($size);
    }

    protected function _getBytes($size)
    {
        $size = (float)$size;

        if ($size >= 1024 * 1024 * 1024) {
            $size = number_format($size / (1024 * 1024 * 1024), 2) . ' Gb';
        } else {
            if ($size >= 1024 * 1024) {
                $size = number_format(($size / (1024 * 1024)), 2) . ' Mb';
            } else {
                if ($size >= 1024) {
                    $size = number_format(($size / 1024), 2) . ' Kb';
                } else {
                    $size = $size . ' b';
                }
            }
        }

        return $size;
    }

    /**
     * Simple access control
     *
     * @return boolean True if user is allowed to create/edit/delete profile
     */
    protected function _isAllowed()
    {
        $action = (Mage::registry('mpbackupBeforeForward') ? Mage::registry('mpbackupBeforeForward') : $this->getRequest()->getActionName());

        $profileEditActions = array(
            'loadSettings',
            'excludePath',
            'excludeAll',
            'path',
            'getSize',
            'getfilesize',
            'getdirsize',
            'gettablesize',
            'save'
        );
        if (in_array($action, $profileEditActions)) {
            return Mage::getSingleton('admin/session')->isAllowed('admin/mpbackup/profile/profile_new')
            || Mage::getSingleton('admin/session')->isAllowed('admin/mpbackup/profile/profile_edit');
        }

        if ($action && ($action != 'index')) {
            return Mage::getSingleton('admin/session')->isAllowed('admin/mpbackup/profile/profile_' . $action);
        }

        return Mage::getSingleton('admin/session')->isAllowed('admin/mpbackup/profile');
    }
}
