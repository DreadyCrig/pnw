<?php if ($lnError):?>

<strong>LinkedIn Error:</strong> <?=$lnError?><br>
<strong>NOTE:</strong> If this was just sent then the above error is normal, LinkedIn takes a while to process. Refresh after a couple of seconds.
<?php else:?>

<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <span class="right">
                <strong>Likes:</strong> <?=$item['likes']?><br>
                <strong>Comments:</strong> <?=$item['comments']?>
            </span>
            <strong>Sent On: </strong><?=$this->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i', strtotime($campaign->scheduled_at))?><br>
            <strong>From Account: </strong> <a href="<?=$item['creatorUrl']?>" target="_blank"><?=$item['creator']?></a><br>
            <strong>Link: </strong> <a href="<?=$item['link']?>" target="_blank"><?=$item['link']?></a><br>
        </div>
    </div>

    <div class="col-xs-6">
        <div class="stats" style="padding: 10px">
            <strong>Message:</strong><br>
            <?=$item['summary']?>
        </div>
    </div>
</div>

<?php endif;?>