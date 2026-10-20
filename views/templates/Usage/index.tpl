<?php

use oat\tao\helpers\Template;

$mode = get_data('mode');
$title = $mode === 'delivery' ? __('Delivery source test usage') : __('Test delivery usage');
?>

<link rel="stylesheet" type="text/css" href="<?= Template::css('usage.css') ?>"/>
<?php if (!empty(get_data('rowActionsExtensionEnabled'))): ?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('usage-preview-grid.css', 'taoDeliverConnect') ?>"/>
<?php endif; ?>

<div class="usage-data-container">
    <h2><?= _dh($title) ?></h2>
    <div class="data-container-wrapper flex-container-full">
        <div class="grid-row">
            <div class="col-12">
                <div
                    class="usage-grid"
                    data-mode="<?= _dh((string) get_data('mode')) ?>"
                    data-uri="<?= _dh((string) get_data('uri')) ?>"
                    data-label="<?= _dh((string) get_data('label')) ?>"
                    data-row-actions-extension-enabled="<?= !empty(get_data('rowActionsExtensionEnabled')) ? '1' : '0' ?>"
                ></div>
            </div>
        </div>
    </div>
</div>

<?php
Template::inc('footer.tpl', 'tao');
?>
