<?php

namespace Local\Article;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Query;
use Local\Catalog\Ad\Specialization;

/**
 * Класс для работы с инфоблоком Авторы / эксперты
 */
class Authors
{
    const IBLOCK_CODE = 'authors';
    const IBLOCK_API_CODE = 'authors';
    const IBLOCK_ID = IBLOCK_AUTHORS;
    
    
    /**
     * Получение списка авторов / экспертов по указанным параметрам
     * 
     * @param array $filter
     * @param array $order
     * @param int $limit
     * @param int $offset
     * 
     * @return array
     */
    public static function getList(array $filter, array $order = [], int $limit = 0, int $offset = 0) : array
    {
        try {
            $oQuery = self::getQuery();
            $oQuery->setFilter($filter);
            if ($order) {
                // так как стаж высчитывается как текущий год - указанный => сортировка по этому 
                // полю реверсится и нулы надо в обоих случаях тоже перемещать 
                if ($order['NULLS_FIELDS']) {
                    $oQuery->registerRuntimeField(
                        null,
                        new ExpressionField(
                            'NULLS_FIELDS',
                            'ISNULL(%s)',
                            ['EXPERIENCE.VALUE']
                        )
                    );
                }
                $oQuery->setOrder($order);
            }
            if ($limit) {
                $oQuery->setLimit($limit);
            }
            if ($offset) {
                $oQuery->setOffset($offset);
            }
    
            return $oQuery->exec()->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Получение кол-ва записей по фильтру
     * 
     * @param array $filter
     * @return int
     */
    public static function getCount(array $filter) : int
    {
        try {
            $oQuery = self::getQuery();
            return $oQuery->setFilter($filter)->exec()->getSelectedRowsCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
    
    /**
     * Получение списка всех экспертов.
     * @param array $filter
     * @return array
     */
    public static function getExpertsList(array $filter = []): array
    {
        try {
            $aElement = self::getQuery()
                ->setFilter(array_merge(['=PR_ROLE_XML_ID' => 'expert'], $filter))
                ->exec()
                ->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
        return $aElement;
    }
    
    /**
     * Получение информации об эксперте по его ID
     * @param int $iExpertId
     * @return array
     */
    public static function getExpertArray(int $iExpertId): array
    {
        try {
            $aElement = self::getQuery()
                ->setFilter([
                    '=ID' => $iExpertId,
                    '=PR_ROLE_XML_ID' => 'expert'
                ])
                ->exec()
                ->fetch();
            if ($aElement) {
                return $aElement;
            }
        } catch (\Throwable $e) {
            return [];
        }
        return [];
    }
    
    /**
     * Получение массива фильтрации по стажу
     * 
     * @return array[]
     */
    public static function getExperienceValues(): array
    {
        return [
            '3' => [
                'FILTER_VALUE' => (int)date('Y') - 3,
                'LABEL' => 'Более 3 лет'
            ],
            '5' => [
                'FILTER_VALUE' => (int)date('Y') - 5,
                'LABEL' => 'Более 5 лет'
            ]
        ];
    }
    
    /**
     * Составление запроса по авторам/экспертам с селектом
     * 
     * @return Query
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private static function getQuery(): Query
    {
        $entity = self::getEntity();
        $hlEntity = HighloadBlockTable::compileEntity(Specialization::HL_NAME)->getName();
        $oSpecializationRef = new ReferenceField(
            'SPEC',
            $hlEntity,
            ['=this.SPECIALIZATION.VALUE' => 'ref.UF_XML_ID']
        );
        
        return (new Query($entity))
            ->registerRuntimeField('SPEC', $oSpecializationRef)
            ->setSelect([
                'ID',
                'NAME',
                'CODE',
                'PREVIEW_PICTURE',
                'PREVIEW_TEXT',
                'PR_EDUCATION' => 'EDUCATION.VALUE',
                'PR_EXPERIENCE' => 'EXPERIENCE.VALUE',
                'PR_ROLE_XML_ID' => 'ROLE.ITEM.XML_ID',
                'PR_ROLE_NAME' => 'ROLE.ITEM.VALUE',
                'PR_SPECIALIZATION_NAME' => 'SPEC.UF_NAME',
                'PR_SPECIALIZATION_ID' => 'SPEC.ID',
            ]);
    }
    
    /**
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getEntity()
    {
        return IblockTable::compileEntity(self::IBLOCK_API_CODE);
    }
}