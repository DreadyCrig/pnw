<div class="block block-camp">
    <div class="block-dark block-top">Campaign</div>
</div>

<div class="service-wrapper">

    <?php foreach($socialServices as $service):?>
    <div class="block service service-<?=$service->type?>" id="service_<?=$service->id?>">
    <input name="services[<?=$service->id?>][id]" type="hidden" value="<?=$service->id?>">

    <!-- Yeah, i know table.. We hate them too -->
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr class="row row-dark">
            <th colspan="2" class="title"><strong><?=$service->label?></strong></th>
        </tr>
    </thead>
    <tbody>
        <tr class="row">
            <td style="width:50%">

            </td>
            <td><img src="<?=$service->themeUrl?>img/service_<?=$service->provider?>.png" class="right"></td>
        </tr>
        <tr class="row">
            <td class="deliver-toggle">
                <label><input name="services[<?=$service->id?>][deliver]" type="radio" value="now" <?php if ($service->deliver == 'now') echo 'checked';?> > Immediately</label><br>
                <label><input name="services[<?=$service->id?>][deliver]" type="radio" value="schedule" <?php if ($service->deliver == 'now') echo 'schedule';?> > Schedule</label>
            </td>
            <td class="deliver-wrap">

                <div class="deliver deliver-test">
                    <label>Email (separate multiple with a comma)</label>
                    <input type="text" class="text" name="services[<?=$service->id?>][test_emails]">
                </div>
                <div class="deliver deliver-now">
                    <label>Sending will immediately send the campaign.<br>You will not be able to cancel.</label>
                </div>
                <div class="deliver deliver-schedule">
                    <div class="line">
                        Date
                        <span class="right">
                            <select name="services[<?=$service->id?>][schedule][date][day]"><?=$schedule['date']['day']?></select>
                            <select name="services[<?=$service->id?>][schedule][date][month]"><?=$schedule['date']['month']?></select>
                            <select name="services[<?=$service->id?>][schedule][date][year]"><?=$schedule['date']['year']?></select>
                        </span>
                        <br clear="all">
                    </div>
                    <div class="line">
                        Time
                        <span class="right">
                            <select name="services[<?=$service->id?>][schedule][time][hour]"><?=$schedule['time']['hour']?></select>
                            <select name="services[<?=$service->id?>][schedule][time][minute]"><?=$schedule['time']['minute']?></select>
                            <select name="services[<?=$service->id?>][schedule][time][ampm]"><?=$schedule['time']['ampm']?></select>
                        </span>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="row row-dark">
            <td class="deliver-wrap">
                <button class="submit deliver deliver-test" data-deliver="test">Send Test Emails</button>
                <button class="submit deliver deliver-now" data-deliver="now">Send Campaign Now</button>
                <button class="submit deliver deliver-schedule" data-deliver="schedule">Schedule Campaign</button>
            </td>
            <td class="loading-wrapper">
                <p class="loading loading-test">sending test emails...</p>
                <p class="loading loading-now">sending campaign...</p>
                <p class="loading loading-schedule">scheduling campaign...</p>
                <p class="success success-test">Test Emails Sent!</p>
                <p class="success success-now">Campaign Sent!</p>
                <p class="success success-schedule">Campaign has been Scheduled</p>
            </td>
        </tr>
        <tr class="row errors">
            <td colspan="2">
                <ul></ul>
            </td>
        </tr>
    </tbody>
    </table>
    </div>
    <?php endforeach;?>
