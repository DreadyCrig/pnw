<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <span class="right">
                <strong>Date Created:</strong> <?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $campaign->created_at);?><br>
                <strong>Date Scheduled:</strong> <?=$this->campaigns_helper->formatDate('%d-%M-%Y - %g:%i', $campaign->scheduled_at);?><br>
            </span>
            <!--<strong>Status:</strong> <?=$apiData['status']?> (API)<br>-->
            <strong>Web URL:</strong> <a href="<?=$apiData['web_url']?>" target="_blank">HTML</a> | <a href="<?=$apiData['web_url_text']?>" target="_blank">Text</a>
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