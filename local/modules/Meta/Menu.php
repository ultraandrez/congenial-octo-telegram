<?php

namespace Local\Seo\Meta;

use \Bitrix\Main\EventManager;
use \Bitrix\Main\Localization\Loc;
use Local\Router\Ad;

/*
 * Класс для работы с меню
 */
class Menu
{
    /**
     * Инициализация добавления пункта меню
     *
     * @example \Local\Seo\Sitemap\Menu::init();
     *
     * @return void
     */
    public static function init()
    {
        EventManager::getInstance()->addEventHandler(
            'main', 'OnBuildGlobalMenu',
            array( static::class, 'initMenu'
            )
        );
    }

    /**
     * Обработчик построения меню
     *
     * @example \Local\Seo\Sitemap\Menu::initMenu();
     *
     * @return void
     */
    public static function initMenu(&$adminMenu, &$moduleMenu)
    {
        Loc::loadMessages(__FILE__);

        $moduleMenu[] = array(
            'parent_menu' => 'global_menu_marketing',
            'section' => 'seo_meta',
            'sort' => 2100,
            'text' => Loc::getMessage('SEO_META_MENU_TITLE'),
            'title' => Loc::getMessage('SEO_META_MENU_TITLE'),
            'icon' => 'blog_menu_icon',
            'page_icon' => 'blog_menu_icon',
            'items_id' => 'menu_seo_meta',
            'items' => array(
                array(
                    'text' => Loc::getMessage('SEO_META_ADS_MENU_TITLE'),
                    'title' => Loc::getMessage('SEO_META_ADS_MENU_TITLE'),
                    'url' => 'custom_admin.php?d=seo/meta/ads&f=list',
                    'more_url' => array(
                        'custom_admin.php?d=seo/meta/ads&f=page',
                        'custom_admin.php?d=seo/meta/ads&f=condition',
                    )
                ),
                array(
                    'text' => Loc::getMessage('SEO_META_BREEDS_MENU_TITLE'),
                    'title' => Loc::getMessage('SEO_META_BREEDS_MENU_TITLE'),
                    'url' => 'custom_admin.php?d=seo/meta/breeds&f=list',
                    'more_url' => array(
                        'custom_admin.php?d=seo/meta/breeds&f=page',
                        'custom_admin.php?d=seo/meta/breeds&f=condition',
                    )
                ),
                array(
                    'text' => Loc::getMessage('SEO_META_PET_TYPE_MENU_TITLE'),
                    'title' => Loc::getMessage('SEO_META_PET_TYPE_MENU_TITLE'),
                    'url' => 'custom_admin.php?d=seo/meta/pettypes&f=list',
                    'more_url' => array(
                        'custom_admin.php?d=seo/meta/pettypes&f=page',
                        'custom_admin.php?d=seo/meta/pettypes&f=condition',
                    )
                ),
                array(
                    'text' => Loc::getMessage('SEO_META_SELLER_MENU_TITLE'),
                    'title' => Loc::getMessage('SEO_META_SELLER_MENU_TITLE'),
                    'url' => 'custom_admin.php?d=seo/meta/sellers&f=list',
                    'more_url' => array(
                        'custom_admin.php?d=seo/meta/sellers&f=page',
                        'custom_admin.php?d=seo/meta/sellers&f=condition',
                    )
                ),
                array(
                    'text' => Loc::getMessage('SEO_META_SETTINGS_MENU_TITLE'),
                    'title' => Loc::getMessage('SEO_META_SETTINGS_MENU_TITLE'),
                    'url' => 'custom_admin.php?d=seo/meta&f=settings',
                ),
            ),
        );

        return;
    }
}
