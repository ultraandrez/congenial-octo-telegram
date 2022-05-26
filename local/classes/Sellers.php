<?php

namespace Local\Sellers;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Local\Directory\City;
use Local\Directory\OrganizationType;
use \Local\Site\Component\Seller as Seller;

class Sellers
{
    /**
     * Префикс тегированного кеша для очистки детальной страницы продавца.
     * Для очистки берем префикс и конкатенируем с айдишником пользователя
     */
    const SELLER_DETAIL_TAG_PREFIX = 'sellers_user_detail_';
    const SELLER_LIST_TAG = 'sellers_user_list';

    /**
     * Очищение кеша выборки по пользователям на странице списка продавцов
     * 
     * @return void
     */
    public static function clearSellersListByTag()
    {
        global $CACHE_MANAGER;
        $CACHE_MANAGER->ClearByTag(self::SELLER_LIST_TAG);
    }

    /**
     * Очищение кеша выборки по пользователю на детальной странице продавца
     *
     * @return void
     */
    public static function clearSellerDetailByTag($userId)
    {
        global $CACHE_MANAGER;
        $CACHE_MANAGER->ClearByTag(self::SELLER_DETAIL_TAG_PREFIX . $userId);
    }

    /**
     * Вернет все поля, использующиеся для вывода и сортировки пользователей на странице каталога продавцов
     * 
     * @return array
     */
    public static function getRelatedWithSellerListUserFields(): array
    {
        return [
            'NAME',                 //имя конкатенирующееся с фамилией
            'LAST_NAME',
            'WORK_COMPANY',         //имя компании, используется вместо имени, если есть
            'UF_PET_TYPE',
            'UF_PET_BREED',
            'PERSONAL_PHOTO',
            'UF_TYPE',              //организация
            'UF_REGISTER_LICENCE',  //документы для сортировки
            'UF_PARENT_LICENCE',
            'UF_RENT_CONTRACT',
            'ACTIVE',               //при деактивации
            'PERSONAL_CITY',        //привязки к городам
            'UF_CITY',
        ];
    }

