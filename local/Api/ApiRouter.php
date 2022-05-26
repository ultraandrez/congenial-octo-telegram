<?php
namespace Local\Api;

use Exception;
use Local\Api\Controllers\Ads;
use Local\Api\Controllers\Auth;
use Local\Api\JsonResponse\AbstractJsonResponse;
use Local\Api\JsonResponse\JsonResponseFail;

/**
 * Класс для роутинга на методы API
 */
class ApiRouter
{
    /**
     * Идентификатор метода, по которому создается класс, генерирующий ответ
     * @var string
     */
    private $sMethod;
    
    /**
     * Массив параметров запроса
     * @var object
     */
    private $aParams;
    
    /**
     * Инициализация идентификатора метода и выполнение формирования запроса
     *
     * @param $method
     * @param $params
     */
    public function __construct($method, $params)
    {
        $this->sMethod = $method;
        $this->aParams = $params;
    }
    
    /**
     * @return AbstractJsonResponse | void
     * @throws Exception
     */
    public function createResponseByClassIdentifier()
    {
        switch ($this->sMethod) {
            case 'auth':
                $oclass = new Auth($this->aParams);
                $oclass->auth();
                break;
            case 'ads':
                $oclass = new Ads($this->aParams);
                return $oclass->getAds();
            default:
                return JsonResponseFail::setError404();
        }
    }
}
