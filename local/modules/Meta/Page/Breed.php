<?php

namespace Local\Seo\Meta\Page;

use Local\Seo\Meta\AbstractPage;

/**
 * Класс для работы со страницами пород на основании роутеров
 */
class Breed extends AbstractPage
{
    public static $elementsEntity = '\Local\Catalog\Breed';
    public static $routerEntity = '\Local\Router\Breed';
    public static $arElementTemplatesFields = [
        'UF_NAME' => '\Local\Catalog\Breed',
    ];
}
