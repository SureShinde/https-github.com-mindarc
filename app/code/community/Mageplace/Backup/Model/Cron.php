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
 * Class Mageplace_Backup_Model_Cron
 */
class Mageplace_Backup_Model_Cron extends Mage_Core_Model_Abstract
{
	const REGISTER_KEY_CHECK_RUN = 'mpbackup_cron_run';

	const CACHE_KEY_LAST_SCHEDULE_GENERATE_AT = 'mpbackup_cron_last_schedule_generate_at';
	const CACHE_KEY_LAST_HISTORY_CLEANUP_AT   = 'mpbackup_cron_last_history_cleanup_at';

	const XML_CRON_EXPR_DELETE        = 'mpbackup/cron/expr_delete';
	const XML_CRON_EXPR_CHECK_RUNNING = 'mpbackup/cron/check_running';

	const JOB_CODE_BACKUP        = 'backup';
	const JOB_CODE_DELETE        = 'delete';
	const JOB_CODE_CHECK_RUNNING = 'check_running';

	protected static $JOBS = array(
		self::JOB_CODE_BACKUP,
		self::JOB_CODE_DELETE,
		self::JOB_CODE_CHECK_RUNNING,
	);


	/**
	 * Error messages
	 *
	 * @var array
	 */
	protected $_errors = array();
	protected $_pendingSchedules;

	/**
	 * Send backup success email
	 *
	 * @param Mageplace_Backup_Model_Profile $profile
	 * @param Mageplace_Backup_Model_Backup $backup
	 */
	public function sendSuccessEmail($profile, $backup)
	{
		Mage::helper('mpbackup/email')->sendSuccessEmail($profile, $backup);
	}

	/**
	 * Send Backup Errors
	 *
	 * @param Mageplace_Backup_Model_Profile $profile
	 *
	 * @return Mageplace_Backup_Model_Cron
	 */
	public function sendErrorsEmail($profile)
	{
		if (!$this->_errors) {
			return $this;
		}

		Mage::helper('mpbackup/email')->sendErrorsEmail($profile, $this->_errors);
	}

	/**
	 * Send backup success delete email
	 *
	 * @param Mageplace_Backup_Model_Profile $profile
	 * @param array $stat
	 */
	public function sendSuccessDeleteEmail($profile, $stat)
	{
		Mage::helper('mpbackup/email')->sendSuccessDeleteEmail($profile, $stat);
	}

	/**
	 * Send backup error delete email
	 *
	 * @param Mageplace_Backup_Model_Profile $profile
	 * @param array|string $stat
	 */
	public function sendErrorDeleteEmail($profile, $stat)
	{
		Mage::helper('mpbackup/email')->sendErrorDeleteEmail($profile, $stat);
	}

