<?php $this->EE =& get_instance(); ?>
<div id="cp_analytics_home_container" class="contents">
	<div class="heading">
		<h2><?=$this->EE->lang->line('cp_analytics_homepage_chart_title')?></h2>
	</div>
	<div id="cp_analytics_home_content">
		<?php if($show_realtime == 'y') : ?>
		<div id="cp_analytics_realtime">
			<div id="cp_analytics_realtime_inner"></div>
		</div>
		<?php endif; ?>
		<?php if($show_realtime == 'y' && $show_chart == 'y') : ?>
		<h3><?=$this->EE->lang->line('cp_analytics_lastmonth')?></h3>
		<?php endif; ?>
		<?php if($show_chart == 'y') : ?>
		<div id="cp_analytics_chart_container">
			<div id="cp_analytics_chart"></div>
		</div>
		<?php endif; ?>
	</div>
</div>