<div class="container-fluid" id="dashboard">

<div class="row pagehead">
    <div class="col-xs-6 ">
        <h2><?=lang('campaigns')?></h2>
    </div>
</div>


<div class="empty">
    <h3>Coming soon</h3>
    <p>We are working on this section</p>
</div>


</div> <!-- #accounts -->


<!--
<div class="pagehead">
	<h2><?=lang('campaigns')?></h2>
</div>

<section class="pagebody">

<?php if (empty($campaigns)):?>
<div class="empty">
    <h3><?=lang('c:campaigns:none')?></h3>
    <p><?=lang('c:campaigns:exp')?></p>
</div>
<?php else:?>

<div class="dtable">
<table cellpadding="0" cellspacing="0">
<thead>
	<tr>
		<th>Label</th>
		<th>Provider</th>
		<th>Scheduled On</th>
	</tr>
</thead>
<tbody>
<?php foreach ($campaigns as $count => $cm):?>
<tr>
	<td><a href="#" data-id="<?=$cm->id?>" class="open_campaign"><?=$cm->label?></a></td>
	<td><?=lang('c:'.$cm->provider)?></td>
	<td><?=$this->campaigns_helper->formatDate('%d-%M-%Y - %H:%i', strtotime($cm->scheduled_on))?></td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>

<?php endif;?>

<div class="modalwrapper modal hide fade">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Campaign Details</h3>
	</div>

	<div class="modal-body">
		<p>loading...</p>
	</div>

	<div class="modal-footer">
		<div class="delete_campaign">
			<a href="#"><small>Delete this campaign</small></a>
			<span class="loading" style="display:none">deleting the campaign...</span>
		</div>
		<a href="#" class="btn" data-dismiss="modal">Close</a>
	</div>
</div>

</section>
-->