<div class="campaign-stats row">
	<div class="col-xs-6">
		<div class="summary">
			<strong>Email Subject:</strong> <?=$apiData['email_subject']?><br clear="all">
			<span class="right">
				<strong>Date Created:</strong> <?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $apiData['date_created']);?> (API) <br>
				<strong>Date Scheduled:</strong> <?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $apiData['date_scheduled']);?> (API) <br>
			</span>
			<strong>Status:</strong> <?=$apiData['status']?> (API)<br>
			<strong>Web URL:</strong> <a href="<?=$apiData['web_url']?>" target="_blank">Link</a>
		</div>
	</div>

	<div class="col-xs-6">
		<div class="stats">
			<ul>
				<?php foreach ($apiData['stats'] as $key => $val):?>
				<li>
					<label><?=lang('c:'.$key)?>: </label>
					<strong><?=$val?></strong>
				</li>
				<?php endforeach;?>
			</ul>
		</div>
	</div>
</div>