	public function generateJobs()
	{
		try {
			$this->_generateJobs(true);
		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	/**
	 * Clean logs
	 *
	 * @param Mage_Cron_Model_Schedule|null $backupSchedule
	 *
	 * @return $this
	 */
	public function run($backupSchedule = null)
	{
		#Mage::log('Single mode mask: ' . (int)$this->getData('single_mode'), null, 'cron.log');
		#Mage::log('Single mode enabled: ' . (int)Mage::helper('mpbackup/config')->isSingleModeEnabled(), null, 'cron.log');

		if (true !== $this->getData('single_mode') && Mage::helper('mpbackup/config')->isSingleModeEnabled()) {
			return $this;
		}

		try {
			$this->_generateJobs();
		} catch (Exception $e) {
			Mage::logException($e);
		}

		$this->_errors = array();

		$executedAt = $backupSchedule instanceof Mage_Cron_Model_Schedule ? strtotime($backupSchedule->getData('executed_at')) : time();

		$schedules        = $this->_getBackupPendingSchedules();
		$scheduleLifetime = Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_LIFETIME) * 60;
		/** @var Mageplace_Backup_Model_Cron_Schedule $schedule */
		foreach ($schedules->getIterator() as $schedule) {
			$time = strtotime($schedule->getData('scheduled_at'));
			if ($time > $executedAt) {
				continue;
			}

			$errorStatus = Mageplace_Backup_Helper_Const::STATUS_ERROR;

			try {
				if ($time < $executedAt - $scheduleLifetime) {
					$errorStatus = Mageplace_Backup_Helper_Const::STATUS_MISSED;
					throw Mage::exception('Mageplace_Backup',
						Mage::helper('cron')->__('Too late for the schedule.')
						. ($backupSchedule instanceof Mage_Cron_Model_Schedule ? ' Schedule ID#' . $backupSchedule->getId() . '.' : '')
						. ' Backup schedule ID#' . $schedule->getId() . '.');
				}

				if (!$schedule->tryJobLock()) {
					/* another cron started this job intermittently, so skip it */
					continue;
				}

				$schedule->setStatus(Mageplace_Backup_Helper_Const::STATUS_RUNNING)
					->setExecutedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
					->save();

				$profile_id = $schedule->getProfileId();
				if (!$profile_id) {
					continue;
				}

				$profile = Mage::getModel('mpbackup/profile')->load($profile_id);
				if (!is_object($profile) || !$profile->getId()) {
					throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Profile ID#%s not founded.', $profile_id));
				}

				$finish = true;
				switch ($schedule->getData('job_code')) {
					case self::JOB_CODE_BACKUP:
						$finish = $this->backupRun($profile, $schedule);
						break;

					case self::JOB_CODE_DELETE:
						$this->deleteOldBackups($profile);
						break;

					case self::JOB_CODE_CHECK_RUNNING:
						$this->checkRunningBackups($profile);
						break;
				}

				if ($finish) {
					$schedule->setStatus(Mageplace_Backup_Helper_Const::STATUS_SUCCESS)
						->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
				}

			} catch (Exception $e) {
				$schedule->setStatus($errorStatus)->setMessages($e->__toString());
				Mage::logException($e);
				Mage::log('MPBackup cron errors: ' . $e->getMessage());
				if ($errorStatus != Mageplace_Backup_Helper_Const::STATUS_MISSED) {
					$this->_errors[] = $e->__toString();
				}
			}

			$schedule->save();
		}

		if (isset($profile) && !empty($this->_errors)) {
			$this->sendErrorsEmail($profile);
		}

		$this->_cleanupJobs();

		return $this;
	}

	/**
	 * @param Mageplace_Backup_Model_Backup $backup
	 * @param bool $error
	 */
	public function finishSchedule(Mageplace_Backup_Model_Backup $backup, $error)
	{
		$schedule = $this->_getBackupSchedule($backup->getId());
		if ($schedule->getId()) {
			$schedule->setStatus($error ? Mageplace_Backup_Helper_Const::STATUS_ERROR : Mageplace_Backup_Helper_Const::STATUS_SUCCESS)
				->setMessages($backup->getBackupErrors())
				->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
				->save();
		}

		if ($error) {
			Mage::helper('mpbackup/email')->sendErrorsEmail($backup->getProfile(), $backup->getBackupErrors());
		} else {
			Mage::helper('mpbackup/email')->sendSuccessEmail($backup->getProfile(), $backup);
		}
	}

	/**
	 * @param Mageplace_Backup_Model_Profile $profile
	 * @param Mageplace_Backup_Model_Cron_Schedule $schedule
	 * @param bool $test
	 *
	 * @return bool
	 * @throws Exception
	 * @throws Zend_Http_Client_Exception
	 * @throws Mage_Core_Exception
	 * @throws Mageplace_Backup_Exception
	 */
	public function backupRun($profile, $schedule, $test = false)
	{
		$profileId = $profile->getId();
		if ($test) {
			$backupName        = Mage::helper('mpbackup')->__('TEST Backup - %s', Mage::app()->getLocale()->storeDate(null, null, true));
			$backupDescription = Mage::helper('mpbackup')->__('Current backup was automatically created by TEST script');
		} else {
			$backupName        = Mage::helper('mpbackup')->__('Backup - %s', Mage::app()->getLocale()->storeDate(null, null, true));
			$backupDescription = Mage::helper('mpbackup')->__('Current backup was automatically created by cron script');
		}

		$timeout = (int)$profile->getData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_TIME);

		Mage::setIsDeveloperMode(true);
		ini_set('display_errors', 1);

		/** @var $backupItem Mageplace_Backup_Model_Backup_Item */
		$backupItem = Mage::getModel('mpbackup/backup')
			->setProfile($profileId)
			->setBackupName($backupName)
			->setBackupFilename('cron_')
			->setBackupDescription($backupDescription)
			->setBackupCron(1)
			->initialize();

		if (!$secret = $backupItem->getSecret()) {
			throw Mage::exception('Mageplace_Backup', Mage::helper('mpbackup')->__('Backup secret code is wrong.'));
		}

		$schedule->setBackupId($backupItem->getBackup()->getId());

		$sid    = null;
		$error  = null;
		$params = $backupItem->toArray();

		if ($profile->getData(Mageplace_Backup_Model_Profile::COLUMN_MULTIPROCESS_CRON_ENABLE)) {
			try {
				Mage::helper('mpbackup')->request(Mage::helper('mpbackup/url')->getWrapperUrl(null, null, $profileId), $params, $profile, 1);
			} catch (Exception $e) {
				throw $e;
			}

			return false;
		}

		if (!@class_exists('Mageplace_Backup_BackupController')) {
			require_once 'Mageplace/Backup/controllers/BackupController.php';
		}

		do {
			try {
				Mage::app()->getRequest()->setParam(Mage_Core_Model_Session_Abstract::SESSION_ID_QUERY_PARAM, $sid);
				Mage::app()->getRequest()->setPost($params);
				$controller = new Mageplace_Backup_BackupController(
					Mage::app()->getRequest(),
					Mage::app()->getResponse()
				);

				$request  = $controller->getRequest();
				$response = $controller->getResponse();


				$step = $controller->backupAction(true);
			} catch (Exception $e) {
				Mage::logException($e);
				$error = $e->getMessage();
				break;
			}

			if (!$step instanceof Mageplace_Backup_Model_Backup_Step) {
				$error = strval($step);
				break;
			}

			$sid    = $step->getSid();
			$params = $step->toArray();

		} while (!$step->isFinished());

		$backup = Mage::getModel('mpbackup/backup')->loadBySecret($secret);

		if ($error !== null) {
			if (empty($error)) {
				$error = Mage::helper('mpbackup')->__('Empty error body');
			}

			try {
				if (!$backup->isFinished() || $backup->isSuccessFinished()) {
					$backup->finishBackupProcess($error);
				}
			} catch (Exception $e) {
				Mage::logException($e);
				$error .= PHP_EOL . $e->getMessage();
				$backup->criticalSave($error);
			}

			throw Mage::exception('Mageplace_Backup', $error);
		}

		$this->sendSuccessEmail($profile, $backup);

		return true;
	}

