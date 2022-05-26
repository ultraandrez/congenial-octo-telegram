<?php

namespace Local\Seo\Meta\Page;

use Local\Seo\Meta\AbstractPage;

/**
 * Класс для работы со страницами статей на основании роутеров
 */
class ArticleDetail extends AbstractPage
{
    public static $elementsEntity = '\Bitrix\Iblock\Elements\ElementArticlesTable';
    public static $routerEntity = '\Local\Router\ArticleDetail';
    public static $arElementTemplatesFields = [
        'UF_NAME' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_NOM' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_DAT' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_ACC' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_GEN' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_NOM_MULTI' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_DAT_MULTI' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
        'UF_NAME_GEN_MULTI' => '\Bitrix\Iblock\Elements\ElementArticlesTable',
    ];
}
