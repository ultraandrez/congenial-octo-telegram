<?php

namespace Local;

use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock\HighloadBlockTable as HLTable;
use \Bitrix\Main\Data\Cache;
use \Bitrix\Main\Entity;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\EventManager;
use \CUtil;
use \Bitrix\Main\Web\Uri;

/**
 * Абстрактный класс-обертка к методам работы с хайлоад блоками
 * Наследуемся от этого класса и получаем удобство при работе с Highload-блоками, как с отдельными классами ORM
 */
abstract class AbstractHighloadWrapper
{
    public static $hlId;
    public static $cache = true;
    public static $cacheTime = 31536000;

    public static $dateCreateField = 'UF_DATE_CREATE';//поле даты создания
    public static $dateUpdateField = 'UF_DATE_UPDATE';//поле даты изменения

    public static $codeField = 'UF_CODE';//поле символьного кода
    public static $codeTransliterateField = 'UF_NAME';//поле для транслитерации символьного кода

//    @example
//    /**
//     * Инициализация обработчиков
//     *
//     * @return void
//     *
//     * @example ClassName::initHandlers();
//     */
//    public static function initHandlers()
//    {
//        self::initDateCreateHandlers();//автодобавление даты создания
//        self::initDateUpdateHandlers();//автодобавление даты изменения
//
//        self::initCodeTransliterateHandlers();//автотранслитерация символьного кода
//        self::initCodeIdHandlers();//автодобавление ID в символьный код
//    }


    /**
     * Формирование многомерного массива из связных полей выборки
     * Связные поля при выборке пишутся в основной массив записи с длиннющими составными названиями вроде CORP_RDER_UF_STATUS_VALUE_UF_NAME
     * Этот метод реформирует массив в многомерный и поле, указанное выше будет находиться в UF_STATUS_VALUE[UF_NAME]
     *
     * @param array $arItem - исходный массив записи со связаными полями
     * @param array $arRuntime - массив связных полей выборки
     *
     * @return array - результат
     * @example ClassName::referenceToArray($arItem, $arRuntime);
     *
     */
    public static function referenceToArray(array $arItem = array(), array $arRuntime = array())
    {
        //получаем имя класса, который вызван
        $className = static::compileEntity();
        $arClassName = explode('\\', $className);//разбиваем по namespace
        $class = array_pop($arClassName);//берем исходный класс
        $class = str_replace('Table', '', $class);//обрезаем приставку таблицы

        //из имени класса формируем префикс связного поля
        //например CorpOrder => CORP_ORDER
        $refPrefix = '';
        for ($i = 0; $i < strlen($class); $i++) {
            $sym = $class[$i];
            $symUpper = strtoupper($class[$i]);

            if ($sym == $symUpper && $refPrefix) {
                $refPrefix .= '_';
            }

            $refPrefix .= $symUpper;
        }

        //по переданным связным полям выборки формируем массив {нужный код поля} => {полный код поля}
        $arRefCodes = array();
        foreach ($arRuntime as $refField) {
            $arRefCodes[$refField->getName()] = $refPrefix . '_' . $refField->getName();
        }

        //реформируем исходный массив записи
        $arNewItem = array();
        //проходимся по каждому полю записи
        foreach ($arItem as $key => $value) {
            $ref = false;

            //проходимся по связным полям
            foreach ($arRefCodes as $code => $refCode) {
                //по коду поля записи смотрим - это связное поле?
                if (stripos($key, $refCode) === 0) {
                    // если да, то пишем его в многомерный массив
                    $refKey = str_replace($refCode . '_', '', $key);
                    $arNewItem[$code][$refKey] = $value;
                    $ref = true;
                    break;
                }
            }

            //для несвязных полей - просто пишем в массив
            if (!$ref) {
                $arNewItem[$key] = $value;
            }
        }

        return $arNewItem;
    }


    /**
     * Получение названия сущности
     *
     * @return string
     * @example ClassName::getEntityName();
     *
     */
    public static function getEntityName()
    {
        if (!Loader::includeModule('highloadblock') || !static::$hlId) {
            return '';
        }

        $hlblock = HLTable::getById(static::$hlId)->fetch();
        if (!$hlblock) {
            return '';
        }

        return $hlblock['NAME'];
    }

