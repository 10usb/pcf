<?php
namespace pcf;

define('T_OPERATOR', 400001);
define('T_CODE_END', 400002);
define('T_PARENT_OPEN', 400003);
define('T_PARENT_CLOSE', 400004);
define('T_BLOCK_OPEN', 400005);
define('T_BLOCK_CLOSE', 400006);
define('T_SEPERATOR', 400007);

class Tokenizer {
	private $tokens;
	
	public function __construct(){
		$this->tokens = array();
	}
	
	public function parse($source){
		foreach(token_get_all($source) as $data){
			$token = new \stdClass();
			
			if(is_array($data)){
				$token->type	= $data[0];
				$token->text	= $data[1];
			}else{
				if(in_array($data, array('=', '-', '+', '*', '/', '%', '^', '&', '!', '<', '>', '?', ':'))){
					$token->type	= T_OPERATOR;
				}elseif($data==';'){
					$token->type	= T_CODE_END;
				}elseif($data=='('){
					$token->type	= T_PARENT_OPEN;
				}elseif($data==')'){
					$token->type	= T_PARENT_CLOSE;
				}elseif($data=='{'){
					$token->type	= T_BLOCK_OPEN;
				}elseif($data=='}'){
					$token->type	= T_BLOCK_CLOSE;
				}elseif($data==','){
					$token->type	= T_SEPERATOR;
				}else{
					$token->type	= 0;
				}
				$token->text	= $data;
			}
			$this->tokens[] = $token;
		}
	}
	
	public function count(){
		return count($this->tokens);
	}

	public function peek($offset = 0){
		if($offset < count($this->tokens)) return $this->tokens[$offset];
		return null;
	}
	
	public function pop($times = 1){
		for($i = 1; $i < $times; $i++) array_shift($this->tokens);
		return array_shift($this->tokens);
	}
	
	public function popAndPeek(){
		$this->pop();
		return $this->peek();
	}
}