<?php
namespace Local\Api\JsonResponse;

/**
 * Генератор ответа API, содержащего ошибку.
 */
final class JsonResponseFail extends AbstractJsonResponse
{
    /**
     * Конструктор ответа, содержащего ошибку.
     * @param array $error
     */
    public function __construct($error)
    {
        parent::__construct($error);
    }
    
    /**
     * Возврат ошибки с кодом 400
     *
     * @return JsonResponseFail
     */
    public static function setError400(): JsonResponseFail
    {
        $error = self::configureResponse('Неверный запрос', self::STATUS_CODE_ERROR_BAD_REQUEST);
        return new JsonResponseFail($error);
    }
    
    /**
     * Возврат ошибки с кодом 404
     *
     * @return JsonResponseFail
     */
    public static function setError404(): JsonResponseFail
    {
        $error = self::configureResponse('Такой страницы не существует', self::STATUS_CODE_ERROR_PAGE_NOT_FOUND);
        return new JsonResponseFail($error);
    }
    
    /**
     * Возврат ошибки с кодом 500
     *
     * @return JsonResponseFail
     */
    public static function setError500($message = null): JsonResponseFail
    {
        $error = self::configureResponse('Ошибка сервера: ' . $message, self::STATUS_CODE_SERVER_ERROR);
        return new JsonResponseFail($error);
    }
    
    /**
     * Возврат ошибки доступа с кодом 500
     *
     * @return JsonResponseFail
     */
    public static function setAccessDeniedError500(): JsonResponseFail
    {
        $error = self::configureResponse('Access denied', self::STATUS_CODE_ERROR_BAD_REQUEST);
        return new JsonResponseFail($error);
    }
    /**
     * Составление массива ответа
     *
     * @param string $message
     * @param int $code
     * @return array
     */
    private static function configureResponse(string $message, int $code) : array
    {
        return  [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
    }
}