    /**
     * Формировка сущности хайлоад блока
     *
     * @return string
     * @example ClassName::compileEntity();
     *
     */
    protected static function compileEntity()
    {
        if (!Loader::includeModule('highloadblock') || !static::$hlId) {
            return '';
        }

        $hlblock = HLTable::getById(static::$hlId)->fetch();
        if (!$hlblock) {
            return '';
        }
        $entity = HLTable::compileEntity($hlblock);

        return $entity->getDataClass();
    }


    /**
     * Получение списка
     *
     * @param array $arParams - параметры запроса
     *
     * @return \Bitrix\Main\DB\MysqliResult Object - объект результата запроса
     * @example ClassName::checkList($arParams);
     *
     */
    public static function getList(array $arParams = array())
    {
        if ($class = self::compileEntity()) {
            return $class::getList($arParams);
        }

        return;
    }

    /**
     * Добавление новой записи
     *
     * @param array $arFields - массив данны записи
     *
     * @return \Bitrix\Main\Entity\AddResult Object - объект результата запроса
     * @example ClassName::add($arFields);
     *
     */
    public static function add(array $arFields)
    {
        if ($class = self::compileEntity()) {
            return $class::add($arFields);
        }

        return;
    }

    /**
     * Обновление записи
     *
     * @param int $id - ID записи
     * @param array $arFields - массив данны записи
     *
     * @return \Bitrix\Main\Entity\UpdateResult Object - объект результата запроса
     * @example ClassName::update($id, $arFields);
     *
     */
    public static function update(int $id, array $arFields)
    {
        if ($class = self::compileEntity()) {
            return $class::update($id, $arFields);
        }

        return;
    }

    /**
     * Удаление записи
     *
     * @param int $id - ID записи
     *
     * @return \Bitrix\Main\Entity\DeleteResult Object - объект результата запроса
     * @example ClassName::delete($id);
     *
     */
    public static function delete(int $id)
    {
        if ($class = self::compileEntity()) {
            return $class::delete($id);
        }

        return;
    }

    /**
     * Получение количества по фильтру
     *
     * @param array $arFilter - фильтр
     *
     * @return int - количество
     * @example ClassName::getCount($arFilter);
     *
     */
    public static function getCount(array $arFilter)
    {
        if ($class = self::compileEntity()) {
            return $class::getCount($arFilter);
        }

        return null;
    }

    /**
     * Очистка кеша сущности
     *
     * @return void
     * @example ClassName::cleanCache();
     *
     */
    public static function cleanCache()
    {
        if ($class = self::compileEntity()) {
            $class::getEntity()->cleanCache();
        }
    }

    /**
     * Получение списка элементов в виде массива
     * Кеширует результат
     *
     * @param array|null $arOrder - сортировка
     * @param array|null $arFilter - фильтр
     * @param array|null $arSelect - выборка
     *
     * @return array - результат
     *
     * @example ClassName::getListArray($arOrder, $arFilter);
     */
    public static function getListArray(array $arOrder = null, array $arFilter = null, array $arSelect = null): array
    {
        $aItems = [];
        $cache = Cache::createInstance();

        $cacheDir = '/dev/' . str_replace('\\', '/', __CLASS__) . '/' . static::$hlId . '/getListArray';
        $cacheKey = md5(json_encode($arOrder) . json_encode($arFilter) . json_encode($arSelect));
        if ($cache->initCache(static::$cacheTime, $cacheKey, $cacheDir) && static::$cache) {
            $aItems = $cache->getVars();
        } elseif ($cache->startDataCache() || !static::$cache) {
            $arParams = [
                'order' => ($arOrder ?: ['ID' => 'ASC']),
                'filter' => ($arFilter ?: []),
            ];
            if ($arSelect) {
                $arParams['select'] = $arSelect;
            }
            $rsItems = static::getList($arParams);
            while ($arItem = $rsItems->fetch()) {
                $aItems[$arItem['ID']] = $arItem;
            }

            $cache->endDataCache($aItems);
        }

        return $aItems;
    }

