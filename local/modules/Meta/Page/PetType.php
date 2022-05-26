<?php

namespace Local\Seo\Meta\Page;

/**
 * Класс для работы со страницами типов питомцев на основании роутеров
 */
class PetType extends \Local\Seo\Meta\AbstractPage
{
    public static $elementsEntity = '\Local\Directory\PetType';
    public static $routerEntity = '\Local\Router\PetType';
    public static $arElementTemplatesFields = [
        'UF_NAME' => '\Local\Directory\PetType',
    ];
}
