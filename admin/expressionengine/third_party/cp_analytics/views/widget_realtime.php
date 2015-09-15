<?php $this->EE =& get_instance(); ?>

<div id="cp_analytics_realtime">
	<div id="cp_analytics_realtime_inner">
		<p class="cp-analytics-reload"><a href="#" onclick="startSpinner(); fetchRealTime();"><?=lang('wgt_cp_analytics_reload')?></a></p>
	</div>
</div>

<script type="text/javascript">

	$(window).load(function()
	{
		startSpinner();
		fetchRealTime();
		setInterval(fetchRealTime, 60000);
	});
	
	function startSpinner()
	{
		var opts = {
			color: '<?=$theme['light_color']?>',
			lines: 13,
			length: 4,
			width: 2,
			radius: 6,
		};
		var spinner = new Spinner(opts).spin();
		$('#cp_analytics_realtime').append(spinner.el);
	}
		
	function fetchRealTime()
	{
		$.get('<?=$realtime_data_url;?>', function(data)
		{
			$('#cp_analytics_realtime').animate({ 'opacity': 0 }, 100, function()
			{
				$('#cp_analytics_realtime_inner').replaceWith(data);
				$('.spinner').remove();
				$('#cp_analytics_realtime').animate({ 'opacity': 1 }, 500);
			});
		});
	}
</script>