	public function deleteOldBackups($profile)
	{
		$backupsLifetime = (int)$profile->getData(Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLDER_THAN_X_DAYS);
		if ($backupsLifetime < 1) {
			return;
		}

		try {
			$ts = Mage::getSingleton('core/date')->gmtTimestamp() - $backupsLifetime * 24 * 60 * 60;

			$backups = $this->_getOldBackups($profile->getId(), date('Y-m-d H:i:s', $ts));
			$count   = $backups->count();
			if ($count) {
				$stat = array(
					Mageplace_Backup_Helper_Email::DELETE_STAT_DELETED => 0,
					Mageplace_Backup_Helper_Email::DELETE_STAT_BACKUPS => array(),
					Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS  => array(),
				);

				/** @var Mageplace_Backup_Model_Backup $backup */
				foreach ($backups as $backup) {
					$id   = $backup->getId();
					$name = $backup->getBackupName();

					$backup->deleteRecordAndFiles(false);

					if (!Mage::getModel('mpbackup/backup')->load($id)->getId()) {
						$stat[Mageplace_Backup_Helper_Email::DELETE_STAT_DELETED]++;
						$stat[Mageplace_Backup_Helper_Email::DELETE_STAT_BACKUPS][] = $name;
					}

					$stat[Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS] = array_merge($stat[Mageplace_Backup_Helper_Email::DELETE_STAT_ERRORS], $backup->getDeleteErrors());
				}

				$this->sendSuccessDeleteEmail($profile, $stat);
			}
		} catch (Exception $e) {
			Mage::logException($e);
			Mage::getModel('mpbackup/cron')->sendErrorDeleteEmail($profile, $e->getMessage());
		}
	}

