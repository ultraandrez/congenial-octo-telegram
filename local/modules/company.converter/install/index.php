<?php
IncludeModuleLangFile(__FILE__);

class site_converter extends CModule
{
    const MODULE_ID = 'site.converter';
    var $MODULE_ID = 'site.converter';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = [];
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::Loc::getMessage('CONVERTER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('CONVERTER_MODULE_DESC');

        $this->PARTNER_NAME = Loc::getMessage('CONVERTER_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('CONVERTER_PARTNER_URI');
    }

    function InstallDB($arParams = [])
    {
        return true;
    }

    function UnInstallDB($arParams = [])
    {
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles($arParams = [])
    {
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    function DoInstall()
    {
        global $APPLICATION;
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
    }

    function DoUninstall()
    {
        global $APPLICATION;
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}
