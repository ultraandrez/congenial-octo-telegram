<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$this->setFrameMode(true);
?>
    <div class="authors__list">
        <?php if ($arResult['ITEMS']) { ?>
            <?php foreach ($arResult['ITEMS'] as $aItem) { ?>
                <div class="author__item-outer">
                    <a href="<?= $aItem['URL'] ?>" class="author__item-link">
                        <div class="author__item">
                            <div class="author__item-wrapper">
                                <div class="author__avatar">
                                    <picture>
                                        <source data-srcset="<?= $aItem['PREVIEW_PICTURE']['WEBP_SRC'] ?>">
                                        <img class="author__img lazy" data-src="<?= $aItem['PREVIEW_PICTURE']['SRC'] ?>">
                                    </picture>
                                    <span class="author__label"><?= $aItem['PR_ROLE_NAME'] ?></span>
                                </div>
                                <div class="author__content">
                                    <h3 class="author__name"><?= $aItem['NAME'] ?></h3>
                                    <div class="author__tags">
                                        <span class="author__tag"><?= $aItem['PR_SPECIALIZATION_NAME'] ?></span>
                                    </div>
                                    <p class="author__desc"><?= $aItem['PREVIEW_TEXT'] ?></p>
                                </div>
                            </div>
                            <p class="author__desc-mobile"><?= $aItem['PREVIEW_TEXT'] ?></p>
                        </div>
                    </a>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="ajax-list">
                <div class="no-items-mess">
                    <span class="no-items-mess__title"><?=Loc::getMessage('NO_ITEMS')?></span>
                    <div class="no-items-mess__desc-none">
                        <p class="no-items-mess__description"><?=Loc::getMessage('NO_ITEMS_TRY_RESET')?></p>
                        <svg width="134" height="110" viewBox="0 0 134 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M82.5911 9.83161H3.38462C1.51546 9.83161 0 8.31615 0 6.44699C0 4.57784 1.51546 3.06238 3.38462 3.06238H82.5911C84.4603 3.06238 85.9757 4.57784 85.9757 6.44699C85.9757 8.31615 84.4603 9.83161 82.5911 9.83161Z" fill="#97A3AF"/>
                            <path d="M82.5911 32.9852H30.3309C28.4618 32.9852 26.9463 31.4697 26.9463 29.6006C26.9463 27.7314 28.4618 26.2159 30.3309 26.2159H82.5911C84.4602 26.2159 85.9757 27.7314 85.9757 29.6006C85.9757 31.4697 84.4602 32.9852 82.5911 32.9852Z" fill="#97A3AF"/>
                            <path d="M12.8223 32.9852H3.38462C1.51546 32.9852 0 31.4697 0 29.6006C0 27.7314 1.51546 26.2159 3.38462 26.2159H12.8223C14.6915 26.2159 16.2069 27.7314 16.2069 29.6006C16.2069 31.4697 14.6915 32.9852 12.8223 32.9852Z" fill="#97A3AF"/>
                            <path d="M82.5911 56.1391H3.38462C1.51546 56.1391 0 54.6236 0 52.7545C0 50.8853 1.51546 49.3699 3.38462 49.3699H82.5911C84.4603 49.3699 85.9757 50.8853 85.9757 52.7545C85.9757 54.6236 84.4603 56.1391 82.5911 56.1391Z" fill="#97A3AF"/>
                            <path d="M127.187 104.706C121.026 109.175 112.322 107.282 108.627 100.669L89.1348 65.7781L101.394 56.8851L128.952 85.9248C134.175 91.4283 133.348 100.237 127.187 104.706Z" fill="#97A3AF"/>
                            <path d="M109.526 22.4656C117.825 36.8353 112.901 55.2108 98.5255 63.508C84.1488 71.8059 65.7667 66.8809 57.4677 52.5101C49.1691 38.14 54.0931 19.7648 68.4684 11.4677C82.845 3.16972 101.227 8.09495 109.526 22.4656Z" fill="#F4F7F9" stroke="#CDD4D9" stroke-width="14"/>
                            <path d="M81.3319 40.196C81.3319 39.2253 81.5465 38.4227 81.9759 37.788C82.4239 37.1533 83.0679 36.4533 83.9079 35.688C84.5239 35.128 84.9719 34.6613 85.2519 34.288C85.5505 33.896 85.6999 33.4573 85.6999 32.972C85.6999 32.2813 85.4199 31.74 84.8599 31.348C84.3185 30.9373 83.5905 30.732 82.6759 30.732C81.7985 30.732 81.0145 30.9187 80.3239 31.292C79.6519 31.6467 79.0825 32.1507 78.6159 32.804L75.2279 30.816C76.0119 29.6213 77.0665 28.6973 78.3919 28.044C79.7359 27.3907 81.3225 27.064 83.1519 27.064C85.2985 27.064 87.0159 27.5213 88.3039 28.436C89.6105 29.3507 90.2639 30.62 90.2639 32.244C90.2639 33.0093 90.1332 33.6813 89.8719 34.26C89.6292 34.8387 89.3212 35.3333 88.9479 35.744C88.5932 36.136 88.1265 36.5933 87.5479 37.116C86.8572 37.732 86.3532 38.2547 86.0359 38.684C85.7185 39.0947 85.5599 39.5987 85.5599 40.196H81.3319ZM83.4599 47.224C82.6945 47.224 82.0505 46.9813 81.5279 46.496C81.0239 45.992 80.7719 45.3853 80.7719 44.676C80.7719 43.9667 81.0239 43.3787 81.5279 42.912C82.0319 42.4267 82.6759 42.184 83.4599 42.184C84.2439 42.184 84.8879 42.4267 85.3919 42.912C85.8959 43.3787 86.1479 43.9667 86.1479 44.676C86.1479 45.3853 85.8865 45.992 85.3639 46.496C84.8599 46.9813 84.2252 47.224 83.4599 47.224Z" fill="#97A3AF"/>
                        </svg>
                        <button type="button" class="no-items-mess__btn"><?=Loc::getMessage('NO_ITEMS_RESET_FILTER')?></button>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php if ($arResult['ITEMS']) { ?>
    <?php
    $APPLICATION->IncludeComponent(
        "bitrix:main.pagenavigation",
        'authors',
        [
            "NAV_OBJECT" => $arResult['NAV'],
            "SEF_MODE" => "N",
        ],
        null,
        [
            'HIDE_ICONS' => 'Y'
        ]
    );
    ?>
<?php } ?>
<?php $this->SetViewTarget('authorsCnt') ?>
    <span class="label yellow-bg">
    <img src="/local/templates/template2/images/star_tab.svg" width="14" height="14" alt="star">
    <span class="yellow-bg__text"><?= $arResult['CNT_FORMATTED'] ?></span>
</span>
<?php $this->EndViewTarget();