	public function checkRunningBackups($profile)
	{
		$scedulesLifetime = $profile->getData(Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING);
		if (!$scedulesLifetime && $scedulesLifetime !== 0) {
			$scedulesLifetime = Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING_DEFAULT;
		}

		if ($scedulesLifetime < 1) {
			return;
		}

		$ts = Mage::getSingleton('core/date')->gmtTimestamp() - $scedulesLifetime * 60;

		$schedules = $this->_getFailureRunningShedules($profile->getId(), date('Y-m-d H:i:s', $ts));
		if ($schedules->count()) {
			/** @var Mageplace_Backup_Model_Backup $backup */
			foreach ($schedules as $schedule) {
				$message = Mage::helper('mpbackup')->__("Schedule has 'running' status for too long");
				if ($backupId = $schedule->getBackupId()) {
					$backup = Mage::getModel('mpbackup/backup')->load($backupId);
					if (is_object($backup) && $backup->getId()) {
						$backup->finishBackupProcess($message);
					}
				}

				$schedule->setStatus(Mageplace_Backup_Helper_Const::STATUS_ERROR)
					->setMessages($message)
					->save();
			}
		}
	}

	protected function _getBackupPendingSchedules()
	{
		if (is_null($this->_pendingSchedules)) {
			/** @var Mageplace_Backup_Model_Mysql4_Cron_Schedule_Collection $collection */
			$collection = Mage::getModel('mpbackup/cron_schedule')->getCollection();

			$this->_pendingSchedules = $collection->addPendingFilter()
				->addDirectionOrder()
				->load();
		}

		return $this->_pendingSchedules;
	}

	/**
	 * @param int $backupId
	 *
	 * @return Mageplace_Backup_Model_Cron_Schedule
	 */
	protected function _getBackupSchedule($backupId)
	{
		return Mage::getModel('mpbackup/cron_schedule')->getCollection()
			->addFilter('backup_id', $backupId)
			->addOrder('executed_at', 'DESC')
			->getFirstItem();
	}

	/**
	 * @param int $profileId
	 * @param datetime $date
	 *
	 * @return Mageplace_Backup_Model_Mysql4_Backup_Collection
	 */
	protected function _getOldBackups($profileId, $date)
	{
		return Mage::getModel('mpbackup/backup')->getCollection()
			->addFilter('profile_id', $profileId)
			->addFieldToFilter('backup_creation_date', array('to' => $date))
			->addFieldToFilter('backup_creation_date', array('neq' => '0000-00-00 00:00:00'))
			->addFieldToFilter('backup_creation_date', array('notnull' => true));
	}

	/**
	 * @param int $profileId
	 * @param datetime $date
	 *
	 * @return Mageplace_Backup_Model_Mysql4_Backup_Collection
	 */
	protected function _getFailureRunningShedules($profileId, $date)
	{
		return Mage::getModel('mpbackup/cron_schedule')->getCollection()
			->addFilter('profile_id', $profileId)
			->addFilter('status', Mageplace_Backup_Helper_Const::STATUS_RUNNING)
			->addFieldToFilter('executed_at', array('to' => $date))
			->addFieldToFilter('executed_at', array('neq' => '0000-00-00 00:00:00'))
			->addFieldToFilter('executed_at', array('notnull' => true));
	}

