<?php

namespace Local\Seo\Meta;

use \Bitrix\Main\Entity\ReferenceField;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Data\Cache;

use \Local\Helper\HighloadBlock as Helper;

/**
 * Класс для работы со страницами на основании роутеров
 */
abstract class AbstractPage
{
    public static $routerEntity = '';//отсылка к роутеру
    
    //поля для подробной страницы в формате array(
        //'{поле}' => '{сущность поля}',
        //'{базовое поле}.{внутр поле}' => '{сущность базового поля}.{сущность внутр поля}'
    //)
    public static $arElementTemplatesFields = array();



    /**
     * Получение условия по коду страницы с учетом уточнений
     *
     * @param string $code - код страницы (равен коду правила роутера)
     * @param array $routerParams - данные для проверки условия  array(поле => значение)
     *
     * @return array - данные условия
     *
     * @example Local\Seo\Meta\Page\Ad::getCondition($code, $routerParams);
     */
    public static function getCondition(string $code, array $routerParams = null): ?array
    {
        $arResCondition = null;
        $rsConditions = Condition::getList(array(
            'filter' => array(
                'UF_ROUTER_CODE' => static::$routerEntity::$code,
                'UF_RULE' => $code,
            ),
            'order' => array(
                'UF_SORT' => 'asc'//сортировка по возрастанию, так как базовое условие 9999999
            )
        ));
        while ($arCondition = $rsConditions->fetch()) {
            //если мы в объявлении
            if ($arCondition["UF_ROUTER_CODE"] == 'ad') {
                $arCondValues = unserialize($arCondition['UF_CONDITIONS']);
                if (!$arCondValues || static::checkConditions($arCondValues, $routerParams)) {
                    return $arCondition;
                }
            }

            //если условия прошли или их нет - это то, что нужно
            if ($arCondition["UF_NAME"] == $routerParams['UF_NAME']) {
                $arCondValues = unserialize($arCondition['UF_CONDITIONS']);
                if (!$arCondValues || static::checkConditions($arCondValues, $routerParams)) {
                    return $arCondition;
                }
            }

            //если нет подходящего правила, применяем базовое
            if($arCondition["UF_NAME"] == 'Базовый') {
                if (!$arCondValues || static::checkConditions($arCondValues, $routerParams)) {
                    return $arCondition;
                }
            }
        }

    	return null;
    }

    /**
     * Сверка условия по значениям
     * Вспомогательный метод
     *
     * @param array $arCondition - условия (массив из БД, задаются в админке)
     * @param array $arValues - значения array(поле => значение)
     *
     * @return bool - результат
     *
     * @example static::checkConditions($arCondition, $arValues);
     */
    protected static function checkConditions(array $arCondition, array $arValues)
    {
        foreach ($arCondition as $field => $value) {
            if ($arValues[$field] != $value) {
                return false;
            }
        }

        return true;
    }




    /**
     * Получение мета-данных для странциы
     * Кеширует результат
     *
     * @param array $arParams - параметры array(поле => id)
     * @param string $ruleCode - код страниц, равен коду правила роутера, если оно известно (снижает нагрузку)
     *
     * @return array - мета-данные
     *
     * @example ClassName::getMeta($code, $arValues);
     */
    public static function getMeta(array $arParams, string $ruleCode = null)
    {       
        $needCache = true;
        
        $cacheKey = 'getMeta_' . serialize($arParams) . $ruleCode . static::class;
        $cacheTime = 86400;
        $cacheDir = '/local/seo/meta/page/';
        
        $cache = Cache::createInstance();
        if ($cache->initCache($cacheTime, $cacheKey, $cacheDir) && $needCache) {
            $arMeta = $cache->getVars();
        	
        } elseif ($cache->startDataCache() || !$needCache) {
            //Получаем правило по коду или пытаемся определить по параметрам
            $routerEntity = static::$routerEntity;
            if ($ruleCode) {
                $arRule = $routerEntity::getRule($ruleCode);
            } else {
                $arRule = $routerEntity::guessRule(array_keys($arParams));
            }
            if (!$arRule) {
                return null;
            }

            //формируем данные для мета-данных
            $arRouterParams = [];
            foreach ($arRule['params'] as $key => $param) {
                $entity = $routerEntity::getParamEntity($param);//сущность параметра

                $id = $arParams[$key];
                $arEntityValues = $entity::getListArray();
                $arValue = $arEntityValues[$id];

                $arRouterParams[$key] = $arValue;
            }

            //формируем мета-данные
            $arMeta = static::formMeta($arRule['code'], $arRouterParams);
        
        	$cache->endDataCache($arMeta); 
        }
        
        return $arMeta;
    }

