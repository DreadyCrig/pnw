<?php if ($fbError):?>

<strong>Facebook Error:</strong> <?=$fbError?><br>
<strong>Link: </strong> <a href="https://www.facebook.com/<?=$campaign->service_cid?>" target="_blank">https://www.facebook.com/<?=$campaign->service_cid?></a><br>

<?php else:?>

<?php if ($fbStats):?>

<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <span class="right">
                <strong>Likes:</strong> <?=$fbStats->like_info->like_count?><br>
                <strong>Comments:</strong> <?=$fbStats->comment_info->comment_count?>
            </span>
            <?php endif;?>
            <strong>Sent On: </strong><?=$this->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i', strtotime($campaign->scheduled_at))?><br>
            <strong>From Account: </strong> <a href="https://www.facebook.com/<?=$fbData->from->id?>" target="_blank"><?=$fbData->from->name?></a><br>
            <strong>Link: </strong> <a href="https://www.facebook.com/<?=$fbData->id?>" target="_blank">https://www.facebook.com/<?=$fbData->id?></a><br>
        </div>
    </div>

    <div class="col-xs-6">
        <div class="stats" style="padding: 10px">
            <strong>Message:</strong><br>
            <?=$fbData->message?>
        </div>
    </div>
</div>

<?php endif;?>