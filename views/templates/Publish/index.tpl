<?php
use oat\tao\helpers\Template;
?>
<link rel="stylesheet" type="text/css" href="<?= Template::css('selector.css')?>"/>
<div class="selector-container" data-root-class="<?=get_data('rootClassUri')?>" data-test="<?=get_data('testUri')?>">
</div>
<?php
Template::inc('footer.tpl', 'tao');
?>