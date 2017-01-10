<?php
namespace pcf;

class Autoloader {
	public static function register() {
		spl_autoload_register('pcf\\Autoloader::load');
	}
	
	public static function load($name, $throw = true){
		if(substr($name, 0, 4)!='pcf\\') return false;
		
		$filename =  __DIR__.'/'.strtolower(implode('/', array_slice(explode('\\', $name), 1))).'.php';
		
		if(!file_exists($filename)){
			if($throw) throw new \Exception('File "'.$filename.'" not found');
			return false;
		}
		require_once $filename;
		
		if(class_exists($name) || interface_exists($name)) return true;
		
		if($throw) throw new \Exception('Class or Interface "'.$name.'" not exists');
		return false;
	}
}