<div class="container-fluid" id="campchannels">

<div class="row pagehead">
    <div class="col-xs-6 ">
        <h2><?=lang('c:channels')?></h2>
    </div>
</div>

<div class="row">
    <div class="col-xs-3">
    	<div class="dtable channel_list">
            <h2><?=lang('c:channels')?></h2>
            <table>
            <?php foreach ($channels as $id => $channel): ?>
            <tr>
            	<td class="channel" data-id="<?=$channel['id']?>">
	            	<?php if ($channel['enabled'] == 'yes'):?>
					<span class="status status-enabled"><?=$channel['label']?></span>
					<?php else:?>
					<span class="status status-disabled"><?=$channel['label']?></span>
					<?php endif;?>
					<!--<a href="<?=$base_url?>&method=channels_settings&id=<?=$id?>"><?=$channel['label']?></a>-->
            	</td>
            </tr>
            <?php endforeach ?>
            </table>
        </div>
    </div>

    <div class="col-xs-9 settingswrapper">
		<div class="loading-empty empty">
			<h3><?=lang('c:channels:select')?></h3>
			<p><?=lang('c:channels:select_exp')?></p>
		</div>
        <div class="loading-settings empty">
            <p class="loading" style="display:inline"><?=lang('c:loading_settings')?></p>
        </div>
        <div class="loading-error empty"></div>

        <div class="settingsinner"></div>
    </div>
</div>

</div> <!-- #campchannels -->