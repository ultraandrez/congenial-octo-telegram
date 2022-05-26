<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arComponentParameters = array(
	"PARAMETERS" => array(
	    'FILTER_NAME' => array(
            'NAME' => Loc::getMessage('AUTHORS_FILTER_FILTER_NAME'),
            'TYPE' => 'STRING',
            'DEFAULT' => 'arrFilter',
            'PARENT' => 'BASE',
        ),
	    'ROUTER_PARAMS' => array(
            'NAME' => Loc::getMessage('AUTHORS_FILTER_ROUTER_PARAMS'),
            'TYPE' => 'STRING',
            'DEFAULT' => '',
            'PARENT' => 'BASE',
        ),
		"CACHE_TIME" => array(
			"DEFAULT" => 31*24*60*60,
		),
	)
);
