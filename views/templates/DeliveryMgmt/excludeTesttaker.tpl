<?php
use oat\tao\helpers\Template;
?>
<div class="grid-container">
  <div class="grid-row">
    <div class="col-6">
    <h3><?= __('Assigned test-takers')?></h3>
    <div class="ttbox">
        <div class="filter-div">
            <input type="search" placeholder="filter" id="tt-filter">
          <span class="icon-find"></span>
        </div>
        <ul id="assignedList" class="none ttlist">
        <?php foreach (get_data('assigned') as $key => $label): ?>
          <li class="clearfix" data-uri="<?=$key?>">
            <?=_dh($label)?><span class="arrow"></span>
          </li>
        <?php endforeach;?>
        </ul>
    </div>
</div>
 
    <div class="col-6">
    <h3><?= __('Excluded test-takers')?></h3>
    <div class="ttbox">
        <input type="hidden" name="assemblyUri" value="<?= get_data('assemblyUri')?>" />
        <ul id="excludedList" class="none ttlist">
        <?php foreach (get_data('excluded') as $key => $label): ?>
          <li class="clearfix" data-uri="<?=$key?>">
            <?=_dh($label)?><span class="arrow"></span>
          </li>
        <?php endforeach;?>
        </ul>
    </div>
</div>
  </div>
</div>

<div class="txt-ctr">
<button id="close-tt" class="btn-info small" type="button" ><?=tao_helpers_Icon::iconClose().__('Cancel')?></button>
<button id="save-tt" class="btn-info small" type="button" ><?=tao_helpers_Icon::iconSave().__('Save')?></button>
</div>
