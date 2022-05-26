<?php

namespace Local\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\IO\File;

/**
 * Класс для работы с веб-сервисом ws3.morpher.ru. Работа со склонениями слов
 * и словосочетаний по падежам.
 **/
class Morpher
{
    // адрес веб-сервиса
    private $morpher_url = 'https://ws3.morpher.ru';

    // путь к папке с кешем относительно корня
    private $cache_path = null;

    // количество оставшихся запросов за сутки
    public $left_requests_per_day = null;

    // соотношение ключей падежей
    private $padezhi = array(
        'IMEN' => 'И',
        'ROD' => 'Р',
        'DAT' => 'Д',
        'VIN' => 'В',
        'TVOR' => 'Т',
        'PRED' => 'П'
    );

    //файл для кеша запросов
    public $morpher_cache_file = '/local/eternal_cache/morpher.data';

    //файл для логов
    public $morpher_log_file = '/local/logs/morpher_left_queries.log';

    public function __construct()
    {
        $document_root = Application::getDocumentRoot();

        $file = new File($document_root . $this->morpher_cache_file);
        if (!$file->isExists()) {
            $file->putContents(json_encode(array()));
        }
        $file = new File($document_root . $this->morpher_log_file);
        if (!$file->isExists()) {
            $file->putContents('');
        }

        // не сохраняем кеш в общей папке, чтобы его нельзя было стереть из админки
        $this->cache_path = $document_root . $this->morpher_cache_file;
    }

    /**
     * Достаём массив данных из кеша
     * @return array|mixed|null
     */
    private function getAllDataFromCache()
    {
        // если кеш файл не создан отдаём null
        if (!file_exists($this->cache_path)) {
            return array();
        }

        // получаем данные из кеш-файл
        $data = file_get_contents($this->cache_path);
        if (!trim($data)) {
            return null;
        }

        $json = json_decode($data, true);
        if ($json === null) {
            return array();
        }

        return $json;
    }

    /**
     * определяем, сколько осталось запросов на сегодня
     * @return int
     */
    public function leftDayQueries()
    {
        // адрес веб-сервиса для получения лимита запросов на текущие сутки
        // в JSON
        $morpher_url = $this->morpher_url . '/get_queries_left_for_today?format=json';

        // получаем результат
        $left_queries = (int)file_get_contents($morpher_url);

        // пишем в лог данные по количеству оставшихся запросов
        $log_path = $_SERVER['DOCUMENT_ROOT'] . $this->morpher_log_file;
        $log_str = 'For ' . date('d.m.Y H:i:s') . ' you got {$left_queries} queries available';
        file_put_contents($log_path, $log_str);

        return $left_queries;
    }

    /**
     * Записываем данные $data в кеш с ключом $key
     * 
     * @param $data
     * @param $key
     * @return void
     */
    private function saveToCache($data, $key)
    {
        // достаём массив из кеша
        $morpher_data = $this->getAllDataFromCache();

        $morpher_data[$key] = $data;

        // готовим json
        $json = json_encode($morpher_data, JSON_UNESCAPED_UNICODE);

        // записываем контент в файл
        file_put_contents($this->cache_path, $json);
    }

    /**
     * Достаём из кеша данные сущности по ключу $key
     * 
     * @param $key
     * @return mixed|null
     */
    public function getFromCache($key)
    {
        // если кеш файл не создан отдаём null
        if (!file_exists($this->cache_path)) {
            return null;
        }

        // получаем данные из кеш-файл
        $data = file_get_contents($this->cache_path);
        if (!trim($data)) {
            return null;
        }

        $json = json_decode($data, true);
        if ($json === null) {
            return null;
        }

        // достаём контент и возвращаем
        if (!isset($json[$key])) {
            return null;
        }

        return $json[$key];
    }

    /**
     * Отправляем запрос в веб-сервис ws3.morpher.ru и, в дополнение, кешируем ответ
     * 
     * @param $keyword
     * @return array|false|mixed|string|string[]|null
     */
    public function getCasesFromMorpher($keyword)
    {
        // проверяем лимит запросов
        $left_queries = $this->leftDayQueries();

        // если лимит запросов исчерпан - отдаём ключевую фразу без изменений
        if (!$left_queries) {
            return $keyword;
        }

        // приводим ключевую фразу к нижнему регистру
        $keyword = mb_strtolower($keyword, 'UTF-8');

        // адрес веб-сервиса для склонения фразы по падежам
        $morpher_url = $this->morpher_url . '/russian/declension';
        // добавляем фразу для склонения к урлу
        $morpher_url .= '?s=' . urlencode($keyword);
        // добавляем вывод в json
        $morpher_url .= '&format=json';

        // получаем ответ от сервера
        $result = json_decode(file_get_contents($morpher_url), true);

        // если результат пуст - отдаём фразу без изменений
        if (!is_array($result) || empty($result)) {
            return $keyword;
        }

        // добавляем к результату именительный падеж
        $result['И'] = $keyword;

        // сохраняем результат в кеш
        $this->saveToCache($result, $keyword);

        return $result;
    }

    /**
     * Получение массива склонений по падежам фразы $keyword
     * 
     * @param $keyword
     * @param $padezh
     * @return array|false|mixed|string|string[]|null
     */
    public function getCase($keyword, $padezh = 'IM')
    {
        $keyword = mb_strtolower($keyword, 'UTF-8');

        // проверяем кеш на наличие склонений фразы
        $cases = $this->getFromCache($keyword);

        if (!$cases) {
            $cases = $this->getCasesFromMorpher($keyword);
        }

        // если $cases не является массивом, то отдаём ключевую фразу без изменений
        if (!is_array($cases)) {
            return $keyword;
        }

        // если нужно искать множественное склонение
        if(mb_strpos($padezh, 'M_', 0, 'UTF-8') === 0) {
            $cases = $cases['множественное'];
            $padezh = mb_substr($padezh, 2, null, 'UTF-8');
        }

        // нужный ключ
        $key = $this->padezhi[$padezh];

        if (!isset($cases[$key])) {
            return $keyword;
        }

        return $cases[$key];
    }
}