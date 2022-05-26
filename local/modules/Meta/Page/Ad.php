<?php

namespace Local\Seo\Meta\Page;

use Local\Seo\Meta\AbstractPage;

/**
 * Класс для работы со страницами объявлений на основании роутеров
 */
class Ad extends AbstractPage
{
    public static $elementsEntity = '\Local\Catalog\Ad';
    public static $routerEntity = '\Local\Router\Ad';
    public static $arElementTemplatesFields = [
        'UF_NAME' => '\Local\Catalog\Ad',
    ];
}
