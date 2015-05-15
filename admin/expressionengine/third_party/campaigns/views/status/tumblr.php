<div class="campaign-stats row">
    <div class="col-xs-6">
        <div class="summary">
            <span class="right">
                <strong>Type:</strong> <?=$dbData->type?><br>
            </span>
            <strong>Sent On: </strong><?=$this->campaigns_helper->formatDate('%D, %F %d, %Y - %g:%i', strtotime($campaign->scheduled_at))?><br>
            <strong>Link: </strong> <a href="<?=$dbData->post_url?>" target="_blank"><?=$dbData->post_url?></a><br>
        </div>
    </div>

    <?php if ($dbData->type == 'text'):?>
    <div class="col-xs-6">
        <div class="stats" style="padding: 10px">
            <strong>Body:</strong><br>
            <?=$dbData->body?>
        </div>
    </div>
    <?php endif;?>

    <?php if ($dbData->type == 'link'):?>
    <div class="col-xs-6">
        <div class="stats" style="padding: 10px">
            <strong>Description:</strong><br>
            <?=$dbData->description?>
        </div>
    </div>
    <?php endif;?>
</div>