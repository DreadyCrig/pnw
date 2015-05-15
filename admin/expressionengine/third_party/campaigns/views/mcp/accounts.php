<div class="container-fluid" id="accounts">

<div class="row pagehead">
    <div class="col-xs-6 ">
        <h2><?=lang('c:accounts')?></h2>
    </div>
    <div class="col-xs-6">
    <a href="<?=$base_url?>&method=accounts_add" class="btn btn-green right"><?=lang('c:accounts:add')?></a>
    </div>
</div>

<br>
<div class="accounts">
    <?php foreach ($accounts as $count => $row): ?>
    <div class="cblock js-not_checked" data-id="<?=$row->id?>">

        <div class="line line-dark title">
            <label><?=$row->label?></label>
            <a href="<?=$base_url?>&method=accounts_add&id=<?=$row->id?>" class="right"><?=lang('c:edit')?></a>
        </div>

        <div class="line"><?=$row->handle?></div>

        <?php if ($this->campaigns->settings['service_logo'] == 'yes'):?>
        <div class="line"><img src="<?=$theme_url?>img/services/<?=$row->service?>.png"></div>
        <?php endif;?>

        <div class="line test"><em><?=lang('c:test_queued')?></em></div>
        <!-- <div class="line hidden test-data"></div> -->
        <div class="line hidden test-error"></div>
    </div>

    <?php
    if (($count+1) % 3 == 0) {
        //echo '<br clear="all"><br clear="all">';
    }
    ?>


    <?php endforeach ?>
    <br clear="all">
</div>

<?php if (empty($accounts)):?>
<div class="empty">
    <h3><?=lang('c:accounts:none')?></h3>
    <p><?=lang('c:accounts:exp')?></p>
</div>
<?php endif;?>



</div> <!-- #accounts -->