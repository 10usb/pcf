<?php
namespace pcf\parsers;

use pcf\TokenException;

class PhpParser extends CodeParser {
	public function __construct($tokenizer, $indent){
		parent::__construct($tokenizer, $indent);
	}
	
	public function process(){
		$token = $this->tokenizer->peek();
		if($token->type==T_OPEN_TAG){
			$this->tokenizer->pop();
			$this->print($token->type, $token->text, 'phptag');
				
			$token = $this->tokenizer->peek();
			if($token->type == T_WHITESPACE){
				$this->print(T_WHITESPACE, "\n", 'whitespace');
			}else{
				$this->print(T_WHITESPACE, "\n", 'whitespace');
			}
		}
		
		$this->whitespace->clear();
		do {
			switch($token->type){
				case T_CLASS:
					if($whitespace = $this->whitespace->get($this->indent)) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
					$this->processClass();
					$this->print(T_WHITESPACE, "\n", 'whitespace');
					$this->whitespace->clear();
				break;
				case T_FUNCTION:
					if($whitespace = $this->whitespace->get($this->indent)) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
					$this->processFunction();
					$this->print(T_WHITESPACE, "\n", 'whitespace');
					$this->whitespace->clear();
				break;
				case T_CLOSE_TAG: throw new TokenException($token, "{token} not supported");
				case T_WHITESPACE: 
					$this->tokenizer->pop();
					$this->whitespace->append($token->text);
				break;
				default:
					if($this->processCode($this->whitespace->get($this->indent))){
						$this->print(T_WHITESPACE, "\n", 'whitespace');
						$this->whitespace->clear();
					}else throw new TokenException($token, 'Unexpected {token} expected "?>"');
				break;
			}
		}while($token = $this->tokenizer->peek());
	}
	
	public function processFunction(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->pop();
		if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
		$this->print(T_WHITESPACE, " ", 'whitespace');
		$token = $this->tokenizer->pop();
		if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
		$this->print($token->type, $token->text, 'function');
	
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
	
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->tokenizer->pop();
		$this->print($token->type, $token->text, 'group');
		
		// @todo change to function params
		$this->processParameters();
	
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'group');
	
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
	
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->processBlock();
	}
	
	public function processClass(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->pop();
		if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
		$this->print(T_WHITESPACE, " ", 'whitespace');
		$token = $this->tokenizer->pop();
		if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
		$this->print($token->type, $token->text, 'class');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		if($token->type == T_EXTENDS){
			$this->print($token->type, $token->text, 'keyword');
			
			$token = $this->tokenizer->pop();
			if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
			$this->print(T_WHITESPACE, " ", 'whitespace');
			
			$token = $this->tokenizer->pop();
			if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
			$this->print($token->type, $token->text, 'class');
			
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			$this->print(T_WHITESPACE, " ", 'whitespace');
		}
		
		if($token->type == T_IMPLEMENTS){
			$this->print($token->type, $token->text, 'keyword');
			
			$token = $this->tokenizer->pop();
			if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
			$this->print(T_WHITESPACE, " ", 'whitespace');
			
			$token = $this->tokenizer->pop();
			if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
			$this->print($token->type, $token->text, 'class');
			
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			
			while($token->type == T_SEPERATOR){
				$this->print($token->type, $token->text, 'operator');
				
				$token = $this->tokenizer->pop();
				if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
				$this->print(T_WHITESPACE, " ", 'whitespace');
				
				if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
				$this->print($token->type, $token->text, 'class');
				
				$token = $this->tokenizer->pop();
				if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			}
			
			
			$this->print(T_WHITESPACE, " ", 'whitespace');
		}
		
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->print($token->type, $token->text, 'operator');
		$this->print(T_WHITESPACE, "\n", 'whitespace');
		$this->indent++;
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		
		while($token){
			switch($token->type){
				case T_PUBLIC:
					$this->print($token->type, $token->text, 'public');
					$this->processClassMember();
				break;
				case T_PROTECTED:
					$this->print($token->type, $token->text, 'protected');
					$this->processClassMember();
				break;
				case T_PRIVATE:
					$this->print($token->type, $token->text, 'private');
					$this->processClassMember();
				break;
				case T_BLOCK_CLOSE:
					$token = $this->tokenizer->pop();
					$this->print($token->type, $token->text, 'operator');
				break 2;
				default: throw new TokenException($token, 'Unexpected {token} expected class member');
			}
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		}
	}
	
	public function processClassMember(){
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		switch($token->type){
			case T_FUNCTION: $this->processFunction(); break;
			case T_VARIABLE:
				$this->processClassProperty();
				$token = $this->tokenizer->pop();
				if($token->type != T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected "{" or ","');
				$this->print($token->type, $token->text, 'operator');
				$this->print(T_WHITESPACE, "\n", 'whitespace');
			break;
			default: throw new TokenException($token, 'Unexpected {token} expected class member');
		}
	}
	
	public function processClassProperty(){
		$token = $this->tokenizer->peek();
		$this->print($token->type, $token->text, 'variable');
		$token = $this->tokenizer->popAndPeek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		
		if($token->type == T_OPERATOR && $token->text == '='){
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->print($token->type, $token->text, 'operator');
			
			$token = $this->tokenizer->popAndPeek();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
			$this->print(T_WHITESPACE, " ", 'whitespace');
			
			$this->processExpression();
		}

		$token = $this->tokenizer->peek();
		if($token->type == T_SEPERATOR){
			$this->print($token->type, $token->text, 'operator');
			$token = $this->tokenizer->popAndPeek();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processClassProperty();
		}
	}
}