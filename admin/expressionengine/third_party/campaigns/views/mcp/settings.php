<div class="container-fluid" id="settings">

<div class="row pagehead">
    <div class="col-xs-6 ">
        <h2><?=lang('c:settings')?></h2>
    </div>
</div>

<?=form_open($base_url_short.AMP.'method=update_settings')?>

<div class="row">
    <div class="col-xs-6">
        <div class="dtable">
            <h2><?=lang('c:cron:url')?></h2>
            <table>
            <tr>
                <td>
                    <input type="text" class="text" value="<?=$cron_url?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="warning">
                    <small><?=lang('c:cron:exp')?></small>
                </td>
            </tr>
            </table>
        </div>

        <div class="dtable">
            <h2><?=lang('c:services')?></h2>
            <table>
            <tr>
                <td><?=lang('c:service_logo')?></td>
                <td>
                    <label><input name="settings[service_logo]" value="yes" type="radio" <?php if ($settings['service_logo'] == 'yes') echo "checked"?> > <?=lang('c:yes')?></label>&nbsp;&nbsp;
                    <label><input name="settings[service_logo]" value="no" type="radio" <?php if ($settings['service_logo'] == 'no') echo "checked"?> > <?=lang('c:no')?></label>
                </td>
            </tr>
            </table>
        </div>

    </div>
    <div class="col-xs-6">
        <div class="dtable">
            <h2><?=lang('c:url_shortener')?></h2>

            <table>
            <thead>
                <tr class="heading urlshort-toggler">
                    <th colspan="5">
                        <label><input name="settings[url_shortener]" value="" type="radio" <?php if ($settings['url_shortener'] == '') echo "checked"?> > <?=lang('c:none')?></label>&nbsp;&nbsp;
                        <label><input name="settings[url_shortener]" value="bitly" type="radio" <?php if ($settings['url_shortener'] == 'bitly') echo "checked"?> > Bitly</label>&nbsp;&nbsp;
                        <label><input name="settings[url_shortener]" value="isgd" type="radio" <?php if ($settings['url_shortener'] == 'isgd') echo "checked"?> > is.gd</label>&nbsp;&nbsp;
                        <label><input name="settings[url_shortener]" value="googl" type="radio" <?php if ($settings['url_shortener'] == 'googl') echo "checked"?> > goo.gl</label>&nbsp;&nbsp;
                        <label><input name="settings[url_shortener]" value="yourls" type="radio" <?php if ($settings['url_shortener'] == 'yourls') echo "checked"?> > YOURLS</label>&nbsp;&nbsp;
                        <!--<label><input name="settings[url_shortener]" value="cloudapp" type="radio" <?php if ($settings['url_shortener'] == 'cloudapp') echo "checked"?> > CloudApp</label>&nbsp;&nbsp;-->
                        <label><input name="settings[url_shortener]" value="lessnmore" type="radio" <?php if ($settings['url_shortener'] == 'lessnmore') echo "checked"?> > Lessn More</label>&nbsp;&nbsp;
                    </th>
                </tr>
            </thead>

            <tbody class="urlshort urlshort-bitly">
                <tr>
                    <td>Username</td>
                    <td><input name="settings[shortner][bitly][username]" value="<?=$settings['shortner']['bitly']['username']?>" type="text"></td>
                </tr>
                <tr>
                    <td>API Key</td>
                    <td><input name="settings[shortner][bitly][api_key]" value="<?=$settings['shortner']['bitly']['api_key']?>" type="text"></td>
                </tr>
                <tr>
                    <td colspan="2"><a href="https://bitly.com/a/your_api_key" target="_blank">Get API Key</a></td>
                </tr>
            </tbody>

            <tbody class="urlshort urlshort-googl">
                <tr>
                    <td>API Key</td>
                    <td><input name="settings[shortner][googl][api_key]" value="<?=$settings['shortner']['googl']['api_key']?>" type="text"></td>
                </tr>
                <tr>
                    <td class="info" colspan="2">
                        <p>To acquire an API key, visit the <a href="https://console.developers.google.com" target="_blank">APIs Console</a>.</p>
                        <p>Create a new project or select an existing one, go to API's & auth -> APIs and turn on the URL Shortener API; if the Terms of Service appear, read and accept them.</p>
                        <p>Next, go to the [API's & auth -> Credentials] pane. Create a new Public API Access key or use an existing one (Server Key).</p>
                    </td>
                </tr>
            </tbody>

            <tbody class="urlshort urlshort-yourls">
                <tr>
                    <td>Install URL</td>
                    <td><input name="settings[shortner][yourls][install_url]" value="<?=$settings['shortner']['yourls']['install_url']?>" type="text"></td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td><input name="settings[shortner][yourls][signature]" value="<?=$settings['shortner']['yourls']['signature']?>" type="text"></td>
                </tr>
                <tr>
                    <td class="info" colspan="2">
                        <p>Install URL: The full URL to your YOURLS install, example: http://yourls.sample.com</p>
                        <p>Your "signature" can be found in the Tools section of your YOURLS admin interface.</p>
                    </td>
                </tr>
            </tbody>

            <tbody class="urlshort urlshort-lessnmore">
                <tr>
                    <td>Install URL</td>
                    <td><input name="settings[shortner][lessnmore][install_url]" value="<?=$settings['shortner']['lessnmore']['install_url']?>" type="text"></td>
                </tr>
                <tr>
                    <td>Signature</td>
                    <td><input name="settings[shortner][lessnmore][api_key]" value="<?=$settings['shortner']['lessnmore']['api_key']?>" type="text"></td>
                </tr>
                <tr>
                    <td class="info" colspan="2">
                        <p>Install URL: The full URL to your Lessn More install, example: http://lessnmore.sample.com</p>
                        <p>Your API key is displayed when you log in to Lessn More.</p>
                    </td>
                </tr>
            </tbody>

            </table>
        </div>
    </div>
</div>

<div class="row pagebottom">
    <button class="btn btn-green"><?=lang('c:save')?></button>
</div>

<?=form_close()?>

</div> <!-- #settings -->