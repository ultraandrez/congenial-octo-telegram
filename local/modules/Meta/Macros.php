<?php
namespace Local\Seo\Meta;

use Protobuf\Exception;

/**
 * Класс для работы с макросами
 */
class Macros
{
    /**
     * Подготовка макросов для мета-данных
     * 
     * @param array $arValues - анные для макросов
     * 
     * @return array - макросы
     *
     * @example Local\Seo\Meta\Macros::prepare($arValues);
     */
    public static function prepare(array $arValues): array
    {
        $arTmpMacroses = array();
        foreach ($arValues as $key => $arValue) {
            //Если поле не из справочника
            if (!is_array($arValue)) {
                $arTmpMacroses[$key] = $arValue;
                continue;
            }

            //базовый макрос параметра
            $arTmpMacroses[$key] = $arValue['UF_NAME'];
            //падежи по параметру
            $arTmpMacroses = [
                "$key . '.UF_NAME_NOM'" => $arValue['UF_NAME_NOM'],
                "$key . '.UF_NAME_GEN'" => $arValue['UF_NAME_GEN'],
                "$key . '.UF_NAME_DAT'" => $arValue['UF_NAME_DAT'],
                "$key . '.UF_NAME_ACC'" => $arValue['UF_NAME_ACC'],
                "$key . '.UF_NAME_PREP'" => $arValue['UF_NAME_PREP'],
                "$key . '.UF_NAME_NOM_MULTI'" => $arValue['UF_NAME_NOM_MULTI'],
                "$key . '.UF_NAME_GEN_MULTI'" => $arValue['UF_NAME_GEN_MULTI'],
                "$key . '.UF_NAME_DAT_MULTI'" => $arValue['UF_NAME_DAT_MULTI'],
                "$key . '.UF_NAME_PREP_MULTI'" => $arValue['UF_NAME_PREP_MULTI'],
            ];
        }
        //для каждого добавляем вариант в нижнем регистре
        foreach ($arTmpMacroses as $key => $makros) {
            $arTmpMacroses[$key . '.LOWER'] = mb_strtolower($makros, "UTF-8");
        }

        //оборачиваем в #
        $arMacroses = array();
        foreach ($arTmpMacroses as $key => $makros) {
            $arMacroses['#' . $key . '#'] = $makros;
        }
        
    	return $arMacroses;
    }
    
    /**
     * Применение макросов к шаблону
     * 
     * @param string $template - шаблон
     * @param array $arMacroses - макросы
     * 
     * @return string - результат
     *
     * @example Local\Seo\Meta\Macros::apply($template, $arMacroses);
     */
    public static function apply(string $template, array $arMacroses): string
    {
        $template = str_replace(
            array_keys($arMacroses),
            array_values($arMacroses),
            $template
        );

        // если есть
        $funcsReplacements = self::getFuncsReplacements($template);
        if($funcsReplacements) {
            $template = str_replace(
                array_keys($funcsReplacements),
                array_values($funcsReplacements),
                $template
            );
        }
        
    	return $template;
    }

    private static function getFuncsReplacements($template): ?array
    {
        // ищем функции внутри шаблона
        $funcs = self::getFuncs($template);
        if(!$funcs) {
            return null;
        }

        // массив соответствий функций и результатов их выполнения
        $replacements = [];
        foreach($funcs as $func) {
            $replacements[$func] = self::processFunc($func);
        }

        return $replacements;
    }

    private static function processFunc($func) {
        // определяем функцию и параметры
        preg_match("/=([a-zA-Z]+)\(([\w\W]*)\)/u", $func, $matches);
        if(!$matches) {
            return $func;
        }

        $method = lcfirst($matches[1]);
        $params = $matches[2];

        // проверяем существование метода
        if(!method_exists("\Local\Seo\Meta\Funcs", $method)) {
            return $func;
        }

        $callable = "\Local\Seo\Meta\Funcs::$method($params);";

        try {
            $funcResult = eval('return ' . $callable);
        }
        catch(\ArgumentCountError $e) {
            // TODO: логирование ошибки
            $funcResult = $func;
        }

        return $funcResult;
    }

    /**
     * Ищет вхождения функций-макросов в шаблоне
     *
     * @param $template Шаблон с текстом макросов
     * @return array|null Возвращает массив функций-макросов
     */
    public static function getFuncs($template) {
        // ищем вхождения функций-макросов
        preg_match_all("/=[a-zA-Z\((\'|\")?[\w\d\s\-]+(\'|\")?(,\s?(\'|\")?[\w\d\s\-]+(\'|\")?)*\)/u", $template, $matches);
        if(!$matches[0]) {
            return null;
        }

        // если встречаются полностью идентичные вызовы, удаляем дубликаты
        $funcs = array_unique($matches[0]);

        return $funcs;
    }
}
