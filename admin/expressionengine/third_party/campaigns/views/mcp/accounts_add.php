<div class="container-fluid" id="accounts_add">

<div class="row pagehead">
    <div class="col-xs-6 ">
        <?php if ($id):?>
		<h2><?=lang('c:accounts:update')?></h2>
		<?php else:?>
		<h2><?=lang('c:accounts:add')?></h2>
		<?php endif;?>
    </div>
</div>

<?=form_open($base_url_short.AMP.'method=accounts_update')?>
<?=form_hidden('id', $id);?>

<div class="row">
    <div class="col-xs-6">
        <div class="dtable">
            <h2><?=lang('c:account')?></h2>
            <table>
            <tr>
            	<td style="width: 200px"><?=lang('c:label')?></td>
                <td><input name="label" id="a_label" type="text" class="text stringtoslug-source" value="<?=$label?>"></td>
            </tr>
            <tr>
            	<td><?=lang('c:handle')?></td>
                <td><input name="handle" type="text" class="text stringtoslug-dest" value="<?=$handle?>"></td>
            </tr>
            </table>
        </div>

        <div class="dtable">
            <h2><?=lang('c:service')?></h2>
            <table>
            <tbody>
	            <tr>
	            	<td colpan="2" id="accounts_toggle">
	            		<div class="emails">
							<label><input type="radio" name="service" value="mailchimp" <?php if ($service == 'mailchimp' || !$service):?>checked<?php endif;?> >&nbsp;&nbsp;Mailchimp</label>
							<label><input type="radio" name="service" value="campaignmonitor" <?php if ($service == 'campaignmonitor'):?>checked<?php endif;?> >&nbsp;&nbsp;Campaign Monitor</label>
							<label><input type="radio" name="service" value="constantcontact" <?php if ($service == 'constantcontact'):?>checked<?php endif;?> >&nbsp;&nbsp;Constant Contact</label>
							<label><input type="radio" name="service" value="benchmarkemail" <?php if ($service == 'benchmarkemail'):?>checked<?php endif;?> >&nbsp;&nbsp;Benchmark Email</label>
							<!--
							<input type="radio" name="service" value="aweber" <?php if ($service == 'aweber'):?>checked<?php endif;?> >&nbsp;&nbsp;AWeber &nbsp;
							<input type="radio" name="service" value="getresponse" <?php if ($service == 'getresponse'):?>checked<?php endif;?> >&nbsp;&nbsp;GetResponse &nbsp;
							-->
						</div>

						<div class="social">
							<label><input type="radio" name="service" value="twitter" <?php if ($service == 'twitter'):?>checked<?php endif;?> >&nbsp;&nbsp;Twitter</label>
							<label><input type="radio" name="service" value="facebook" <?php if ($service == 'facebook'):?>checked<?php endif;?> >&nbsp;&nbsp;Facebook</label>
							<label><input type="radio" name="service" value="linkedin" <?php if ($service == 'linkedin'):?>checked<?php endif;?> >&nbsp;&nbsp;LinkedIn</label>
							<label><input type="radio" name="service" value="tumblr" <?php if ($service == 'tumblr'):?>checked<?php endif;?> >&nbsp;&nbsp;Tumblr</label>
							<label><input type="radio" name="service" value="googleplus" <?php if ($service == 'googleplus'):?>checked<?php endif;?> >&nbsp;&nbsp;Google+ (experimental)</label>
						</div>
	            	</td>
	            </tr>
            </tbody>
            </table>
        </div>
    </div>
    <div class="col-xs-6" id="accounts_holder">

    </div>
</div>

<div class="row pagebottom">
    <button class="btn btn-green"><?=lang('c:save')?></button>
    <?php if ($id):?>
	<a href="<?=$base_url?>&method=accounts_update&delete=yes&id=<?=$id?>" class="btn btn-red" style="float:right"><?=lang('c:del')?></a>
	<?php endif;?>
</div>

<script type="text/x-subs-json" class="json"><?=$json?></script>

<?=form_close()?>

</div> <!-- #accounts_add -->