<?php

namespace Local\Helper;

use Local\IndexNow;

class IndexNowHelper
{
    const KEY = 'key';
    /**
     * Имя хоста, так как при по получении глобальной переменной в хост вставляется порт
     */
    const HOST = 'site.ru';

    /**
     * Инициализация событий на изменение/добавление элементов инфоблоков
     *
     * @return void
     */
    public static function initHandlers()
    {
        \Bitrix\Main\EventManager::getInstance()->addEventHandler(
            'iblock', 'OnAfterIBlockElementAdd',
            array(self::class, 'onElementAdd')
        );
        \Bitrix\Main\EventManager::getInstance()->addEventHandler(
            'iblock', 'OnAfterIBlockElementUpdate',
            array(self::class, 'onElementUpdate')
        );
    }

    /**
     * Событие на добавление элемента
     *
     * @param $arFields
     *
     * @return void
     */
    public static function onElementAdd($arFields)
    {
        if (!$arFields['ID']) {
            return;
        }
        self::createRowIndex($arFields['ID']);
    }

    /**
     * Событие на обновление элемента
     *
     * @param $arFields
     *
     * @return void
     */
    public static function onElementUpdate($arFields)
    {
        if (!$arFields['ID']) {
            return;
        }
        self::createRowIndex($arFields['ID']);
    }

    /**
     * Добавление/изменение времени ссылок, на отправку в indexnow
     *
     * @param $id
     *
     * @return void
     */
    public static function createRowIndex($id)
    {
        $detailPage = self::getDetailPageUrl($id);
        if (!$detailPage) {
            return;
        }
        $sElementUrl = 'https://' . self::HOST . $detailPage;
        \Local\IndexNow::addElementIfNotExist($sElementUrl);
    }

    /**
     * Получение детальной ссылки на элемент, если она есть
     *
     * @param $id
     *
     * @return string|void
     */
    public static function getDetailPageUrl($id)
    {
        $res = \CIBlockElement::GetList([], [
            'ID' => $id,
        ], false, false, [
            'ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL',
        ]);


        if ($arItem = $res->GetNext() and !empty($arItem['DETAIL_PAGE_URL'])) {
            return $arItem['DETAIL_PAGE_URL'];
        }
    }

    /**
     * Агент отправляющий измененные страницы на индексацию
     *
     * @return string
     */
    public static function sendIndexNowAgent(): string
    {
        $aElementToSend = \Local\IndexNow::getList(['select' => ['ID', 'UF_URL']])->fetchAll();
        $aLinks = array_column($aElementToSend, 'UF_URL');

        if (!count($aLinks)) {
            return '\\' . __METHOD__ . '();';
        }
        $bIsSended = self::sendLinks($aLinks);

        if ($bIsSended) {
            IndexNow::removeAllFields();
        }
        return '\\' . __METHOD__ . '();';
    }

    /**
     * Отправка ссылок на индексацию
     *
     * @param $links
     *
     * @return bool
     */
    public static function sendLinks($links): bool
    {
        $payload = json_encode([
            'host' => self::HOST,
            'key' => self::KEY,
            'keyLocation' => 'https:/site.ru/key.txt',
            'urlList' => $links,
        ]);

        $ch = curl_init('https://yandex.com/indexnow');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);
        if (!$result['success']) {
            // Если неуспешно - вывести ошибку в панели администратора
            \CAdminNotify::Add([
                'MESSAGE' => 'Произошла ошибка при работе IndexNow. Сообщение: "' . $result['message'] . '"',
                'TAG' => 'INDEX_NOW',
                'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR
            ]);
            return false;
        }
        return true;
    }
}
