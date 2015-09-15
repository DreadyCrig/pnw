<?php $this->EE =& get_instance(); ?>

<div id="cp_analytics_chart_container">
	<div id="cp_analytics_chart">
		<p class="cp-analytics-reload"><a href="#" onclick="drawChart();"><?=lang('wgt_cp_analytics_reload')?></a></p>
	</div>
</div>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
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
</script>
