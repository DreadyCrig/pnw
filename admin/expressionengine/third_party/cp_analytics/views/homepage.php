<?php $this->EE =& get_instance(); ?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	var template = $('<?=$template;?>');
	$('#mainContent').append(template);
	
	<?php if($display_sections['show_chart'] == 'y') : ?>
	
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawChart);
	var delay = (function(){
		var timer = 0;
		return function(callback, ms)
		{
	    	clearTimeout(timer);
	    	timer = setTimeout(callback, ms);
		};
	})();
	
	$(window).resize(function()
	{
		delay(function(){ drawChart(); }, 500);
	});
	
	function drawChart()
	{
		frame_width = $('#cp_analytics_chart').innerWidth();
		chart_width = frame_width - 20;	
		var data = new google.visualization.DataTable();
		data.addColumn('string', '<?= ucfirst($this->EE->lang->line('cp_analytics_date')); ?>');
		data.addColumn('number', '<?= ucfirst($this->EE->lang->line('cp_analytics_visits')); ?>');
		data.addColumn('number', '<?= ucfirst($this->EE->lang->line('cp_analytics_pageviews')); ?>');
		data.addRows([
		  <?=$chart;?>
		]);	
		var chart = new google.visualization.AreaChart(document.getElementById('cp_analytics_chart'));
				
		chart.draw(data, {
			backgroundColor: '<?=$theme['background_color'];?>',
			chartArea: { height: 150, left: 10, top: 10, width: chart_width },
			colors: ['<?=$theme['accent_color']?>', '<?=$theme['light_color']?>'],
			hAxis: { textPosition: 'in', textStyle: { color: '<?=$theme['medium_color']?>', fontSize: 11 }, showTextEvery: 4 },
			height: 160,
			isStacked: false,
			legend: 'none',
			lineWidth: 3,
			pointSize: 6,
			tooltipTextStyle: { color: '<?=$theme['medium_color']?>', fontSize: 11 },
			vAxis: { baselineColor: '<?=$theme['light_color']?>', gridlineColor: '<?=$theme['background_color'];?>', textPosition: 'in', textStyle: { color: '<?=$theme['medium_color']?>', fontSize: 11 }, viewWindowMode: 'pretty' },
			width: frame_width
		});
	}
	
	<?php endif; ?>

	<?php if($display_sections['show_realtime'] == 'y') : ?>

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
	
	<?php endif; ?>
</script>