    /**
     * Получение элемента в виде массива
     * Кеширует результат
     *
     * @param int $id - ID
     *
     * @return array - результат
     *
     * @example ClassName::getElementArray($id);
     */
    public static function getElementArray(int $id): array
    {
        $aItems = static::getListArray();
        return $aItems[$id];
    }


    /**
     * Инициализация обработчиков автоматического добавления даты создания
     *
     * @return void
     *
     * @example self::initDateCreateHandlers();
     */
    protected static function initDateCreateHandlers()
    {
        if (!static::$dateCreateField) {
            return;
        }

        $entityName = self::getEntityName();
        if (!$entityName) {
            return;
        }

        $manager = EventManager::getInstance();
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeAdd',
            array(
                static::class,
                'dateCreateHandler'
            )
        );
    }

    /**
     * Обработчик добавления даты создания
     *
     * @param Event $event - событие
     *
     * @return EventResult - результат обработчика
     *
     * @example ClassName::dateCreateHandler($event);
     */
    public static function dateCreateHandler(Event $event)
    {
        $arFields = $event->getParameter('fields');
        if ($arFields[static::$dateCreateField]) {
            return null;
        }

        $result = new EventResult;
        $result->modifyFields(array(
            static::$dateCreateField => new DateTime()
        ));

        return $result;
    }


    /**
     * Инициализация обработчиков автоматического добавления даты изменения
     *
     * @return void
     *
     * @example self::initDateUpdateHandlers();
     */
    protected static function initDateUpdateHandlers()
    {
        if (!static::$dateUpdateField) {
            return;
        }

        $entityName = self::getEntityName();
        if (!$entityName) {
            return;
        }

        $manager = EventManager::getInstance();
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeAdd',
            [
                static::class,
                'dateUpdateHandler'
            ]
        );
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeUpdate',
            [
                static::class,
                'dateUpdateHandler'
            ]
        );
    }

    /**
     * Обработчик добавления даты изменения
     *
     * @param Event $event - событие
     *
     * @return EventResult - результат обработчика
     *
     * @example ClassName::dateUpdateHandler($event);
     */
    public static function dateUpdateHandler(Event $event): EventResult
    {
        $result = new EventResult;
        $result->modifyFields(array(
            static::$dateUpdateField => new DateTime()
        ));

        return $result;
    }


    /**
     * Инициализация обработчиков автоматической транслитерации символьного кода
     *
     * @return void
     *
     * @example self::initCodeTransliterateHandlers();
     */
    protected static function initCodeTransliterateHandlers()
    {
        if (!static::$codeTransliterateField || !static::$codeField) {
            return;
        }

        $entityName = self::getEntityName();
        if (!$entityName) {
            return;
        }

        $manager = EventManager::getInstance();
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeAdd',
            [
                static::class,
                'codeTransliterateHandler'
            ]
        );
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeUpdate',
            [
                static::class,
                'codeTransliterateHandler'
            ]
        );
    }

    /**
     * Обработчик транслитерации символьного кода
     *
     * @param Event $event - событие
     *
     * @return EventResult - результат обработчика
     *
     * @example ClassName::codeTransliterateHandler($event);
     */
    public static function codeTransliterateHandler(Event $event): EventResult
    {
        $arFields = $event->getParameter('fields');

        //если не меняется транслитерируемое поле или код задан вручную - ничего не делаем
        if (!isset($arFields[static::$codeTransliterateField]) || $arFields[static::$codeField]) {
            return;
        }

        //транслитерируем значение
        $code = Cutil::translit(
            $arFields[static::$codeTransliterateField],
            'ru',
            [
                'replace_space' => '-',
                'replace_other' => '-'
            ]
        );

        //предаем в событие для применения
        $result = new EventResult;
        $result->modifyFields(array(
            static::$codeField => $code
        ));

        return $result;
    }


    /**
     * Инициализация обработчиков автоматической транслитерации символьного кода
     *
     * @return void
     *
     * @example self::initCodeIdHandlers();
     */
    protected static function initCodeIdHandlers()
    {
        if (!static::$codeField) {
            return;
        }

        $entityName = self::getEntityName();
        if (!$entityName) {
            return;
        }

        $manager = EventManager::getInstance();
        $manager->addEventHandler(
            '',
            $entityName . 'OnAfterAdd',
            [
                static::class,
                'codeIdAfterHandler'
            ]
        );
        $manager->addEventHandler(
            '',
            $entityName . 'OnBeforeUpdate',
            [
                static::class,
                'codeIdBeforeHandler'
            ]
        );
    }

    /**
     * Обработчик добавления ID в символьный код при изменении элемента
     *
     * @param Event $event - событие
     *
     * @return EventResult - результат обработчика
     *
     * @example ClassName::codeIdBeforeHandler($event);
     */
    public static function codeIdBeforeHandler(Event $event)
    {
        $arFields = $event->getParameter('fields');

        $id = $event->getParameter('id');
        if ($id['ID']) {
            $id = $id['ID'];
        }

        //учитываем другие обработчики, которые изменяют код
        foreach ($event->getResults() as $result) {
            $arModifiedFields = $result->getModified();
            if (isset($arModifiedFields[static::$codeField])) {
                $arFields[static::$codeField] = $arModifiedFields[static::$codeField];
            }
        }

        //если не меняется код - ничего не делаем
        if (!isset($arFields[static::$codeField])) {
            return;
        }

        //разбираем новый символьный код для удобства
        $arCode = explode('-', $arFields[static::$codeField]);

        //если в символьном коде, последний элемент не ID записи - дописываем ее последним элементом
        //чтобы не дописать дважды
        if (empty($arCode) || $arCode[count($arCode) - 1] != $id) {
            $arCode[] = $id;
        }

        //собираем символьный код обратно
        $code = implode('-', $arCode);

        //предаем в событие для применения
        $result = new EventResult;
        $result->modifyFields(array(
            static::$codeField => $code
        ));

        return $result;
    }

    /**
     * Обработчик добавления ID в символьный код при добавлении элемента
     *
     * @param Event $event - событие
     *
     * @return void
     *
     * @example ClassName::codeIdAfterHandler($event);
     */
    public static function codeIdAfterHandler(Event $event)
    {
        $id = $event->getParameter('id');
        if ($id['ID']) {
            $id = $id['ID'];
        }

        if (!$id) {
            return;
        }

        //получаем текущий код
        $rsElement = self::getList(array(
            'filter' => array('ID' => $id),
            'select' => array(static::$codeField)
        ));
        $arElement = $rsElement->fetch();
        $code = (string)$arElement[static::$codeField];

        //разбираем текущий символьный код для удобства
        $arCode = explode('-', $code);

        //если в символьном коде, последний элемент не ID записи - дописываем ее последним элементом
        //чтобы не дописать дважды
        if (empty($arCode) || $arCode[count($arCode) - 1] != $id) {
            $arCode[] = $id;
        }

        //собираем символьный код обратно
        $code = implode('-', $arCode);

        //обновляем код
        self::update($id, array(
            static::$codeField => $code
        ));
    }


    /**
     * Получение урла страницы админ части для хайлоад блока
     *
     * @param int $elementId - ID элемента (если нужна страница редактирования)
     * @param string $lang - язык админ части
     *
     * @return string - урл
     *
     * @example ClassName::getAdminUrl($elementId, $lang);
     */
    public static function getAdminUrl(int $elementId = null, string $lang = null)
    {
        $baseUrl = '/bitrix/admin/highloadblock_rows_list.php';
        $arQuery = array(
            'ENTITY_ID' => static::$hlId,
            'lang' => ($lang ? $lang : LANGUAGE_ID)
        );
        if (!is_null($elementId)) {
            $arQuery['ID'] = $elementId;
            $baseUrl = '/bitrix/admin/highloadblock_row_edit.php';
        }

        $uri = new Uri($baseUrl);
        $uri->addParams($arQuery);

        return $uri->getUri();
    }
}
