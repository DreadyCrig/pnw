<?php
    $this->EE =& get_instance();
	$this->EE->load->helper('form');
	$this->EE->load->library('table');
	$this->EE->table->set_template($cp_pad_table_template);

	foreach($services as $service => $urls)
	{
		echo form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=cp_analytics'.AMP.'method=save_integrations');
		$this->EE->table->set_heading(array('data'=> $this->EE->lang->line('cp_analytics_'.$service)), array('data'=> NBS));
		$this->EE->table->add_row(array('data' => $this->EE->lang->line('cp_analytics_'.$service.'_description'), 'colspan' => 2, 'style' => 'background-color: #FFF;'));
		$this->EE->table->add_row(
			form_label($this->EE->lang->line('cp_analytics_enable').' '.$this->EE->lang->line('cp_analytics_'.$service)),
			form_label(
				form_radio('enable_integration_'.$service, 'y', (isset($settings['enable_integration_'.$service]) && $settings['enable_integration_'.$service] == 'y') ? TRUE : FALSE).NBS.$this->EE->lang->line('yes')
			).NBS.NBS.
			form_label(
				form_radio('enable_integration_'.$service, 'n', ( (isset($settings['enable_integration_'.$service]) && $settings['enable_integration_'.$service] == 'n') || !isset($settings['enable_integration_'.$service]) ) ? TRUE : FALSE).NBS.$this->EE->lang->line('no')
			)
		);
		foreach($urls as $k => $url)
		{
			if($service == 'statusboard')
			{
				$url = $url.'&site_id='.$site_id;
				$type = ($k == 'graph_url') ? 'graph' : 'table';
				$title = (!empty($profile['name'])) ? rawurlencode($profile['name']) : rawurlencode($this->EE->lang->line('cp_analytics_module_name'));
				
				$text = '<a href="panicboard://?url='.rawurlencode($url).'&panel='.$type.'&sourceDisplayName='.$title.'">'.$url.'</a>';
			}
			else
			{
				$text = $url.AMP.'site_id='.$site_id;	
			}
			$this->EE->table->add_row(
				$this->EE->lang->line('cp_analytics_'.$k),
				$text
			);
		}
	
		echo $this->EE->table->generate();		
		echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('cp_analytics_save_settings'), 'class' => 'submit'));
		echo form_close();

	}
																				
?>