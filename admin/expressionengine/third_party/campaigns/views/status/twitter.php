<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <?php if (!$twError):?>
            <span class="right">
                <strong>Retweets:</strong> <?=$twData->retweet_count?><br>
                <strong>Favorites:</strong> <?=$twData->favorite_count?>
            </span>
            <?php else:?>
            <strong class="error">Twitter Error:</strong> <?=$twError?>

            <br><br><strong>(Cached Data)</strong><br>
            <?php endif;?>

            <strong>Sent On: </strong><?=$this->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i %A', strtotime($campaign->scheduled_at))?><br>
            <strong>From Account: </strong> <a href="https://twitter.com/<?=$dbData->user->screen_name?>" target="_blank"><?=$dbData->user->name?> (@<?=$dbData->user->screen_name?>)</a><br>
            <strong>Link: </strong> <a href="https://twitter.com/<?=$dbData->user->screen_name?>/status/<?=$dbData->id_str?>" target="_blank">https://twitter.com/<?=$dbData->user->screen_name?>/status/<?=$dbData->id_str?></a><br>
        </div>
    </div>

    <div class="col-xs-6">
        <div class="stats" style="padding: 10px;">
            <strong>Tweet:</strong><br>
            <?=$dbData->text?>
        </div>
    </div>
</div>