<?php
namespace Local\Api\Controllers;

use Bitrix\Main\DB\Exception;

/**
 * Класс API с методами аутентификации
 */
class Auth
{
    /**
     * Идентификатор пользователя
     */
    private $iUserId;
    /**
     * Логин пользователя на партнере
     */
    private $login;
    /**
     * Идентификатор пользователя на партнере
     */
    private $partnerId;
    /**
     * Токен пользователя с партнера
     */
    private $token;
    
    /**
     * Конструктор, который присваивает поля из запроса в переменные класса
     *
     * @param $aQueryParams
     */
    public function __construct($aQueryParams)
    {
        $this->login = $aQueryParams['login'];
        $this->partnerId = $aQueryParams['partnerId'];
        $this->token = $aQueryParams['token'];
    }
    
    /**
     * Сверка данных пользователя с данными от партнера
     *
     * @throws \Exception
     */
    public function auth()
    {
        // проверяем поля на существование
        if (!$this->login || !$this->partnerId || empty((int)($this->partnerId)) || !$this->token) {
            throw new \Exception('Неверные параметры запроса');
        }
        // если не нашли пользователя - создаем
        if (!$this->isUserExistWithPartnerId())
        {
            if (!$this->createPartnerUser())
            {
                throw new \Exception('Не удалось создать пользователя');
            }
        }
        // сверяем пометы
        \Local\Api\Controllers\Litters::updateUserLitters($this->iUserId, $this->partnerId);
        
        global $USER;
        $USER->Authorize($this->iUserId);
        LocalRedirect('/personal/');
    }
    
    /**
     * Проверка существования пользователя на template
     *
     * @return bool
     */
    private function isUserExistWithPartnerId() : bool
    {
        $oUser = \Bitrix\Main\UserTable::getList([
            'select' => ['ID'],
            'filter' => [
                'UF_partner_ID' => $this->partnerId,
                'EMAIL'     => $this->login
            ],
        ]);

        if ($oUser !== null) {
            $aUser = $oUser->fetch();
            if ($aUser) {
                $this->iUserId = $aUser['ID'];
                return true;
            }
        }
        return false;
    }
    
    /**
     *
     *
     * @return bool
     * @throws \Exception
     */
    private function createPartnerUser() : bool
    {
        $aPartnerUserData = self::getPartnerUserData();

        $sPassword = self::generateRandomPassword();
        $aUserFields = [
            "NAME"              => $aPartnerUserData['name'],
            "LAST_NAME"         => $aPartnerUserData['lastName'],
            "SECOND_NAME"       => $aPartnerUserData['secondName'],
            "EMAIL"             => $aPartnerUserData['email'],
            "LOGIN"             => $aPartnerUserData['login'],
            "ACTIVE"            => "Y",
            "PASSWORD"          => $sPassword,
            "CONFIRM_PASSWORD"  => $sPassword,
            "UF_partner_ID"         => $aPartnerUserData['id'],
            "PERSONAL_PHONE"    => $aPartnerUserData['phone'],
            "UF_USER_GROUPS"    => 24
        ];

        $user = new \CUser;
        $iAddedUserId = $user->Add($aUserFields);
        if (intval($iAddedUserId) > 0) {
            $this->iUserId = $iAddedUserId;
            return true;
        }
        return false;
    }
    
    /**
     * Получение данных пользователя с partner
     *
     * @return array
     * @throws \Exception
     */
    private function getPartnerUserData() : array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            'https://www.partner.ru/api/user/?id=' . $this->partnerId . '&token=' . $this->token
        );
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
    
        if (!$response['success'])
        {
            throw new \Exception('Пользователь на partner не найден!');
        }
        return $response['user'];
    }
    
    /**
     * Генерация рандомного пароля
     *
     * @return string
     */
    private function generateRandomPassword() : string
    {
        return md5(time() . rand());
    }
}
