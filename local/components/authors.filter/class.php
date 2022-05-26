<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Local\Article\Authors;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;
use Local\Catalog\Ad\Specialization;

class AuthorsFilterComponentClass extends \CBitrixComponent
{
    public $arResult;
    public $arParams;

    protected $filterName = '';
    protected $arFilter = [];
    protected $url = '';


    /**
     * Подготовка параметров компонента
     * 
     * @param array arParams - параметры компоеннта
     * @return array - массив измененных параметров
     */
    public function onPrepareComponentParams($arParams): array
    {
        if (!$arParams['FILTER_NAME']) {
            $arParams['FILTER_NAME'] = 'arrFilter';
        }

        return $arParams;
    }

    /**
     * Инициализация переменных
     * 
     * @example $this->initVars();
     */
    public function initVars()
    {
        $this->filterName = $this->arParams['FILTER_NAME'];
        global ${$this->filterName};
        $this->arFilter = (${$this->filterName} ?: []);

        $request = Application::getInstance()->getContext()->getRequest();
        $uri = new Uri($request->getRequestUri());
        $this->url = $uri->getPath();
    }

    /**
     * Применение параметров роутера к фильтру
     * 
     * @example $this->applyRouterParams();
     */
    public function applyRouterParams()
    {
        if (!$this->arParams['ROUTER_PARAMS']) {
            return;
        }

        foreach ($this->arParams['ROUTER_PARAMS'] as $key => $val) {
            $this->arFilter[$key] = $val;
        }
    }

    /**
     * Применение параметров запроса к фильтру
     * примененный фильтр приходит в get
     * 
     * @example $this->applyRequestParams();
     */
    public function applyRequestParams()
    {
        $request = Application::getInstance()->getContext()->getRequest();

        $arParams = [
            'NAME',
            'ROLE',
            'SPECIALIZATION',
            'EXPERIENCE'
        ];

        foreach ($arParams as $key) {
            if (!isset($request[$key])) {
                continue;
            }

            if ($val = $request->getQuery($key)) {
                $this->arFilter[$key] = $val;
            } else {
                unset($this->arFilter[$key]);
            }
        }
    }

    /**
     * Инициализация даных
     * 
     * @example $this->initResult();
     */
    public function initResult()
    {
        $this->arResult['AJAX_URL'] = $this->__path . '/ajax.php';
        $this->arResult['URL'] = $this->url;

        // роли авторов / экспертов
        $this->arResult['ROLES'] = PropertyEnumerationTable::getList([
            'select' => ['*'],
            'filter' => [
                '=ROLE.IBLOCK_ID' => Authors::IBLOCK_ID,
                '=ROLE.CODE' => 'ROLE',
            ],
            'runtime' => [
                new ReferenceField(
                    'ROLE',
                    '\Bitrix\Iblock\PropertyTable',
                    ['=this.PROPERTY_ID' => 'ref.ID']
                )
            ]
        ])->fetchAll();
        foreach ($this->arResult['ROLES'] as &$aRole) {
            if ($aRole['XML_ID'] == $this->arFilter['ROLE']) {
                $aRole['SELECTED'] = true;
            }
        }
        // специализации авторов / экспертов
        $this->arResult['SPECIALIZATION'] = Specialization::getListArray();
        if ($this->arFilter['SPECIALIZATION']) {
            $this->arResult['SPECIALIZATION'][$this->arFilter['SPECIALIZATION']]['SELECTED'] = true;
        }
        // опыт авторов / экспертов
        $this->arResult['EXPERIENCE'] = Authors::getExperienceValues();
        if ($this->arFilter['EXPERIENCE']) {
            $this->arResult['EXPERIENCE'][$this->arFilter['EXPERIENCE']]['SELECTED'] = true;
        }
        // имя автора / эксперта
        if ($this->arFilter['NAME']) {
            $this->arResult['CURRENT_NAME'] = $this->arFilter['NAME'];
        }
    }

    /**
     * Обработка запроса поиска автора/эксперта
     * выбор авторов/экспертов с подсказками при вводе
     * 
     * @example AuthorsFilterComponentClass::processAuthorsSuggestRequest();
     */
    public static function processAuthorsSuggestRequest()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->getPost('action') != 'authorSuggest') {
            return;
        }

        Loc::loadMessages(__FILE__);
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');

        $arRes = array(
            'status' => 'error',
            'list' => null,
            'mess' => Loc::getMessage('AUTHORS_FILTER_UNDEFINED_ERROR')
        );

        try {
            $q = $request->getPost('q');
            if (!$q) {
                throw new Exception(Loc::getMessage('AUTHORS_FILTER_AUTHORS_SUGGEST_NO_QUERY'));
            }

            $q = str_replace(['-', '_', ' '], '%', $q);
            $oQueryResult = ElementTable::getList([
                'filter' => [
                    'NAME' => '%' . $q . '%',
                    '=IBLOCK_ID' => Authors::IBLOCK_ID
                ],
                'select' => [
                    'ID',
                    'NAME',
                ]
            ]);
            while ($aAuthor = $oQueryResult->fetch()) {
                $arRes['list'][] = [
                    'authorId' => $aAuthor['ID'],
                    'name' => $aAuthor['NAME']
                ];
            }

            $arRes['status'] = 'ok';
            $arRes['mess'] = '';

        } catch (Exception $e) {
            $arRes['mess'] = $e->getMessage();
        }

        echo json_encode($arRes);
        die();
    }

    /**
     * Выполнение компонента
     * 
     * @return void
     */
    public function executeComponent()
    {
        $this->initVars();
        $this->applyRouterParams();
        $this->applyRequestParams();
        $this->initResult();

        //делаем созданный фильтр глобальным
        global ${$this->filterName};
        ${$this->filterName} = $this->arFilter;

        $this->includeComponentTemplate();
    }
}
