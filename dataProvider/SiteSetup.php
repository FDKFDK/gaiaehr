<?php
if(!isset($_SESSION)) {
	session_name('GaiaEHR');
	session_start();
	session_cache_limiter('private');
}
include_once($_SESSION['site']['root'] . '/classes/FileManager.php');
include_once($_SESSION['site']['root'] . '/dataProvider/ACL.php');
set_time_limit(0);
ini_set('memory_limit', '512M');
class SiteSetup
{
	private $conn;
	private $err;
	private $sitesDir = 'sites';
	private $dbPrefix;
	private $AESkey;
	private $newConf;
	var $siteName;
	var $connType;
	var $dbUser;
	var $dbPass;
	var $dbHost;
	var $dbPort;
	var $dbName;
	var $rootUser;
	var $rootPass;
	var $adminUser;
	var $adminPass;

	function __construct()
	{
		chdir($_SESSION['site']['root']);
	}

	public function checkDatabaseCredentials(stdClass $params)
	{
		if(isset($params->rootUser)) {
			$success = $this->rootDatabaseConn($params->dbHost, $params->dbPort, $params->rootUser, $params->rootPass);
			if($success && $this->conn->query("USE $params->dbName") !== false){
				return array('success' => false, 'error' => 'Database name in used. Please, use a different Database name');
			}
		} else {
			$success = $this->databaseConn($params->dbHost, $params->dbPort, $params->dbName, $params->dbUser, $params->dbPass);
		}
		if($success){
			return array('success' => true, 'dbInfo' => $params);
		}else{
			return array('success' => false, 'error' => 'Could not connect to sql server!<br>Please, check database information and try again');
		}
	}