<br clear="all">
    <?php foreach($emailServices as $service):?>
    <div class="block service service-<?=$service->type?>" id="service_<?=$service->id?>">
    <input name="services[<?=$service->id?>][id]" type="hidden" value="<?=$service->id?>">

    <input name="services[<?=$service->id?>][html_tmpl]" type="hidden" value="<?=$service->channelSettings['html_tmpl']?>">
    <input name="services[<?=$service->id?>][text_tmpl]" type="hidden" value="<?=$service->channelSettings['text_tmpl']?>">
    <!-- Yeah, i know table.. We hate them too -->
    <table cellpadding="0" cellspacing="0">
    <thead>
        <tr class="row row-dark">
            <th colspan="2" class="title"><strong><?=$service->label?></strong></th>
        </tr>
    </thead>
    <tbody>
        <tr class="row">
            <td style="width:50%">
                <label>Email Subject</label>
                <input name="services[<?=$service->id?>][email_subject]" type="text" class="text" value="<?=$service->email_subject?>">
            </td>
            <td><img src="<?=$service->themeUrl?>img/service_<?=$service->provider?>.png" class="right"></td>
        </tr>
        <tr class="row row-dark">
            <td colspan="2">Lists</td>
        </tr>
        <tr class="row">
            <td colspan="2" class="lists">
                <?php foreach($service->emailLists as $count => $list):?>
                <div class="list">
                    <label>
                        <input <?php if ($service->singleList):?>type="radio"<?php else:?>type="checkbox"<?php endif;?> name="services[<?=$service->id?>][lists][<?=$list['id']?>]" value="yes" <?php if ($list['checked']) echo 'checked';?> >
                        <?=$list['label']?>
                    </label>
                </div>
                <?php if ($count % 2):?><br clear="all"><?php endif;?>
                <?php endforeach;?>
            </td>
        </tr>
        <tr class="row row-dark">
            <td>Deliver</td>
            <td>
                Preview:
                <?php if ($service->channelSettings['html_tmpl'] > 0):?> <a href="<?=$service->previewHtml?>" target="_blank" class="preview-action" data-type="html">HTML</a> <?php endif;?>
                <?php if ($service->channelSettings['text_tmpl'] > 0):?> <a href="<?=$service->previewText?>" target="_blank" class="preview-action" data-type="Text">Text</a> <?php endif;?>
            </td>
        </tr>
        <tr class="row">
            <td class="deliver-toggle">
                <label><input name="services[<?=$service->id?>][deliver]" type="radio" value="test" <?php if ($service->deliver == 'test') echo 'checked';?> > Test</label><br>
                <label><input name="services[<?=$service->id?>][deliver]" type="radio" value="now" <?php if ($service->deliver == 'now') echo 'checked';?> > Immediately</label><br>
                <label><input name="services[<?=$service->id?>][deliver]" type="radio" value="schedule" <?php if ($service->deliver == 'now') echo 'schedule';?> > Schedule</label>
            </td>
            <td class="deliver-wrap">

                <div class="deliver deliver-test">
                    <label>Email (separate multiple with a comma)</label>
                    <input type="text" class="text" name="services[<?=$service->id?>][test_emails]">
                </div>
                <div class="deliver deliver-now">
                    <label>Sending will immediately send the campaign.<br>You will not be able to cancel.</label>
                </div>
                <div class="deliver deliver-schedule">
                    <div class="line">
                        Date
                        <span class="right">
                            <select name="services[<?=$service->id?>][schedule][date][day]"><?=$schedule['date']['day']?></select>
                            <select name="services[<?=$service->id?>][schedule][date][month]"><?=$schedule['date']['month']?></select>
                            <select name="services[<?=$service->id?>][schedule][date][year]"><?=$schedule['date']['year']?></select>
                        </span>
                        <br clear="all">
                    </div>
                    <div class="line">
                        Time
                        <span class="right">
                            <select name="services[<?=$service->id?>][schedule][time][hour]"><?=$schedule['time']['hour']?></select>
                            <select name="services[<?=$service->id?>][schedule][time][minute]"><?=$schedule['time']['minute']?></select>
                            <select name="services[<?=$service->id?>][schedule][time][ampm]"><?=$schedule['time']['ampm']?></select>
                        </span>
                    </div>
                </div>
            </td>
        </tr>
        <tr class="row row-dark">
            <td class="deliver-wrap">
                <button class="submit deliver deliver-test" data-deliver="test">Send Test Emails</button>
                <button class="submit deliver deliver-now" data-deliver="now">Send Campaign Now</button>
                <button class="submit deliver deliver-schedule" data-deliver="schedule">Schedule Campaign</button>
            </td>
            <td class="loading-wrapper">
                <p class="loading loading-test">sending test emails...</p>
                <p class="loading loading-now">sending campaign...</p>
                <p class="loading loading-schedule">scheduling campaign...</p>
                <p class="success success-test">Test Emails Sent!</p>
                <p class="success success-now">Campaign Sent!</p>
                <p class="success success-schedule">Campaign has been Scheduled</p>
            </td>
        </tr>
        <tr class="row errors">
            <td colspan="2">
                <ul></ul>
            </td>
        </tr>
    </tbody>
    </table>
    </div>
    <?php endforeach;?>

    <br clear="all">
</div>


<br clear="all">
<div class="ajax_error block">
    <div class="block-dark block-top">AJAX Error</div>
    <div class="content"></div>
</div>
