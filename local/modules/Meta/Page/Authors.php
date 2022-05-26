<?php

namespace Local\Seo\Meta\Page;

use \CUserTypeEntity;
use Local\Seo\Meta\AbstractPage;

/**
 * Класс для работы со страницами авторов и экспертов на основании роутеров
 */
class Authors extends AbstractPage
{
    public static $elementsEntity = '\Bitrix\Iblock\Elements\ElementAuthorsTable';
    public static $routerEntity = '\Local\Router\Authors';
    public static $arElementTemplatesFields = [
        'NAME' => '\Bitrix\Iblock\Elements\ElementAuthorsTable'
    ];
}
