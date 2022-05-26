<?php
namespace Local\Api\Controllers;

use Local\AbstractHighloadWrapper;

class Ads extends AbstractHighloadWrapper
{
    public static $hlId = HIGHLOAD_LITTERS;
    
    /**
     * Обновление записей пометов, пришедших с partner
     *
     * @param $userId
     * @param $partnerId
     *
     * @return bool
     */
    public static function updateUserAds($userId, $partnerId) : bool
    {
        $aPartnerAds = self::getPartnerResponse($partnerId);
        $aPartnerAds = array_merge($aPartnerAds['cats'], $aPartnerAds['dogs']);
        $aPartnerAdsFormatted = array_combine(array_column($aPartnerAds, 'id'), $aPartnerAds);

        $oExistsAds = self::getList([
            'filter' => [
                'UF_OUR_USER_ID' => $userId,
            ]
        ]);
        //проходим по каждой существующей записи и сверяем поля, если не совпадают - обновляем
        if ($oExistsAds !== null) {
            while ($aExistsAd = $oExistsAds->fetch()) {
                if ($aPartnerAdsFormatted[$aExistsAd['UF_partner_ID']]) {
                    $aAd = $aPartnerAdsFormatted[$aExistsAd['UF_partner_ID']];
                    if ($aExistsAd['UF_partner_BREED'] != $aAd['breed'] || $aExistsAd['UF_PET_COUNT'] != $aAd['petsCount']) {
                        self::update($aExistsAd['ID'], [
                            'UF_PET_COUNT' => $aAd['petsCount'],
                            'UF_partner_BREED' => $aAd['breed'],
                            'UF_DATE_CREATE' => $aAd['dateCreate'],
                            'UF_BIRTHDAY' => $aAd['birthday'],
                        ]);
                    }
                    unset($aPartnerAdsFormatted[$aExistsAd['UF_partner_ID']]);
                }
            }
        }
        
        // добавляем новые записи
        foreach ($aPartnerAdsFormatted as $aAd) {
                self::add([
                    'UF_OUR_USER_ID' => $userId,
                    'UF_PET_COUNT' => $aAd['petsCount'],
                    'UF_partner_BREED' => $aAd['breed'],
                    'UF_partner_ID' => $aAd['id'],
                    'UF_DATE_CREATE' => $aAd['dateCreate'],
                    'UF_BIRTHDAY' => $aAd['birthday'],
                ]);
        }
        return true;
    }
    
    /**
     * Получение пометов пользователя partner
     *
     * @param $iUserId
     * @return mixed
     */
    private static function getPartnerResponse($iUserId)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            'https://www.partner.ru/api/ads/?id=' . $iUserId
        );
        $content = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($content, true);
    }
}