    /**
     * Формировка мета-данных для странциы
     *
     * @param string $code - код страницы/роута
     * @param array $arParamsValues - данные для мета-параметров array(поле => массив значений)
     *
     * @return array - мета-данные
     *
     * @example ClassName::formMeta($code, $arParamsValues);
     */
    public static function formMeta(string $code, array $arParamsValues)
    {
        //формируем параметры для проверки уточнений для условий
        $arParams = array();
        foreach ($arParamsValues as $key => $arValue) {
            $arParams[$key] = $arValue['ID'];

            if($arValue['NAME'] && !$arParams['UF_NAME'])
                $arParams['UF_NAME'] = $arValue['NAME']; //Для частного правила SEO (условие стр 51)
        }

        //получаем условие, проходящее по параметрам
        $arCondition = static::getCondition($code, $arParams);
        if (!$arCondition) {
            return null;
        }

        //формируем для него мета-данные
        $arMeta = Condition::formMeta($arCondition, $arParamsValues);

        return $arMeta;
    }

    /**
     * Формировка мета-данных для элемента (подробной страницы)
     *
     * @param array $arElement - данные элемента
     * @param string|string $sType - тип страницы
     *
     * @return array - мета-данные
     *
     * @example ClassName::formElementMeta($arElement);
     */
    public static function formElementMeta(array $arElement, string $sType = 'element')
    {
        //получаем условие для подрорбной
        $arCondition = static::getCondition($sType, $arElement);
        if (!$arCondition) {
            return null;
        }

        $arValues = array();

        $elementsEntity = (static::$routerEntity)::$elementsEntity;

        //проходимся по полям, что могут быть в мета-шаблонах подробной страницы 
        $arFields = static::$arElementTemplatesFields;
        foreach ($arFields as $field => $entity) {
            //если поле из сущности самого элемента - просто берем значение
            if ($entity == $elementsEntity) {
                $arValues[$field] = $arElement[$field];
                continue;
            }

            //значение поля
            $value = $arElement[$field];

            //получаем элемент по связанному полю из соответствующей сущности
            $arEntityValues = $entity::getListArray();
            $arValues[$field] = $arEntityValues[$value];
        }

        //формируем мета-данные
        return Condition::formMeta($arCondition, $arValues);
    }




    /**
     * Получение списка страниц
     * Сформированный массив для админки со всеми параметрами
     *
     * @return array - массив страниц
     *
     * @example ClassName::getList();
     */
    public static function getList(): array
    {
        //каждое правило роутера - страница
        $arPages = array();
        $arRules = (static::$routerEntity)::getRules();

        foreach ($arRules as $arRule) {
            //к правилу добавляем данные
            $arPage = static::processRule($arRule);
            $arPages[$arRule['code']] = $arPage;
        }

        return $arPages;
    }

    /**
     * Получение страницы по коду
     * Сформированный массив для админки со всеми параметрами
     *
     * @param string $code - код страницы (равен коду правила роутера)
     *
     * @return array - данные страницы
     *
     * @example Local\Seo\Meta\Page\Ad::get($code);
     */
    public static function get(string $code): array
    {
        $arPages = static::getList();
        return $arPages[$code];
    }




