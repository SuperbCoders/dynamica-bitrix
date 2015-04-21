<?
IncludeModuleLangFile(__FILE__);
Class itcube_dynamica extends CModule
{
	const MODULE_ID = 'itcube.dynamica';
	var $MODULE_ID = 'itcube.dynamica';
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	function itcube_dynamica()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__)."/version.php");
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = GetMessage("itcube.dynamica_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("itcube.dynamica_MODULE_DESC");

		$this->PARTNER_NAME = GetMessage("itcube.dynamica_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("itcube.dynamica_PARTNER_URI");
	}

	function InstallDB($arParams = array())
	{
        COption::SetOptionString(self::MODULE_ID, "api_token", "");
        COption::SetOptionString(self::MODULE_ID, "project_id", "");
        COption::SetOptionString(self::MODULE_ID, "activate_module", "Y");
        COption::SetOptionString(self::MODULE_ID, "period_count", "10");
        COption::SetOptionString(self::MODULE_ID, "period_type", "day");
        return true;
	}

	function UnInstallDB($arParams = array())
	{
        COption::RemoveOption(self::MODULE_ID);
        return true;
	}

	function InstallAgents()
	{
        global $DB;
        /*CAgent::AddAgent(
            "CItcubeDynamyca::sendStatistics();",
            self::MODULE_ID,
            "N",
            86400,
            date( $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), mktime(11, 30, 0, date("n"), date("j"), date("Y") ) ),
            "Y",
            date( $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")), mktime(11, 30, 0, date("n"), date("j"), date("Y") ) )
        );*/
        CAgent::AddAgent(
            "CItcubeDynamyca::sendIfError();",
            self::MODULE_ID,
            "N",
            7200
        );
		return true;
	}

	function UnInstallAgents()
	{
        CAgent::RemoveModuleAgents(self::MODULE_ID);
		return true;
	}

	function InstallFiles($arParams = array())
	{
        if( !is_dir($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/') ){
            mkdir($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/', 0777, true);
        }
        copy(
            $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/itcube.dynamica/cron/cron.php',
            $_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/dynamica_cron.php'
        );
		return true;
	}

	function UnInstallFiles()
	{
        unlink($_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_error.txt');
        unlink($_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_ids.txt');
        unlink($_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_firstexec.txt');
        unlink($_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_lastexec.txt');
        unlink($_SERVER["DOCUMENT_ROOT"].'/bitrix/php_interface/dynamica_cron.php');
		return true;
	}

	function DoInstall()
	{ 
		global $APPLICATION;
		RegisterModule(self::MODULE_ID);
        $this->InstallFiles();
        $this->InstallDB();
        $this->InstallAgents();
	}

	function DoUninstall()
	{
		global $APPLICATION;
        $this->UnInstallAgents();
        $this->UnInstallDB();
        $this->UnInstallFiles();
		UnRegisterModule(self::MODULE_ID);
	}
}
?>
