<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Widgets_m extends MY_Model
{
	
	function __construct()
	{
		$this->widget_path = array(
			'theme' => WWW_FOLDER . $this->template->get_theme_path($this->config->item('theme')). 'views/widgets' . DIRECTORY_SEPARATOR,
			'addon' => ADDON_FOLDER . 'widgets' . DIRECTORY_SEPARATOR,
			'core' => APPPATH . 'widgets' . DIRECTORY_SEPARATOR
		);
	}
	
	function get_widgets($type = 'core')
	{
		
		$widgets = array();
		if(! $files = directory_map($this->widget_path[$type], 1))
			return false;
		
		foreach ($files as $file) {
			
			// if it is folder
			if (strpos($file, DIRECTORY_SEPARATOR)) {
				
				// get foldername
				$foldername = str_replace(DIRECTORY_SEPARATOR, "", $file);
				
				if (file_exists($this->widget_path[$type] . $file . 'Widget_' . $foldername . '.php')) {
					
					// load widget class
					$widget_obj = $this->cpformutil->load('Widget_' . $foldername, $this->widget_path[$type] . $file);
					
					// get neccesary properties
					$widget_obj->init();
					foreach ($widget_obj as $key => $value) {
						if (strpos($key, 'widget') !== FALSE) $widgets[$foldername][$key] = $value;
					}
					
					// get widget form
					$widgets[$foldername]['form'] = $widget_obj->generate('paragraph');
					
					// destroy object
					unset($widget_obj);
				}
			}
		}
		
		return $widgets;
	}
	
	function load_widget($widget_class = false, $data = array())
	{
		$widget = false;
		foreach ($this->widget_path as $key => $path) {
			
			// get foldername
			$foldername = str_replace('Widget_', "", $widget_class);
			
			if (file_exists($path . $foldername . '/' . $widget_class . '.php')) {
				
				// load widget class
				$widget = $this->cpformutil->load($widget_class, $path . $foldername . DIRECTORY_SEPARATOR);
				
				// init form
				$widget->init($data);
				
				// place data into object
				$widget->widget_data = $data;
			}
		}
		
		return $widget;
	}
	
	function load_widget_data($data = array(), $form_config = array(), $additional_config = array())
	{
		if (empty($data)) return false;
		
		// load widget class
		$widget = $this->load_widget('Widget_' . $data['widget'], $data);
		
		$output = array();
		// get neccesary properties
		foreach ($widget as $key => $value) {
			if (strpos($key, 'widget') !== FALSE) $output[$key] = $value;
		}

		// override action url for edit
		if(! empty($form_config) || ! empty($additional_config)) {
			$widget->config($form_config, $additional_config);
		}
		
		// get widget form
		$output['form'] = $widget->generate('paragraph');
		
		// destroy object
		unset($widget);

		return $output;
	}

	function save_widget($widget_class, $data)
	{
		$data['widget'] = str_replace('Widget_', '', $widget_class);
		
		if (!file_exists(WIDGET_FOLDER . $data['area'])) mkdir(WIDGET_FOLDER . $data['area'], 0775, true);
		
		// write widget data file
		write_file(WIDGET_FOLDER . $data['area'] . '/' . strtolower(url_title($data['title'])) . '.json', json_encode($data, JSON_PRETTY_PRINT));

		// update widget index
		$index = json_decode(file_get_contents(WIDGET_FOLDER.$data['area'].DIRECTORY_SEPARATOR.'index.json'), true);
		$index[] = strtolower(url_title($data['title']));
		return write_file(WIDGET_FOLDER . $data['area'] . '/index.json', json_encode($index, JSON_PRETTY_PRINT));
	}


	function update_widget($widget_class, $data, $prev_data)
	{
		$data['widget'] = str_replace('Widget_', '', $widget_class);

		// delete previous data
		$this->delete_widget(strtolower(url_title($prev_data['title'])), $prev_data['area']);
		
		return $this->save_widget($widget_class, $data);
	}

	function delete_widget($widget_slug, $area)
	{
		// delete the file
		unlink(WIDGET_FOLDER.$area.'/'.$widget_slug.'.json');

		// update widget index
		$index = json_decode(file_get_contents(WIDGET_FOLDER.$area.DIRECTORY_SEPARATOR.'index.json'), true);
		if(($key = array_search($widget_slug, $index)) !== false)
		    unset($index[$key]);
		return write_file(WIDGET_FOLDER . $area . '/index.json', json_encode($index, JSON_PRETTY_PRINT));
	}
}
