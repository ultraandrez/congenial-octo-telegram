<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

?>
<div class="mob-filter-item">
    <span class="icon-search"><?=Loc::getMessage('AUTHORS_FILTER_FILTERS')?></span>
</div>
<?php $this->SetViewTarget('adFilterBody'); ?>
<div class="side-search">
    <div class="close-filter js-close"></div>
    <span class="side-title"><?=Loc::getMessage('AUTHORS_FILTER_FIND')?></span>
    <span class="clear-filt"><?=Loc::getMessage('AUTHORS_FILTER_RESET')?></span>

    <form class="side-form side-filter-form author-side-form" method="get" id="side-form" name="side-form" data-ajax-action="<?=$arResult['AJAX_URL']?>" data-url="<?=$arResult['URL']?>">

        <div class="form-item">
            <label class="form-item-label author-suggest-wrap">
                <span><?=Loc::getMessage('AUTHORS_FILTER_NAME')?></span>
                <input class="form-item-input form-item-input-name <?=$arResult['CURRENT_NAME'] ? ' selected-input' : ''?>" name="NAME" type="text" autocomplete="off" value="<?=$arResult['CURRENT_NAME']?>" data-value="<?=$arResult['CURRENT_NAME']?>" placeholder="<?=Loc::getMessage('AUTHORS_FILTER_NAME')?>">
                <ul class="author-suggest" data-simplebar>
                </ul>
            </label>
        </div>

        <div class="form-item">
            <label class="form-item-label">
                <span><?=Loc::getMessage('AUTHORS_FILTER_ROLE')?></span>
                <select name="ROLE" class="sidebar-select slc-author">
                    <option></option>
                    <option value="0"><?=Loc::getMessage('AUTHOR_FILTER_ANY')?></option>
                    <?php foreach ($arResult['ROLES'] as $iKey => $aRole) { ?>
                        <option value="<?=$aRole['XML_ID']?>" <?=($aRole['SELECTED'] ? 'selected' : '')?>><?=$aRole['VALUE']?></option>
                    <?php } ?>
                </select>
            </label>
        </div>

        <div class="form-item">
            <label class="form-item-label">
                <span><?=Loc::getMessage('AUTHORS_FILTER_SPECIALIZATION')?></span>
                <select name="SPECIALIZATION" class="sidebar-select slc-spec">
                    <option></option>
                    <option value="0"><?=Loc::getMessage('AUTHOR_FILTER_ANY')?></option>
                    <?php foreach ($arResult['SPECIALIZATION'] as $iKey => $aSpecialization) { ?>
                        <option value="<?=$aSpecialization['ID']?>" <?=($aSpecialization['SELECTED'] ? 'selected' : '')?>><?=$aSpecialization['UF_NAME']?></option>
                    <?php } ?>
                </select>
            </label>
        </div>

        <div class="form-item">
            <label class="form-item-label">
                <span><?=Loc::getMessage('AUTHORS_FILTER_EXPERIENCE')?></span>
                <select name="EXPERIENCE" class="sidebar-select slc-exp">
                    <option></option>
                    <option value="0"><?=Loc::getMessage('AUTHOR_FILTER_ANY')?></option>
                    <?php foreach ($arResult['EXPERIENCE'] as $iKey => $aExperience) { ?>
                        <option value="<?=$iKey?>" <?=($aExperience['SELECTED'] ? 'selected' : '')?>><?=$aExperience['LABEL']?></option>
                    <?php } ?>
                </select>
            </label>
        </div>

        <button type="submit" class="button btn-black icon-search"><?=Loc::getMessage('AUTHORS_FILTER_SEARCH')?></button>
    </form>
</div>
<?php $this->EndViewTarget(); ?>