    /**
     * Выборка списка пользователей и структуризация данных по ним для вывода в разделе продавцов
     * 
     * @param $iUserId
     * 
     * @return array
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getDetailUserInfo($iUserId): array
    {
        $cachePath = 'seller_detail_user_' . $iUserId;
        $cacheDir = '/seller/user/';
        $obCache = Cache::createInstance();

        if ($obCache->InitCache(86400, $cachePath, $cacheDir))
        {
            return $obCache->GetVars();
        } else {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cachePath);
            $CACHE_MANAGER->RegisterTag(self::SELLER_DETAIL_TAG_PREFIX . $iUserId);
            
            $aUsersData = (new Query(UserTable::getEntity()))
                ->registerRuntimeField(
                    'ORGANIZATION',
                    (new Reference(
                        'ORGANIZATION',
                        OrganizationType::getEntityName(),
                        Join::on(
                            'this.UF_TYPE', 'ref.ID'
                        ))
                    )->configureJoinType('left')
                )
                ->registerRuntimeField(
                    'CITY_REFERENCE',
                    (new Reference(
                        'CITY_REFERENCE',
                        City::getEntityName(),
                        Join::on(
                            'this.UF_CITY', 'ref.ID'
                        ))
                    )->configureJoinType('left')
                )
                ->setSelect([
                    'ID',
                    'DATE_REGISTER',
                    'EMAIL',
                    'PERSONAL_PHONE',
                    'NAME',
                    'LAST_NAME',
                    'ACTIVE',
                    'UF_PET_TYPE',
                    'UF_PET_BREED',
                    'UF_USERS_SUBSCRIBED',
                    'WORK_COMPANY',
                    'PERSONAL_PHOTO',
                    'ORG_ID' => 'ORGANIZATION.ID',
                    'ORG_NAME' => 'ORGANIZATION.UF_NAME',
                    'ORG_CODE' => 'ORGANIZATION.UF_CODE',
                    'CITY_NAME' => 'CITY_REFERENCE.UF_NAME',
                    'PERSONAL_CITY',
                    'PERSONAL_STREET',
                    'PERSONAL_NOTES',
                    'UF_REGISTER_LICENCE',
                    'UF_PARENT_LICENCE',
                    'UF_RENT_CONTRACT',
                    'UF_PHONE_CONFIRMED',
                    'UF_REGISTER_CONFIRMED',
                    'UF_YOULA_PARSE',
                    'UF_FAVORITES_SELLERS',
                    'UF_MID_MESS_TIME',
                ])
                ->setFilter(['=ID' => $iUserId])
                ->exec()
                ->fetch();

            Seller::formatUrl($aUsersData);

            // получаем все породы и составляем ассоциативный массив ID => [NAME, CODE]
            $aBreeds = \Local\Catalog\Breed::getList([
                'select' => ['ID', 'UF_NAME', 'UF_CODE'],
            ]);
            $aBreedAssoc = [];
            foreach ($aBreeds as $aBreed) {
                $aBreedAssoc[$aBreed['ID']] = [
                    'NAME' => $aBreed['UF_NAME'],
                    'CODE' => $aBreed['UF_CODE'],
                ];
            }

            // Формируем город. Либо обычное св-во у пользователя, либо пользовательское
            $sCityName = $aUsersData['PERSONAL_CITY'] ?: $aUsersData['CITY_NAME'];
            $sUserFullAddress = $sCityName . ($aUsersData['PERSONAL_STREET'] ? ', ул. ' . $aUsersData['PERSONAL_STREET'] : '');
            // форматируем дату регистрации
            $sDateRegistered = $aUsersData['DATE_REGISTER'] ? strtolower(FormatDate('d F Y', $aUsersData['DATE_REGISTER']->getTimestamp())) : '';
            // форматируем фото
            $sUserPhoto = $aUsersData['PERSONAL_PHOTO'] ? \CFile::GetPath($aUsersData['PERSONAL_PHOTO']) : SITE_TEMPLATE_PATH . '/images/no-user-pic.png';
            // Собираем имя
            $sUserName = $aUsersData['WORK_COMPANY'] ?: ($aUsersData['NAME'] . ($aUsersData['LAST_NAME'] ? ' ' . $aUsersData['LAST_NAME'] : ''));

            $aUserDataFormatted = [
                'ID' => $aUsersData['ID'],
                'NAME' => $sUserName,
                'DATE_REG' => $sDateRegistered,
                'ACTIVE' => $aUsersData['ACTIVE'],
                'PHOTO' => $sUserPhoto,
                'EMAIL' => $aUsersData['EMAIL'],
                'PHONE' => $aUsersData['PERSONAL_PHONE'],
                'CITY' => $sUserFullAddress,
                'ADDRESS' => [
                    'CITY' => $sCityName,
                    'STREET' => $aUsersData['PERSONAL_STREET']
                ],
                'URL' => $aUsersData['URL'],
                'PERSONAL_NOTES' => $aUsersData['PERSONAL_NOTES'],
                'PHONE_CONFIRMED' => $aUsersData['UF_PHONE_CONFIRMED'],
                'REG_CONFIRMED' => $aUsersData['UF_REGISTER_CONFIRMED'],
                'YOULA_PARSE' => $aUsersData['UF_YOULA_PARSE'],
                'MESS_TIME' => $aUsersData['UF_MID_MESS_TIME'],
                'USERS_SUBSCRIBED' => $aUsersData['UF_USERS_SUBSCRIBED'],
                'FAVORITES_SELLERS' => $aUsersData['UF_FAVORITES_SELLERS'],
                'ORG_TYPE' => $aUsersData['ORG_NAME'],
                'PET_BREED' => $aUsersData['ORG_NAME'],
                'PET_TYPE' => $aUsersData['ORG_NAME'],
                'SOURCE_SORT' => $aUsersData['SOURCE'],
            ];

            // дописываем породы
            foreach ($aUsersData['UF_PET_BREED'] as $aUserBreed) {
                $aUserDataFormatted['BREED_SPEC'][] = $aBreedAssoc[$aUserBreed];
            }

            $CACHE_MANAGER->EndTagCache();
            $obCache->endDataCache($aUserDataFormatted);
        }

        return $aUserDataFormatted;
    }

    /**
     * Выборка списка пользователей и структуризация данных по ним для вывода в разделе продавцов
     * 
     * @param array $filter
     * @param array $order
     * @param int $limit
     * @param int $offset
     * 
     * @return array
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getUserList(array $filter, array $order = [], int $limit = 0, int $offset = 0): array
    {
        $cachePath = 'seller_detail_user_' . md5(serialize($filter)) . md5(serialize($order)) . md5($limit) . md5($offset);
        $cacheDir = '/seller/user/';
        $obCache = Cache::createInstance();

        if ($obCache->InitCache(86400, $cachePath, $cacheDir))
        {
            return $obCache->GetVars();
        } else {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cachePath);
            $CACHE_MANAGER->RegisterTag(self::SELLER_LIST_TAG);
            
            $filter = array_merge(self::getDefaultFilter(), $filter);
            $aUsersData = (new Query(UserTable::getEntity()))
                ->registerRuntimeField(
                    'ORGANIZATION',
                    (new Reference(
                        'ORGANIZATION',
                        OrganizationType::getEntityName(),
                        Join::on(
                            'this.UF_TYPE', 'ref.ID'
                        ))
                    )->configureJoinType('left')
                )
                ->registerRuntimeField(
                    'CITY_REFERENCE',
                    (new Reference(
                        'CITY_REFERENCE',
                        City::getEntityName(),
                        Join::on(
                            'this.UF_CITY', 'ref.ID'
                        ))
                    )->configureJoinType('left')
                )
                ->registerRuntimeField(
                    'SOURCE',
                    (new Reference(
                        'SOURCE',
                        self::compileEntityEnumFields(),
                        Join::on(
                            'this.UF_SOURCE_SORT', 'ref.ID'
                        ))
                    )->configureJoinType('left')
                )
                ->setSelect([
                    'ID',
                    'NAME',
                    'LAST_NAME',
                    'UF_PET_TYPE',
                    'UF_PET_BREED',
                    'UF_USERS_SUBSCRIBED',
                    'WORK_COMPANY',
                    'PERSONAL_PHOTO',
                    'DATE_REGISTER',
                    'ORG_ID' => 'ORGANIZATION.ID',
                    'ORG_NAME' => 'ORGANIZATION.UF_NAME',
                    'ORG_CODE' => 'ORGANIZATION.UF_CODE',
                    'CITY_NAME' => 'CITY_REFERENCE.UF_NAME',
                    'PERSONAL_STREET',
                    'UF_REGISTER_LICENCE',
                    'UF_PARENT_LICENCE',
                    'SOURCE',
                ])
                ->setFilter($filter)
                ->setOrder($order)
                ->setLimit($limit)
                ->setOffset($offset)
                ->exec()
                ->fetchAll();


            // получаем все породы и составляем ассоциативный массив ID => [NAME, CODE]
            $aBreeds = \Local\Catalog\Breed::getList([
                'select' => ['ID', 'UF_NAME', 'UF_CODE'],
            ]);
            $aBreedAssoc = [];
            foreach ($aBreeds as $aBreed) {
                $aBreedAssoc[$aBreed['ID']] = [
                    'NAME' => $aBreed['UF_NAME'],
                    'CODE' => $aBreed['UF_CODE'],
                ];
            }
            // получаем понравившихся продавцов по текущему пользователю
            $aFavoritesSellers = json_decode(self::getFavoritesSellersList(), true);

            $aElements = [];

            foreach ($aUsersData as $aUser) {
                $sUserName = $aUser['WORK_COMPANY'] ?: ($aUser['NAME'] . ($aUser['LAST_NAME'] ? ' ' . $aUser['LAST_NAME'] : ''));
                // собираем адрес. Указанный у продавца или у пользователя + улица пользователя
                $aSellerCity = $aUser['CITY_NAME'];
                $aSellerStreet = $aUser['PERSONAL_STREET'] ? ', ул. ' . $aUser['PERSONAL_STREET'] : '';
                // форматируем дату регистрации
                $sDateRegistered = strtolower(FormatDate('d F Y', $aUser['DATE_REGISTER']->getTimestamp()));
                // формируем урл, параметры передаются будто от пользователя. Для урла нужны CODE и UF_TYPE
                $sElementUrl = \Local\Router\Seller::formElementUrl(['UF_TYPE' => $aUser['ORG_ID'], 'ID' => $aUser['ID']]);

                $sFilePath = $aUser['PERSONAL_PHOTO'] ? \CFile::GetPath($aUser['PERSONAL_PHOTO']) : SITE_TEMPLATE_PATH . '/images/no-user-pic.png';

                $aElement = [
                    'NAME' => $sUserName,
                    'ORG_TYPE_NAME' => $aUser['ORG_NAME'],
                    'USER_INFO' => [
                        'ID' => $aUser['ID'],
                        'URL' => $sElementUrl,
                        'PHOTO' => $sFilePath,
                        'DATE_REG' => $sDateRegistered,
                        'CITY' => $aSellerCity . $aSellerStreet
                    ],
                ];
                // дописываем породы
                foreach ($aUser['UF_PET_BREED'] as $aUserBreed) {
                    $aElement['USER_INFO']['BREED_SPEC'][] = $aBreedAssoc[$aUserBreed];
                }

                if ($aFavoritesSellers[$aUser['ID']]) {
                    $aElement['SUBSCRIBED'] = true;
                }
                $aElements[] = $aElement;
            }
            
            $CACHE_MANAGER->EndTagCache();
            $obCache->endDataCache($aElements);
        }

        return $aElements;
    }

    /**
     * Получение списка избранных пользователей текущего пользователя
     * 
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getFavoritesSellersList()
    {
        global $USER;
        return UserTable::getList([
            'select' => ['UF_FAVORITES_SELLERS',],
            'filter' => ['=ID' => $USER->GetID(),],
        ])->fetch()['UF_FAVORITES_SELLERS'];
    }

    /**
     * Получение списка подписок пользователей текущего пользователя
     * 
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getSubscriptionsSellersList()
    {
        global $USER;
        return UserTable::getList([
            'select' => ['UF_USER_SUBSCRIPTIONS',],
            'filter' => ['=ID' => $USER->GetID(),],
        ])->fetch()['UF_USER_SUBSCRIPTIONS'];
    }

    /**
     * Получить кол-во элементов по заданному фильтру
     * 
     * @param array $filter
     * @return int
     */
    public static function getCount(array $filter) : int
    {
        try {
            $filter = array_merge(self::getDefaultFilter(), $filter);
            return (new Query(UserTable::getEntity()))
                ->addSelect('ID')
                ->setCacheTtl(86400)
                ->setFilter($filter)->exec()->getSelectedRowsCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
    
    /**
     * Фильтр по-умолчанию, дописываем его к любому фильтру запроса
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getDefaultFilter(): array
    {
        $aAdmins = UserGroupTable::getList([
            'filter' => ['=GROUP_ID' => 1, '=USER.ACTIVE' => 'Y'],
            'select' => ['USER_ID'],
            'cache' => ['ttl' => 86400]
        ])->fetchAll();
        $aAdminsIds = array_column($aAdmins, 'USER_ID');
        return [
            '=ACTIVE' => 'Y',
            '!=ID' => $aAdminsIds, // не выводим администраторов
        ];
    }
    
    /**
     * Компиляция сущности таблицы свойств типа список
     *
     * @return Entity
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function compileEntityEnumFields(): Entity
    {
        return Entity::compileEntity(
            'ENUM_FIELDS',
            [
                'ID' => ['data_type' => 'integer'],
                'VALUE' => ['data_type' => 'string'],
                'SORT' => ['data_type' => 'integer'],
            ],
            [
                'table_name' => 'b_user_field_enum',
            ]
        );
    }

    /**
     * Компиляция сущности таблицы с сортировками пользователей
     * 
     * @return Entity
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    private static function compileEntitySourceSort(): Entity
    {
        return Entity::compileEntity(
            'SOURCE_UF_FIELDS',
            [
                'ID' => ['data_type' => 'integer'],
                'UF_SOURCE' => ['data_type' => 'string'],
                'UF_XML_ID' => ['data_type' => 'string'],
                'UF_SORT' => ['data_type' => 'integer']
            ],
            [
                'table_name' => 'ad_sort_sources',
            ]
        );
    }
}