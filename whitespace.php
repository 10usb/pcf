<?php
namespace pcf;

class Whitespace {
	private $value;
	
	public function __construct($value = ''){
		$this->value = $value;
	}
	
	public function append($value){
		$this->value.= $value;
		return $this;
	}
	
	public function clear(){
		$this->value = '';
		return $this;
	}
	
	public function get($indent){
		if($this->value){
			$value = str_repeat("\n", max(0, min(1, substr_count($this->value, "\n") - 1)));
			if($indent){
				if($value){
					return str_replace("\n", "\n".str_repeat("\t", $indent), $value);
				}
				return str_repeat("\t", $indent);
			}
			return $value;
		}
		
		if($indent) return str_repeat("\t", $indent);
		return '';
	}
}