<?php

namespace Local\Seo\Meta\Page;

use Local\Seo\Meta\AbstractPage;

/**
 * Класс для работы со страницами статей на основании роутеров
 */
class ArticleSection extends AbstractPage
{
    public static $elementsEntity = '\Local\Article\SectionTable';
    public static $routerEntity = '\Local\Router\ArticleSection';
    public static $arElementTemplatesFields = [
        'UF_NAME' => '\Local\Article\SectionTable',
    ];
}