	protected function _generateJobs($skipCacheCheck = false)
	{
		$now = floor(time() / 60) * 60;

		if (!$skipCacheCheck) {
			$lastRun = Mage::app()->loadCache(self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT);
			if ($lastRun > $now - Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_GENERATE_EVERY) * 60) {
				return $this;
			}
		}

		$skipJobs = array();

		$pendingSchedules = $this->_getBackupPendingSchedules();

		/** @var Mageplace_Backup_Model_Cron_Schedule $pendingSchedule */
		foreach ($pendingSchedules->getIterator() as $pendingSchedule) {
			$skipJobs[$pendingSchedule->getProfileId() . '/' . $pendingSchedule->getJobCode() . '/' . $pendingSchedule->getScheduledAt()] = 1;
		}

		$profileIds = Mage::getModel('mpbackup/profile')->getCollection()->getAllIds();
		foreach ($profileIds as $profileId) {
			/** @var Mageplace_Backup_Model_Profile $profile */
			$profile = Mage::getModel('mpbackup/profile')->load($profileId);

			/** @var Mageplace_Backup_Model_Cron_Schedule $schedule */
			$schedule = Mage::getModel('mpbackup/cron_schedule');
			$schedule->setProfileId($profileId)
				->setStatus(Mageplace_Backup_Helper_Const::STATUS_PENDING);

			$scheduleAheadFor = Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_AHEAD_FOR) * 60;

			foreach (self::$JOBS as $jobCode) {
				$skip     = true;
				$cronExpr = null;
				switch ($jobCode) {
					case self::JOB_CODE_BACKUP:
						$cronEnable = $profile->getData(Mageplace_Backup_Model_Profile::CRON_ENABLE);
						$skip       = empty($cronEnable);
						$cronExpr   = $profile->getData(Mageplace_Backup_Model_Profile::CRON_BACKUP_EXPR);
						break;

					case self::JOB_CODE_DELETE:
						$skip     = $profile->getData(Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE) != Mageplace_Backup_Model_Profile::CRON_DELETE_TYPE_DELETE_OLD;
						$cronExpr = Mage::getStoreConfig(self::XML_CRON_EXPR_DELETE);
						break;

					case self::JOB_CODE_CHECK_RUNNING:
						$cronEnable = $profile->getData(Mageplace_Backup_Model_Profile::CRON_ENABLE);
						$skip       = empty($cronEnable) || $profile->getData(Mageplace_Backup_Model_Profile::CRON_FAILURE_RUNNING) == 0;
						$cronExpr   = Mage::getStoreConfig(self::XML_CRON_EXPR_CHECK_RUNNING);
						break;
				}

				if ($skip || !$cronExpr) {
					continue;
				}

				$schedule->setCronExpr($cronExpr)->setJobCode($jobCode);

				$timeAhead = $now + $scheduleAheadFor;
				for ($time = $now; $time < $timeAhead; $time += 60) {
					$ts = strftime('%Y-%m-%d %H:%M:00', $time);
					if (!empty($skipJobs[$profileId . '/' . $jobCode . '/' . $ts])) {
						// already scheduled
						continue;
					}

					if (!$schedule->trySchedule($time)) {
						// time does not match cron expression
						continue;
					}

					$schedule->unsCronId();
					$schedule->save();
				}
			}
		}

		Mage::app()->saveCache($now, self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT, array('crontab'), null);

		return $this;
	}

	public function _cleanupJobs()
	{
		$lastCleanup = Mage::app()->loadCache(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT);
		if ($lastCleanup > time() - Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_HISTORY_CLEANUP_EVERY) * 60) {
			return $this;
		}

		Mage::getModel('mpbackup/cron_schedule')->clean();

		Mage::app()->saveCache(time(), self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT, array('crontab'), null);

		return $this;
	}
}
