<?php

namespace Local\Helper;

class RegexReplace
{
    /**
     * Заменяет вхождения номера в текст
     *
     * @param string $sReplacement
     * @param string $sSubject
     * @param int $limit
     * @param null $count
     *
     * @return array|string|string[]|null
     *
     * @example Local\Helper\RegexReplace::replaceNumbers('', $string);
     */
    public static function replaceNumbers(string $sReplacement, string $sSubject, int $limit = -1, &$count = null) {
        $sFindPhoneNumbersPattern = '/(?<=^|\s|>|;|:|\))(?:\+|7|8|9|\()[\d\-() .оОoO]{8,20}\d/u';
        return preg_replace($sFindPhoneNumbersPattern, $sReplacement, $sSubject, $limit, $count);
    }
    
    /**
     * Заменяет вхождения ссылок в текст
     *
     * @param string $sReplacement
     * @param string $sSubject
     * @param int $limit
     * @param null $count
     *
     * @return array|string|string[]|null
     *
     * @example Local\Helper\RegexReplace::replaceLinks('', $string);
     */
    public static function replaceLinks(string $sReplacement, string $sSubject, int $limit = -1, &$count = null) {
        $sFindLinksPattern = '/((http|https|ftp|ftps):\/\/)?[a-zA-Zа-яА-Я0-9\-.]+\.[a-zA-Zа-яА-Я]{2,8}(\/\S*)?/u';
        return preg_replace($sFindLinksPattern, $sReplacement, $sSubject, $limit, $count);
    }
}
