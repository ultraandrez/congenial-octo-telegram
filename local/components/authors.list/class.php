<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

use Site\Converter\Process\Tools\PictConverter;
use Bitrix\Main\UI\PageNavigation;
use Local\Article\Authors;
use Bitrix\Main\Localization\Loc;
use Partner\Helpers\StringHelper;
use Local\Helper\LetterPicture;

class AuthorsListComponentClass extends \CBitrixComponent
{
    private $aAuthorsFilter = [];
	protected $arFilter;
    
	public $arResult;
	public $arParams;
	

	/**
	 * Подготовка параметров компонента
	 *
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams): array
    {
		if (!$arParams['FILTER_NAME']) {
			$arParams['FILTER_NAME'] = 'arrFilter';
		}

		if (!$arParams['COUNT_PAGE']) {
			$arParams['COUNT_PAGE'] = 30;
		}

		return $arParams;
	}
    
	/**
	 * Инициализация фильтра
	 */
	private function initFilter()
	{
		$filterName = $this->arParams['FILTER_NAME'];
		global ${$filterName};
		$this->arFilter = (${$filterName} ?: []);

		$aParamsFilter = [];
        
        if ($this->arFilter['NAME']) {
            $aParamsFilter['NAME'] =  '%' . $this->arFilter['NAME'] . '%';
        }
        if ($this->arFilter['ROLE']) {
            $aParamsFilter['=PR_ROLE_XML_ID'] = $this->arFilter['ROLE'];
        }
        if ($this->arFilter['SPECIALIZATION']) {
            $aParamsFilter['=PR_SPECIALIZATION_ID'] = $this->arFilter['SPECIALIZATION'];
        }
        if ($this->arFilter['EXPERIENCE']) {
            $aParamsFilter['<=PR_EXPERIENCE'] = Authors::getExperienceValues()[$this->arFilter['EXPERIENCE']]['FILTER_VALUE'];
        }
		
		$this->aAuthorsFilter = $aParamsFilter;
	}
    
    /**
     * Инициализация результата
     * 
     * @return void
     */
	public function initResult()
	{
        \Bitrix\Main\Loader::includeModule('company.converter');
        $this->arResult['CNT'] = Authors::getCount($this->aAuthorsFilter);
        
        // форматируем кол-во специалистов
        $aWordList = [
            Loc::getMessage('AUTHORS_CNT_1'),
            Loc::getMessage('AUTHORS_CNT_2'),
            Loc::getMessage('AUTHORS_CNT_5'),
        ];
        $sCntTitle = StringHelper::inclineWord($this->arResult['CNT'], $aWordList);
        $this->arResult['CNT_FORMATTED'] = $this->arResult['CNT'] . ' ' . $sCntTitle;
        
		// инициализируем пагинацию
		if ($this->arParams['PAGER_TEMPLATE']) {
			$nav = new PageNavigation('nav');
			$nav->allowAllRecords(false)
				->setPageSize($this->arParams['COUNT_PAGE'])
				->initFromUri();
			$nav->setRecordCount($this->arResult['CNT']);
		}
		$this->arResult['NAV'] = $nav;

		$limit = $nav ? $nav->getLimit() : $this->arParams['COUNT_PAGE'];
		$offset = $nav ? $nav->getOffset() : 0;

        //собираем сортировку
		$aSort = [
			$this->arParams['SORT'] => $this->arParams['ORDER'],
			$this->arParams['SORT1'] => $this->arParams['ORDER1'],
		];
		if (!empty($this->arParams['SORT2'])) {
			$aSort = array_merge($aSort, [$this->arParams['SORT2'] => $this->arParams['ORDER2']]);
		}
        
        $aFilter = array_merge($this->aAuthorsFilter, ['=ACTIVE' => 'Y']);
        
        $aAuthorsList = Authors::getList($aFilter, $aSort, $limit, $offset);
        
        foreach ($aAuthorsList as &$aAuthor) {
            $sPictureSrc = \CFile::ResizeImageGet($aAuthor['PREVIEW_PICTURE'], ['width' => 236, 'height' => 233], BX_RESIZE_IMAGE_EXACT)['src'] ?? LetterPicture::create($aAuthor['NAME']);
            $aAuthor['PREVIEW_PICTURE'] = [
                'SRC' => $sPictureSrc,
                'WEBP_SRC' => PictConverter::getWebpSrc($sPictureSrc)
            ];
            $aAuthor['URL'] = \Local\Router\Authors::formElementUrl($aAuthor);
        }
        $this->arResult['ITEMS'] = $aAuthorsList;
	}
    
    /**
     * Выполнение компонента
     * 
     * @return mixed|null
     */
	public function executeComponent()
	{
		$this->initFilter();
		$this->initResult();
		$this->includeComponentTemplate();

		return $this->arResult;
	}
}