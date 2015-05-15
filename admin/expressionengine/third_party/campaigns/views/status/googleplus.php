<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <strong>Sent On: </strong><?=$this->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i', strtotime($campaign->scheduled_at))?><br>
            <strong>Link: </strong> <a href="<?=$url?>" target="_blank"><?=$url?></a><br>
        </div>
    </div>
</div>