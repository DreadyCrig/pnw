<?php

function cp_analytics_chart_data($data, $days = 30, $date_format = 'M jS')
{
	$stats = array();
	$i = 0;
	
	if($days < count($data))
	{
		$data = array_slice($data, -$days, $days, true);
	}
		
	foreach($data as $date => $row)
	{
		$stats[$i] = "['".date($date_format, strtotime($date.' 12:00:00'))."', ";
		$stats[$i] .= $row['visits'].', ';
		$stats[$i] .= $row['pageviews'].']';
		$i++;
	}
	return implode(',', $stats);
}


function cp_analytics_link_url($url, $title)
{
	$text = (strlen($title) > 20) ? substr($title, 0, 20).'&hellip;' : $title;
	return '<a href="'.QUERY_MARKER.'URL='.urlencode($url).'" target="_blank" title="'.$title.'">'.$text.'</a>';
}

function cp_analytics_percentage($value, $total, $rounded = false)
{
	$v = ( $value / $total ) * 100;
	return ($rounded == true) ? number_format($v) : $v;
}

function cp_analytics_sparkline($url, $width = 120, $height = 20)
{
	return '<img src="'.$url.'" width="'.$width.'" height="'.$height.'" alt="" />';
}