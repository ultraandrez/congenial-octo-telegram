<?php
namespace Local\Api\JsonResponse;

/**
 * Генератор успешного ответа API.
 */
final class JsonResponseSuccess extends AbstractJsonResponse
{
    /** @var int HTTP-статус успешного ответа по умолчанию */
    private const HTTP_STATUS_CODE_SUCCESS_DEFAULT = 200;
    
    /**
     * Конструктор успешного ответа API.
     *
     * @param mixed $data       Данные для конвертации в JSON.
     */
    public function __construct($data)
    {
        $data = self::configureResponse($data);
        parent::__construct($data);
    }
    
    /**
     * Составление массива ответа
     * @param array $data
     * @return array
     */
    private function configureResponse(array $data) : array
    {
        return array_merge(['success' => true], $data);
    }
}