	function databaseConn($host, $port, $dbName, $dbUser, $dbPass)
	{
		try {
			$this->conn = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $dbUser, $dbPass,
				array(PDO::MYSQL_ATTR_LOCAL_INFILE => 1, PDO::ATTR_PERSISTENT => true));
			return true;
		} catch(PDOException $e) {
			$this->err = $e->getMessage();
			return false;
		}
	}

	public function rootDatabaseConn($host, $port, $rootUser, $rootPass)
	{
		try {
			$this->conn = new PDO("mysql:host=$host;port=$port", $rootUser, $rootPass);
			return true;
		} catch(PDOException $e) {
			$this->err = $e->getMessage();
			return false;
		}
	}

	public function checkRequirements()
	{
		$row = array();
		// check if ...
		$status = (empty($_SESSION['site']['sites']) ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'GaiaEHR is not installed', 'status'=> $status);

		// verified that php 5.2.0 or later is installed
		$status = (version_compare(phpversion(), "5.3.2", ">=") ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'PHP 5.3.2 + installed', 'status'=> $status);

		// Check if get_magic_quotes_gpc is off
		$status = (get_magic_quotes_gpc() != 1 ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'get_magic_quotes_gpc off/disabled', 'status'=> $status);

		// try chmod sites folder and check chmod after that
		chmod('sites', 777);
		$status = (substr(sprintf('%o', fileperms('sites')), -4) ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'Sites folder is writable', 'status'=> $status);

		// check if safe_mode is off
		$status = (!ini_get('safe_mode') ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'PHP safe mode off', 'status'=> $status);

		// check if ZipArchive is enable
		$status = (class_exists('ZipArchive') ? 'Ok' : 'Fail');
		$row[] = array('msg'=> 'PHP class ZipArchive', 'status'=> $status);

		return $row;
	}


	public function setSiteDirBySiteId($siteId){
		$siteDir = "sites/$siteId";
		if(!file_exists($siteDir)){
			if(mkdir($siteDir, 0777, true)){
				if(chmod($siteDir, 0777)){
					if(
						(mkdir("$siteDir/patients", 0777, true) && chmod("$siteDir/patients", 0777)) &&
						(mkdir("$siteDir/documents", 0777, true) && chmod("$siteDir/documents", 0777)) &&
						(mkdir("$siteDir/temp", 0777, true) && chmod("$siteDir/temp", 0777)) &&
						(mkdir("$siteDir/trash", 0777, true) && chmod("$siteDir/trash", 0777))
					){
						return array('success' => true);
					}else{
						return array('success' => false, 'error' => 'Something went wrong creating site sub directories');
					}
				}else{
					return array('success' => false, 'error' => 'Unable to set "/sites/'.$siteId.'" write permissions,<br>Please, check "/sites/'.$siteId.'" directory write permissions');
				}
			}else{
				return array('success' => false, 'error' => 'Unable to create Site directory,<br>Please, check "/sites" directory write permissions');
			}
		}else{
			return array('success' => false, 'error' => 'Site ID already in use.<br>Please, choose another Site ID');
		}
	}


	public function createDatabaseStructure(stdClass $params){
		if(isset($params->rootUser) && $this->rootDatabaseConn($params->dbHost, $params->dbPort, $params->rootUser, $params->rootPass)){
		    $this->conn->exec("CREATE DATABASE $params->dbName;");
		    $this->conn->exec("CREATE USER '$params->dbUser'@'$params->dbHost' IDENTIFIED BY '$params->dbPass'");
		    $this->conn->exec("GRANT USAGE ON * . * TO  '$params->dbUser'@'$params->dbHost' IDENTIFIED BY  $params->dbPass' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0");
		    $this->conn->exec("GRANT ALL PRIVILEGES ON $params->dbName.* TO '$params->dbUser'@'$params->dbHost' WITH GRANT OPTION");
			if($this->databaseConn($params->dbHost, $params->dbPort, $params->dbName, $params->dbUser, $params->dbPass)){
				if($this->loadDatabaseStructure()){
					return array('success' => true);
				}else{
					FileManager::rmdir_recursive("sites/$params->siteId");
					return array('success' => false, 'error' => $this->conn->errorInfo());
				}
			}else{
				FileManager::rmdir_recursive("sites/$params->siteId");
				return array('success' => false, 'error' => $this->err);
			}
		}elseif($this->databaseConn($params->dbHost, $params->dbPort, $params->dbName, $params->dbUser, $params->dbPass)){
			if($this->loadDatabaseStructure()){
				return array('success' => true);
			}else{
				FileManager::rmdir_recursive("sites/$params->siteId");
				return array('success' => false, 'error' => $this->conn->errorInfo());
			}
		}else{
			FileManager::rmdir_recursive("sites/$params->siteId");
			return array('success' => false, 'error' => $this->err);
		}
	}

	public function loadDatabaseStructure(){
		if(file_exists($sqlFile = 'sql/gaiadb_install_structure.sql')) {
			$query = file_get_contents($sqlFile);
			if($this->conn->query($query) !== false){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function loadDatabaseData(stdClass $params){
		if($this->databaseConn($params->dbHost, $params->dbPort, $params->dbName, $params->dbUser, $params->dbPass)){
			if(file_exists($sqlFile = 'sql/gaiadb_install_data.sql')) {
				$query = file_get_contents($sqlFile);
				if($this->conn->query($query) !== false){
					return array('success' => true);
				}else{
					FileManager::rmdir_recursive("sites/$params->siteId");
					if(isset($params->rootUser)) $this->dropDatabase($params->dbName);
					return array('success' => false, 'error' => $this->conn->errorInfo());
				}
			}else{
				FileManager::rmdir_recursive("sites/$params->siteId");
				if(isset($params->rootUser)) $this->dropDatabase($params->dbName);
				return array('success' => false, 'error' => 'Unable find installation data file');
			}
		}else{
			FileManager::rmdir_recursive("sites/$params->siteId");
			return array('success' => false, 'error' => $this->err);
		}
	}

	function dropDatabase($dbName)
	{
		$this->conn->exec("DROP DATABASE $dbName");
	}


	public function createSConfigurationFile($params){
		if(file_exists($conf = 'sites/conf.example.php')) {
			if(($AESkey = ACL::createRandomKey()) !== false){
				$buffer     = file_get_contents($conf);
				$search     = array('%host%', '%user%', '%pass%', '%db%', '%port%', '%key%', '%lang%');
				$replace    = array($params->dbHost, $params->dbUser, $params->dbPass, $params->dbName, $params->dbPort, $AESkey, 'en_US');
				$newConf    = str_replace($search, $replace, $buffer);
				$siteDir = "sites/$params->siteId";
				$conf_file = ("$siteDir/conf.php");
				$handle    = fopen($conf_file, 'w');
				fwrite($handle, $newConf);
				fclose($handle);
				chmod($conf_file, 0644);

				if(file_exists($conf_file)) {
					return array('success' => true , 'AESkey' => $AESkey);
				}else{
					return array('success' => false, 'error' => "Unable to create $siteDir/conf.php file");
				}
			}else{
				return array('success' => false, 'error' => 'Unable to Generate AES 32 bit key');
			}
		} else {
			return array('success' => false, 'error' => 'Unable to Find sites/conf.example.php');
		}
	}

	public function createSiteAdmin($params){
		include_once('sites/'.$params->siteId.'/conf.php');
		include_once('dataProvider/User.php');
		$u = new User();
		$userParams = new stdClass();
		$userParams->title = 'Mr.';
		$userParams->fname = 'Administrator';
		$userParams->lname = 'Administrator';
		$userParams->username = $params->adminUsername;
		$userParams->password = $params->adminPassword;
		$userParams->authorized = 1;
		$userParams->active = 1;
		$userParams->role_id = 1;
		$u->addUser($userParams);
		return array('success' => true);
	}


	public function loadICD9Codes(){
		sleep(4);
		return array('success' => true);
	}


	public function loadICD10Codes(){
		sleep(7);
		return array('success' => true);
	}


	public function loadSNOMEDCodes(){
		sleep(20);
		return array('success' => true);
	}


	public function loadRxNormCodes(){
		sleep(10);
		return array('success' => true);
	}

//
//
//
//
//
//
//
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//	//*****************************************************************************************
//


//
//	//*****************************************************************************************
//	// Create Site Conf File
//	//*****************************************************************************************
//	function createSiteConf()
//	{
//		$workingDir = $this->sitesDir . "/" . $this->siteName;
//		mkdir($workingDir, 0777, true);
//		chmod($workingDir, 0777);
//		$conf_file = ($workingDir . "/conf.php");
//		$handle    = fopen($conf_file, "w");
//		fwrite($handle, $this->newConf);
//		fclose($handle);
//		chmod($conf_file, 0644);
//		if(!file_exists($conf_file)) {
//			echo '{"success":false,"msg":"Error - The conf.php file for <b>' . $this->siteName . '</b> could not be created."}';
//			exit;
//		}
//		return;
//	}
//
//	//*****************************************************************************************
//	// Create Admin User and AES Encrypted Password Using Site AESkey
//	//*****************************************************************************************
//	function adminUser()
//	{
//		require_once("classes/AES.php");
//		$admin = $this->adminUser;
//		$aes   = new AES($this->AESkey);
//		$ePass = $aes->encrypt($this->adminPass);
//		$this->conn->exec("INSERT INTO users
//							  	   SET username 	='" . $admin . "',
//							  	       fname		='Adminstrator',
//							  	  	   password 	='" . $ePass . "',
//							  	       authorized 	='1'");
//		return $this->displayError();
//	}
//
//	function installationCk()
//	{
//		if(!$this->displayError()) {
//			$error = array('success' => true, 'msg' => 'Congratulation! GaiaEHR is installed please click Ok to Login.');
//			echo json_encode($error, JSON_FORCE_OBJECT);
//			exit;
//		}
//	}
//
//	//*****************************************************************************************
//	// Method to Install a Site With Root Access and Creating Database
//	//*****************************************************************************************
//	function rootInstall()
//	{
//		$this->siteCk();
//		$this->rootDatabaseConn();
//		$this->createDatabase();
//		$this->createDatabaseUser();
//		$this->DatabaseConn();
//		$this->sqldump();
//		$this->createRandomKey();
//		$this->buildConf();
//		$this->createSiteConf();
//		$this->adminUser();
//		$this->installationCk();
//	}
//
//	//*****************************************************************************************
//	// Method to Install a Site With Databse User Access
//	//*****************************************************************************************
//	function dbInstall()
//	{
//		$this->siteCk();
//		$this->DatabaseConn();
//		$this->sqldump();
//		$this->createRandomKey();
//		$this->buildConf();
//		$this->createSiteConf();
//		$this->adminUser();
//		$this->installationCk();
//	}

} // end class siteSetup
