<?php
class Json extends CI_model{
	var $_param = array();
	function set($name,$value)
	{
		//$value = $this->nullstring->set($value);
		$this->_param['data'][$name] = $value;
	}
	function send()
	{
		header("content-type:application/json;charset:utf-8");
		echo json_encode($this->_param);
		exit();
	}
}
?>