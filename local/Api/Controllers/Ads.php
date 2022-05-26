<?php
namespace Local\Api\Controllers;

use Bitrix\Highloadblock\HighloadBlockTable as HLTable;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;
use Local\Api\JsonResponse\AbstractJsonResponse;
use Local\Api\JsonResponse\JsonResponseFail;
use Local\Api\JsonResponse\JsonResponseSuccess;
use Local\Catalog\Ad;
use Local\Directory\AdStatus;
use Local\Directory\City;
use Local\Directory\PetType;

class Ads
{
    /*
     * Токен для проверки
     */
    private const AUTH_TOKEN = 'token';
    
    /*
     * Параметр урла: токен для аутентификации
     */
    private $token;
    /*
     * Параметр урла: дата начала активности объявления
     */
    private $dateFrom;
    /*
     *  Параметр урла: дата конца активности объявления
     */
    private $dateTo;
    
    
    public function __construct($aQueryParams)
    {
        $this->token = $aQueryParams['token'] ?? '';
        
        if ($aQueryParams['from']) {
            $this->dateFrom = DateTime::createFromTimestamp(strtotime($aQueryParams['from']));
        }
        if ($aQueryParams['to']) {
            $this->dateTo = DateTime::createFromTimestamp(strtotime($aQueryParams['to']));
        }
    }
    
    /**
     * Получение списка объявлений
     * @return AbstractJsonResponse
     * @throws \Bitrix\Main\ObjectException
     */
    public function getAds(): AbstractJsonResponse
    {
        if ($this->token !== self::AUTH_TOKEN) {
            return JsonResponseFail::setAccessDeniedError500();
        }
    
        try {
            $hlblock = HLTable::getById(Ad::$hlId)->fetch();
            $entity = HLTable::compileEntity($hlblock);
            $query = new Query($entity);
            self::buildFilter($query);
    
            Application::getConnection()->startTracker();
            $oQueryResult = $query
                ->registerRuntimeField('ADDRESS_REF', [
                        'data_type' => City::getEntityName(),
                        'reference' => [
                            '=this.UF_CITY' => 'ref.ID',
                        ]
                    ]
                )
                ->registerRuntimeField('PET_TYPE_REF', [
                        'data_type' => PetType::getEntityName(),
                        'reference' => [
                            '=this.UF_PET_TYPE' => 'ref.ID',
                        ]
                    ]
                )
                ->setSelect([
                    'ID', 
                    'UF_ACTIVE', 
                    'UF_DATE_FROM', 
                    'UF_DATE_TO', 
                    'UF_STATUS', 
                    'UF_ADDRESS', 
                    'PET_TYPE_REF.UF_NAME', 
                    'PET_TYPE', 
                    'ADDRESS_REF.UF_NAME', 
                    'CITY_NAME'
                ])
                ->addOrder('UF_DATE_FROM')
                ->exec();
        } catch (\Throwable $e) {
            return JsonResponseFail::setError500($e->getMessage());
        }
    
        $aAds = [];
        $aAdStatuses = AdStatus::getListArray();
        while ($aAd = $oQueryResult->fetch()) {
            $dateFrom = (new DateTime($aAd['UF_DATE_FROM']))->format('d.m.Y H:i:s');
            $dateTo = (new DateTime($aAd['UF_DATE_TO']))->format('d.m.Y H:i:s');
            $sAddress = $aAd['CITY_NAME'] . ($aAd['UF_ADDRESS'] ? (", " . $aAd['UF_ADDRESS']) : '');
            $bActive = (bool) $aAd['UF_ACTIVE'];
            
            $aAds[] = [
                'id' => (int)$aAd['ID'],
                'active' => $bActive,
                'status' => $aAdStatuses[$aAd['UF_STATUS']]['NAME'] ?? null,
                'date_from' => $dateFrom ?? null,
                'date_to' => $dateTo ?? null,
                'pet_type' => $aAd['PET_TYPE'] ?? null,
                'address' => $sAddress ?? null
            ];
        }
        return new JsonResponseSuccess(['ads' => $aAds]);
    }
    
    /**
     * Составление фильтра по полученным в урле параметрам
     *
     * @param object $query
     * @return void
     */
        private function buildFilter(object &$query)
        {
            if (isset($this->dateFrom)) {
                $query->where('UF_DATE_FROM', '>=', $this->dateFrom);
            }
            if (isset($this->dateTo)) {
                $query->where('UF_DATE_FROM', '<=', $this->dateTo);
            }
        }
}