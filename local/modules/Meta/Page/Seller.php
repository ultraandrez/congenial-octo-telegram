<?php

namespace Local\Seo\Meta\Page;

use \CUserTypeEntity;

/**
 * Класс для работы со страницами типов питомцев на основании роутеров
 */
class Seller extends \Local\Seo\Meta\AbstractPage
{
    public static $elementsEntity = '\Bitrix\Main\UserTable';
    public static $routerEntity = '\Local\Router\Seller';
    public static $arElementTemplatesFields = [
        'NAME' => '\Bitrix\Main\UserTable',
    ];

    /**
     * Получение полей для страницы
     *
     * @param array $arFieldCodes - коды полей
     *
     * @return array - данные полей
     *
     * @example static::getFields($arFieldCodes);
     */
    protected static function getFields(array $arFieldCodes)
    {
        $arFields = array();

        foreach ($arFieldCodes as $code) {
            //для дефолтных полей нужны заглушки, так как это не UF, их получить неоткуда
            switch ($code) {
                case 'NAME':
                    $arField = Array(
                        'ENTITY_ID' => 'USER',
                        'FIELD_NAME' => 'NAME',
                        'USER_TYPE_ID' => 'system',
                        'EDIT_FORM_LABEL' => 'Имя',
                        'LIST_COLUMN_LABEL' => 'Имя',
                        'LIST_FILTER_LABEL' => 'Имя',
                    );
                    break;

                case 'LAST_NAME':
                    $arField = Array(
                        'ENTITY_ID' => 'USER',
                        'FIELD_NAME' => 'LAST_NAME',
                        'USER_TYPE_ID' => 'system',
                        'EDIT_FORM_LABEL' => 'Фамилия',
                        'LIST_COLUMN_LABEL' => 'Фамилия',
                        'LIST_FILTER_LABEL' => 'Фамилия',
                    );
                    break;

                case 'WORK_COMPANY':
                    $arField = Array(
                        'ENTITY_ID' => 'USER',
                        'FIELD_NAME' => 'WORK_COMPANY',
                        'USER_TYPE_ID' => 'system',
                        'EDIT_FORM_LABEL' => 'Название компании',
                        'LIST_COLUMN_LABEL' => 'Название компании',
                        'LIST_FILTER_LABEL' => 'Название компании',
                    );
                    break;

                default:
                    //получаем поле
                    $arField = self::getUserField($code, 'ru');
                    break;
            }

            //если поле - справочник из хайлоад блока - получаем еще подполя
            if ($arField['USER_TYPE_ID'] == 'hlblock') {
                $arField['SUBFIELDS'] = self::getSubfields($arField['SETTINGS']['HLBLOCK_ID']);
            }

            $arFields[$code] = $arField;
        }

        return $arFields;
    }

    /**
     * Получение поля пользорвателя
     *
     * @param string $name - код поля
     * @param string $lang - язык для получения языковых названий
     *
     * @return array - данные поля
     *
     * @example self::getUserField($name);
     */
    private static function getUserField(string $name, string $lang = null): array
    {
        $arFilter = array(
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => $name,
        );
        if ($lang) {
            $arFilter['LANG'] = $lang;
        }
        $rsFields = CUserTypeEntity::GetList(array(), $arFilter);

        return $rsFields->Fetch();
    }
}
