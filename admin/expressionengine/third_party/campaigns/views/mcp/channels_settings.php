<?=form_open($base_url.'&method=channels_update')?>
<?=form_hidden('channel_id', $channel->channel_id);?>

<div class="dtable">
    <h2><?=$channel->channel_title?></h2>
    <table>
    <tr>
        <td><?=lang('c:enabled')?></td>
        <td>
            <label><input type="radio" name="campaigns[enabled]" value="yes" <?php if ($settings['enabled'] == 'yes'):?>checked<?php endif;?> > <?=lang('c:yes')?></label>&nbsp;
            <label><input type="radio" name="campaigns[enabled]" value="no" <?php if ($settings['enabled'] == 'no'):?>checked<?php endif;?> > <?=lang('c:no')?></label>
        </td>
    </tr>
    </table>
</div>


<div class="csettings channelsettingsui" data-channel_id="<?=$channel->channel_id?>" field_id=""><div class="loading"><?=lang('c:loading_settings')?></div></div>
<script type="text/x-campaigns" class="channelsettings"><?=ee()->campaigns_helper->generateJson($settings)?></script>

<div class="row pagebottom">
    <button class="btn btn-green"><?=lang('c:save')?></button>
</div>

<?=form_close()?>