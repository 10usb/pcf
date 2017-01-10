<?php
namespace pcf\parsers;

use pcf\TokenException;

class SwitchParser extends CodeParser {
	public function __construct($tokenizer, $indent){
		parent::__construct($tokenizer, $indent);
	}
	
	public function process(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop(2);
	
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->tokenizer->pop();
		$this->print($token->type, $token->text, 'group');
	
		$this->processExpression();
		$token = $this->tokenizer->pop();
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'group');
	
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
	
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
	
		$token = $this->tokenizer->pop();
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->print($token->type, $token->text, 'operator');
		$this->print(T_WHITESPACE, "\n", 'whitespace');
		$this->indent+=2;
		
		$open = false;
		$this->whitespace->clear();
		while($token = $this->tokenizer->peek()){
			switch($token->type){
				case T_CASE:
					if($whitespace = $this->whitespace->get($this->indent - 1)) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
					$this->whitespace->clear()->append("\n");
					$this->print($token->type, $token->text, 'keyword');
					$token = $this->tokenizer->popAndPeek();
					if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
					$this->print(T_WHITESPACE, " ", 'whitespace');
					$this->tokenizer->pop();
					$this->processExpression();
					$token = $this->tokenizer->pop();
					if($token->type!=T_OPERATOR || $token->text != ':') throw new TokenException($token, 'Unexpected {token} expected ":"');
					$this->print($token->type, $token->text, 'operator');
					$open = true;
				break;
				case T_DEFAULT:
					if($whitespace = $this->whitespace->get($this->indent - 1)) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
					$this->whitespace->clear()->append("\n");
					$this->print($token->type, $token->text, 'keyword');
					$this->tokenizer->pop();
					$token = $this->tokenizer->pop();
					if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
					if($token->type!=T_OPERATOR || $token->text != ':') throw new TokenException($token, 'Unexpected {token} expected ":"');
					$this->print($token->type, $token->text, 'operator');
					$open = true;
				break;
				case T_WHITESPACE:
					$this->tokenizer->pop();
					$this->whitespace->append($token->text);
				break;
				case T_BREAK:
					if($whitespace = $this->whitespace->get($this->indent - 1)) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
					if($this->processCode()){
						$this->print(T_WHITESPACE, "\n", 'whitespace');
						$this->whitespace->clear();
					}else throw new TokenException($token, 'Unexpected {token}');
				break;
				case T_RETURN:
					if($open){
						$this->print(T_WHITESPACE, ' ', 'whitespace');
						if($this->processCode()){
							$this->print(T_WHITESPACE, "\n", 'whitespace');
							$this->whitespace->clear();
						}else throw new TokenException($token, 'Unexpected {token}');
					}else{
						if($this->processCode($this->whitespace->get($this->indent))){
							$this->print(T_WHITESPACE, "\n", 'whitespace');
							$this->whitespace->clear();
					}else throw new TokenException($token, 'Unexpected {token}');
					}
				break;
				default:
					if($this->processCode($this->whitespace->get($this->indent))){
						$this->print(T_WHITESPACE, "\n", 'whitespace');
						$this->whitespace->clear();
					}else break 2;
				break;
			}
		}
	
		$this->indent-=2;
		if($this->indent) $this->print(T_WHITESPACE, str_repeat("\t", $this->indent), 'whitespace');
		$token = $this->tokenizer->pop();
		if($token->type != T_BLOCK_CLOSE) throw new TokenException($token, 'Unexpected {token} expected "}"');
		$this->print($token->type, $token->text, 'operator');
	}
}