    /**
     * Обработка правила для формировки страницы
     * Из массива правила роутера делает массив страницы
     * Нужен для админки
     *
     * @param array $arRule - правило
     *
     * @return array - страница
     *
     * @example static::processRule($arRule);
     */
    protected static function processRule(array $arRule): ?array
    {
        Loc::loadMessages(__FILE__);

    	switch ($arRule['type']) {
            case 'element':
                //поля страницы
                $arFields = static::getFields(array_keys(static::$arElementTemplatesFields));
                $name = Loc::getMessage('DETAIL_PAGE');
                break;

		    case 'photo':
			    $arFields = static::getFields(array_keys(static::$arElementTemplatesFields));
			    $name = Loc::getMessage('PHOTO_PAGE');
			    break;

            case 'list':
                //формируем поля страницы
                $arFieldCodes = array();
                foreach ($arRule['params'] as $elementField => $param) {
                    $arFieldCodes[] = $elementField;
                }
                foreach ($arRule['relativeParams'] as $field => $paramEntity) {
                    $arFieldCodes[] = $field;
                }
                $arFields = static::getFields($arFieldCodes);

                //формируем название страницы
                $arName = array();
                //если в урле есть Россия - добвляем к названию
                if (stripos($arRule['pattern'], 'rossiya') !== false) {
                    $arName[] = 'Россия';
                }
                foreach ($arRule['params'] as $elementField => $param) {
                    $arField = $arFields[$elementField];
                    $arName[] = $arField['EDIT_FORM_LABEL'];
                }
                $name = implode(' + ', $arName);

                break;

            default:
                return null;
        }

        return array(
            'name' => $name,
            'rule' => $arRule,
            'fields' => $arFields,
        );
    }

    /**
     * Получение полей для страницы
     * Для админки
     *
     * @param array $arFieldCodes - коды полей
     *
     * @return array - данные полей
     *
     * @example static::getFields($arFieldCodes);
     */
    protected static function getFields(array $arFieldCodes): array
    {
        $arFields = array();

        $elementsEntity = (static::$routerEntity)::$elementsEntity;

        foreach ($arFieldCodes as $code) {
            $_code = $code;
            $highloadId = $elementsEntity::$hlId;

            //получаем поле
            $arField = Helper::getCatalogField($code, $highloadId, 'ru');

            //если поле - справочник из хайлоад блока - получаем еще подполя
            if ($arField['USER_TYPE_ID'] == 'hlblock') {
                $arField['SUBFIELDS'] = self::getSubfields($arField['SETTINGS']['HLBLOCK_ID']);
            }

            $arFields[$_code] = $arField;
        }
        
    	return $arFields;
    }

    /**
     * Получение подполей для поля
     * Поля падежей
     *
     * @param int $hlId - ID хайлоад блока из поля
     *
     * @return array - массив подполей
     *
     * @example static::getSubfields($hlId);
     */
    protected static function getSubfields(int $hlId): array
    {
        $arFields = array();

        //получаем нужные поля если они есть
        if ($arField = Helper::getCatalogField('UF_NAME', $hlId, 'ru')) {
            $arFields['UF_NAME'] = $arField;
        }

        if ($arField = Helper::getCatalogField('UF_NAME_NOM', $hlId, 'ru')) {
            $arFields['UF_NAME_NOM'] = $arField;
        }
        if ($arField = Helper::getCatalogField('UF_NAME_GEN', $hlId, 'ru')) {
            $arFields['UF_NAME_GEN'] = $arField;
        }
        if ($arField = Helper::getCatalogField('UF_NAME_DAT', $hlId, 'ru')) {
            $arFields['UF_NAME_DAT'] = $arField;
        }

        if ($arField = Helper::getCatalogField('UF_NAME_ACC', $hlId, 'ru')) {
            $arFields['UF_NAME_ACC'] = $arField;
        }
        if ($arField = Helper::getCatalogField('UF_NAME_NOM_MULTI', $hlId, 'ru')) {
            $arFields['UF_NAME_NOM_MULTI'] = $arField;
        }
        if ($arField = Helper::getCatalogField('UF_NAME_GEN_MULTI', $hlId, 'ru')) {
            $arFields['UF_NAME_GEN_MULTI'] = $arField;
        }
        if ($arField = Helper::getCatalogField('UF_NAME_DAT_MULTI', $hlId, 'ru')) {
            $arFields['UF_NAME_DAT_MULTI'] = $arField;
        }

        return $arFields;
    }
}
