<?php
namespace pcf;

use pcf\parsers\PhpParser;

class Formatter {
	/**
	 * 
	 * @var Tokenizer
	 */
	private $tokenizer;
	
	private $indent;
	
	public function __construct($tokenizer){
		$this->tokenizer	= $tokenizer;
		$this->indent		= 0;
	}

	public function print($type, $text, $classname = ''){
		echo '<span class="'.$classname.'" title="'.token_name($type).'">'.htmlentities(str_replace("\t", '    ', $text)).'</span>';
		return $this;
	}
	
	public function process(){
		while($token = $this->tokenizer->peek()){
			if($token->type==T_OPEN_TAG){
				$parser = new PhpParser($this->tokenizer, $this->indent);
				$parser->process();
			}else{
				$this->print($token->type, $token->text);
				$this->tokenizer->pop();
			}
		}
	}
}