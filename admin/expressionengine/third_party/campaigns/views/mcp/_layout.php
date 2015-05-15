<!--[if lt IE 7 ]><div id="campaigns" class="no-js ie ie6 lt-ie10 lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7 ]><div id="campaigns" class="no-js ie ie7 lt-ie10 lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8 ]><div id="campaigns" class="no-js ie ie8 lt-ie10 lt-ie9"><![endif]-->
<!--[if IE 9 ]><div id="campaigns" class="no-js ie ie9 lt-ie10"><![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<div id="campaigns" class="no-js">
<!--<![endif]-->

<div id="dmenu">
    <ul>
        <li <?php if ($section == 'home') echo 'class="current"';?> ><a href="<?=$base_url?>&method=home" class="menu"><?=lang('c:dashboard')?></a></li>
        <!--<li <?php if ($section == 'lists') echo 'class="current"';?> ><a href="<?=$base_url?>&method=lists" class="menu"><?=lang('c:lists')?></a></li>-->
        <li <?php if ($section == 'channels') echo 'class="current"';?> ><a href="<?=$base_url?>&method=channels" class="menu"><?=lang('c:channels')?></a></li>
        <li <?php if ($section == 'accounts') echo 'class="current"';?> ><a href="<?=$base_url?>&method=accounts" class="menu"><?=lang('c:accounts')?></a></li>
        <li <?php if ($section == 'settings') echo 'class="current"';?> ><a href="<?=$base_url?>&method=settings" class="menu"><?=lang('c:settings')?></a></li>
    </ul>
</div>


<div id="dpage">
    <div id="sec-<?=$section?>"><?=$content?></div>

    <div class="ajax_error">
        <div class="top">AJAX Error</div>
        <div class="content"></div>
    </div>

</div>

</div> <!-- </subs> -->


<br clear="all">
</div> <!-- </campaigns> -->