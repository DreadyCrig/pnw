<?php
	$this->EE =& get_instance();
	
	switch($board_type)
	{
		case 'graph':
		
			$visitors = $pageviews = array();
			$i = 0;
				
			foreach($lastmonth['daily_stats'] as $date => $data)
			{
				$date = date('n/j', strtotime($date));
				$visitors[$i] = array('title' => $date, 'value' => $data['visits']);
				$pageviews[$i] = array('title' => $date, 'value' => $data['pageviews']);
				$i++;
			}
		
			$graph = array(
				'graph' => array(
					'title' => $profile['name'],
					'total' => true,
					'type' => 'line',
					'datasequences' => array(
						array(
							'title' => 'Visitors',
							'color' => 'lightGray',
							'datapoints' => $visitors
						),
						array(
							'title' => 'Pageviews',
							'color' => 'yellow',
							'datapoints' => $pageviews
						)
					)
				)			
			);
			
			echo json_encode($graph);	
		
		break;
		
		case 'monthly-table': ?>
			
			<table>
				<tr>
					<td><?=$lastmonth['visits']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_visits')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['visits_sparkline'], 180, 30)?></td>
				</tr>
				<tr>
					<td><?=$lastmonth['pageviews']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_pageviews')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['pageviews_sparkline'], 180, 30)?></td>
				</tr>
				<tr>
					<td><?=$lastmonth['pages_per_visit']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_pages_per_visit')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['pages_per_visit_sparkline'], 180, 30)?></td>
				</tr>
				<tr>
					<td><?=$lastmonth['bounce_rate']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_bounce_rate')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['bounce_rate_sparkline'], 180, 30)?></td>
				</tr>
				<tr>
					<td><?=$lastmonth['avg_visit']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_avg_visit')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['avg_visit_sparkline'], 180, 30)?></td>
				</tr>
				<tr>
					<td><?=$lastmonth['new_visits']?> <span style="color:rgb(100,112,118);"><?=$this->EE->lang->line('cp_analytics_new_visits')?></span></td>
					<td style="width:180px;"><?=cp_analytics_sparkline($lastmonth['new_visits_sparkline'], 180, 30)?></td>
				</tr>
			</table>
		
		<?php break;
		
		case 'today-table': ?>
			
			<table>
				<tr>
					<td colspan="2" style="color:rgb(255,198,0);"><?=$this->EE->lang->line('cp_analytics_today')?></td>
				</tr>
				<tr>
					<td><?=$visits?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_visits')?></td>
				</tr>
				<tr>
					<td><?=$pageviews?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_pageviews')?></td>
				</tr>
				<tr>
					<td><?=$pages_per_visit?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_pages_per_visit')?></td>
				</tr>
				<tr>
					<td><?=$avg_visit?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_avg_visit')?></td>
				</tr>
			</table>		

		<?php break;
		
		case 'yesterday-table': ?>
			
			<table>
				<tr>
					<td colspan="2" style="color:rgb(255,198,0);"><?=$this->EE->lang->line('cp_analytics_yesterday')?></td>
				</tr>
				<tr>
					<td><?=$yesterday['visits']?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_visits')?></td>
				</tr>
				<tr>
					<td><?=$yesterday['pageviews']?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_pageviews')?></td>
				</tr>
				<tr>
					<td><?=$yesterday['pages_per_visit']?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_pages_per_visit')?></td>
				</tr>
				<tr>
					<td><?=$yesterday['avg_visit']?></td>
					<td style="color:rgb(100, 112, 118);"><?=$this->EE->lang->line('cp_analytics_avg_visit')?></td>
				</tr>
			</table>		

		<?php break;	

	}
	
?>