<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc; 
Loc::loadMessages(__FILE__);

$arComponentDescription = array(
	"NAME" => Loc::getMessage("AUTHORS_FILTER_COMP_NAME"),
	"DESCRIPTION" => Loc::getMessage("AUTHORS_FILTER_COMP_DESCR"),
	"PATH" => array(
		"ID" => "PARTNER",
	),
);
