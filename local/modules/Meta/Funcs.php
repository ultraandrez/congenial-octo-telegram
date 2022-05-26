<?php
namespace Local\Seo\Meta;

use \Local\Helper\Morpher;

/**
 * Класс с функциями макросов
 */
class Funcs
{
    public static function getDecl($declination, $subject) {
        $availDeclinations = [
            "IMEN", "ROD", "DAT", "VIN", "TVOR", "PRED",
            "M_IMEN", "M_ROD", "M_DAT", "M_VIN", "M_TVOR", "M_PRED"
        ];

        if(!in_array($declination, $availDeclinations)) {
            return $subject;
        }

        $morpher = new Morpher;
        $resultWord = $morpher->getCase($subject, $declination);
        if(!$resultWord) {
            return $subject;
        }
        unset($morpher);

        return $resultWord;
    }
}