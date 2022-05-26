<?php
namespace Local\Api\JsonResponse;


/**
 * Стандартизация ответа в формате JSON для API.
 */
abstract class AbstractJsonResponse
{
    /** @var int HTTP-статус ответа с ошибкой по умолчанию */
    protected const STATUS_CODE_SERVER_ERROR = 500;
    /** @var int Неправильный запрос, ошибка на стороне клиента*/
    protected const STATUS_CODE_ERROR_BAD_REQUEST = 400;
    /** @var int Страница не найдена */
    protected const STATUS_CODE_ERROR_PAGE_NOT_FOUND = 404;
    /** @var int Код успешного ответа */
    protected const STATUS_CODE_SUCCESS = 200;
    /**
     * Минимальное значение HTTP-кода ошибки.
     * То есть меньше 300 - успех, больше или равно - ошибка.
     * @var int
     */
    protected const HTTP_STATUS_CODE_ERROR_MIN = 300;
    
    /** @var string $body Тело ответа */
    protected $body;
    
    
    /**
     * Конструктор JSON-ответа API.
     *
     * @param mixed $body       Тело ответа для кодирования в JSON.
     */
    public function __construct($body)
    {
        $this->body = json_encode($body);
    }
    
    
    /**
     * Отдача текущего ответа.
     */
    public function emit(): void
    {
        header('Content-type: application/json');
        if (!is_null($this->body) && $this->body != 'null') {
            echo $this->body;
        }
    }
}
