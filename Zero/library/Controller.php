<?php
namespace Zero\library;

use Zero\library\Config;
use TemplateEngine\TemplateEngine;

class Controller
{

	protected $smarty;

	public function __construct($module, $controller, $action)
	{
		// template init
		$this->module = $module;
		$this->controller = $controller;
		$this->action = $action; 
		$template_config = Config::get('template');
		$app_config = Config::get('app');
		$this->smarty = new TemplateEngine();
		$this->smarty->debug = $app_config['app_debug'];  //debug on
		$this->smarty->setTemplateDir(APP_PATH.$this->module.DS.$template_config['template_dir'].DS.$this->controller.DS);
		$this->smarty->setCompileDir(RUNTIME_PATH.$template_config['compie_dir'].DS.$module.DS.$this->controller.DS);
		$this->smarty->left_delimiter = $template_config['left_delimiter'];
		$this->smarty->right_delimiter = $template_config['right_delimiter'];
	}

	protected function assign($key, $value)
	{
		$this->smarty->assign($key, $value);	
	}

	protected function display($file = '')
	{
		if( empty($file) ){	
			$file = $this->action;
		}
		$this->smarty->display($file);
	}

}