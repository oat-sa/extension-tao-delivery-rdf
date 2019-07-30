<?php
use oat\tao\helpers\Template;
?>

<div class="delivery-headings flex-container-full">
    <header>
        <h2><?=_dh(get_data('label'))?></h2>
        <p>
            <span class="label"><?=__('Published on:') ?></span>
            <span><?= tao_helpers_Date::displayeDate(get_data('date')) ?></span>
            <?php if(has_data('updatedAt')) : ?>
                <span class="label"><?=__('Last updated on:') ?></span>
                <span><?= tao_helpers_Date::displayeDate(get_data('updatedAt')) ?></span>
            <?php endif; ?>
        </p>
        <p>
        <?php if(has_data('exec')):?>
            <?php if(get_data('exec') == 0):?>
                <?=__('No attempt has been started yet.')?>
            <?php elseif(get_data('exec') == 1) :?>
                <?=__('There is currently 1 attempt')?>.
            <?php else:?>
                <?=__('There are currently %s attempts', get_data('exec'))?>.
            <?php endif;?>
        <?php else:?>
            <?=__('No information available about attempts')?>.
        <?php endif;?>
        </p>
    </header>
    <div>
        <table id="history-list"></table>
        <div id="history-list-pager"></div>
    </div>
</div>

<header class="flex-container-full">
    <h3><?=get_data('formTitle')?></h3>
</header>
<div class="main-container flex-container-main-form">
    <div id="form-container">
        <?=get_data('myForm')?>
    </div>
</div>

<div class="data-container-wrapper flex-container-remainder">
    <?= get_data('groupTree')?>
    <?php Template::inc('widgets/excludeTesttaker.tpl');?>
    <?= has_data('campaign') ? get_data('campaign') : '';?>
</div>
<?php
Template::inc('footer.tpl', 'tao');
?>
