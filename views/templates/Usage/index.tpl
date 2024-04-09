<?php
use oat\tao\helpers\Template;
?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('selector.css')?>"/>
<link rel="stylesheet" type="text/css" href="<?= Template::css('usage.css')?>"/>
<div class="usage-tabs-container">
    <h2><?= __('Item usage')?></h2>
    <div class="tab-container">
        <nav class="tab-selector"></nav>
        <div data-tab-content="tao-tests" data-tab-label="Tests">
            <div class="data-container-wrapper flex-container-full">
                <div class="grid-row">
                    <div class="col-12">
                        <div class="usage-tests-container"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hidden" data-tab-content="tao-sessions" data-tab-label="Sessions">
            <div class="data-container-wrapper flex-container-full">
                <div class="grid-row">
                    <div class="col-12">
                        <div class="usage-sessions-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="selector-container" data-root-class="<?=get_data('rootClassUri')?>" data-test="<?=get_data('testUri')?>" data-label="<?=_dh(get_data('label'))?>">
    </div>
</div>
<?php
Template::inc('footer.tpl', 'tao');
?>