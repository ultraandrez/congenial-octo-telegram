<?php

namespace Local\Seo\Meta;

/**
 * Класс для работы с уточнениями правил по страницах
 */
class Condition extends \Local\AbstractHighloadWrapper
{
    public static $hlId = HIGHLOAD_SEO_META_CONDITIONS;
    
    /**
     * Формирование мета данных по условию
     * 
     * @param array $arCondition - данные условия
     * @param array $arValues - данные для мета-параметров
     * 
     * @return array - сформированные мета данные
     *
     * @example \Local\Seo\Meta\Condition::formMeta($arCondition);
     */
    public static function formMeta(array $arCondition, array $arValues): array
    {
        //формируем макросы
        $arMacroses = Macros::prepare($arValues);

        //подставляем и отдаем
    	return [
    	    'H1' => Macros::apply($arCondition['UF_H1'] ?: '', $arMacroses),
    	    'TITLE' => Macros::apply($arCondition['UF_TITLE'] ?: '', $arMacroses),
    	    'DESCRIPTION' => Macros::apply($arCondition['UF_DESCRIPTION'] ?: '', $arMacroses),
    	    'KEYWORDS' => Macros::apply($arCondition['UF_KEYWORDS'] ?: '', $arMacroses),
    	    'CHAIN' => Macros::apply($arCondition['UF_CHAIN'] ?: '', $arMacroses),
    	    'TOP_TEXT' => Macros::apply($arCondition['UF_TOP_TEXT'] ?: '', $arMacroses),
    	    'BOTTOM_TEXT' => Macros::apply($arCondition['UF_BOTTOM_TEXT'] ?: '', $arMacroses),
        ];
    }
}
