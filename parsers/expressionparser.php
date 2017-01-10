<?php
namespace pcf\parsers;

use pcf\TokenException;

class ExpressionParser {
	/**
	 * 
	 * @var pcf\Tokenizer
	 */
	protected  $tokenizer;
	
	/**
	 * 
	 * @param pcf\Tokenizer $tokenizer
	 */
	public function __construct($tokenizer){
		$this->tokenizer	= $tokenizer;
	}

	public function print($type, $text, $classname = ''){
		echo '<span class="'.$classname.'" title="'.token_name($type).'">'.htmlentities(str_replace("\t", '    ', $text)).'</span>';
		return $this;
	}
	
	public function processExpression(){
		$token = $this->tokenizer->peek();
		switch($token->type){
			case T_OPERATOR:case T_INC: case T_DEC:
			case T_IS_EQUAL:
				$this->print($token->type, $token->text, 'operator');
				$this->tokenizer->pop();
				$this->processExpression();
			break;
			case T_STRING_CAST:
				$this->print($token->type, $token->text, 'operator');
				$this->tokenizer->pop();
				$this->processExpression();
			break;
			case T_LNUMBER:
				$this->print($token->type, $token->text, 'number');
				$this->tokenizer->pop();
				$this->processExpressionExtend();
			break;
			case T_CONSTANT_ENCAPSED_STRING:
				$this->print($token->type, $token->text, 'string');
				$this->tokenizer->pop();
				$this->processExpressionExtend();
			break;
			case T_VARIABLE:
				$this->processVariable();
			break;
			case T_STRING:
				$this->processConstant();
			break;
			default: throw new TokenException($token, "{token} not supported");
		}
	}
	
	public function processExpressionExtend(){
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE){
			$whitespace = $token;
			$token = $this->tokenizer->popAndPeek();
		}else{
			$whitespace = null;
		}
		
		switch($token->type){
			case T_CODE_END: return true;
			case T_PARENT_CLOSE: return true;
			case T_SEPERATOR: return true;
			case T_AS: return true;
			case T_OPERATOR:
				if($token->text == '?'){
					$this->print(T_WHITESPACE, " ", 'whitespace');
					$this->print($token->type, $token->text, 'operator');
					$this->tokenizer->pop();
					$token = $this->tokenizer->peek();
					if($token->type == T_WHITESPACE){
						$this->print(T_WHITESPACE, " ", 'whitespace');
						$this->tokenizer->pop();
					}else{
						$this->print(T_WHITESPACE, " ", 'whitespace');
					}
					$this->processExpression();
					$token = $this->tokenizer->peek();
					if($token->type == T_WHITESPACE){
						$whitespace = $token;
						$token = $this->tokenizer->popAndPeek();
					}else{
						$whitespace = null;
					}
					if($token->type!=T_OPERATOR || $token->text != ':') throw new TokenException($token, 'Unexpected {token} expected ":"');
					$this->print(T_WHITESPACE, " ", 'whitespace');
					$this->print($token->type, $token->text, 'operator');
					$this->tokenizer->pop();
					$token = $this->tokenizer->peek();
					if($token->type == T_WHITESPACE){
						$this->print(T_WHITESPACE, " ", 'whitespace');
						$this->tokenizer->pop();
					}else{
						$this->print(T_WHITESPACE, " ", 'whitespace');
					}
					$this->processExpression();
				}else if($token->text == ':') return true;
			case T_BOOLEAN_AND:
			case T_IS_EQUAL: case T_PLUS_EQUAL:
				$this->print(T_WHITESPACE, " ", 'whitespace');
				$this->print($token->type, $token->text, 'operator');
				$this->tokenizer->pop();
				$token = $this->tokenizer->peek();
				if($token->type == T_WHITESPACE){
					$this->print(T_WHITESPACE, " ", 'whitespace');
					$this->tokenizer->pop();
				}else{
					$this->print(T_WHITESPACE, " ", 'whitespace');
				}
				$this->processExpression();
			break;
			case T_INC: case T_DEC:
				$this->print($token->type, $token->text, 'operator');
				$this->tokenizer->pop();
				$token = $this->tokenizer->peek();
				if($token->type == T_WHITESPACE) $this->tokenizer->pop();
				$this->processExpressionExtend();
			break;
			default: throw new TokenException($token, "{token} not supported");
		}
	}
	
	public function processVariable(){
		$token = $this->tokenizer->peek(1);
		if($token->type == T_WHITESPACE){
			$token = $this->tokenizer->peek(2);
		}
		
		if($token->type == T_PARENT_OPEN){
			$token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'function');
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'operator');
			$this->processParameters();
			$token = $this->tokenizer->pop();
			if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
			$this->print($token->type, $token->text, 'operator');
			
			$token = $this->tokenizer->peek();
			if($token->type == T_OBJECT_OPERATOR){
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'operator');
				$this->processMember();
			}
		}else{
			$token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'variable');
			
			$token = $this->tokenizer->peek();
			if($token->type == T_OBJECT_OPERATOR){
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'operator');
				$this->processMember();
			}
		}
		$this->processExpressionExtend();
	}
	
	public function processConstant(){
		$token = $this->tokenizer->peek();
		if(preg_match('/true|false|null/i', $token->text)){
			$token = $this->tokenizer->pop();
			$this->print($token->type, strtolower($token->text), 'keyword');
		}else{
			$token = $this->tokenizer->peek(1);
			if($token->type == T_WHITESPACE){
				$token = $this->tokenizer->peek(2);
			}
			
			if($token->type == T_PARENT_OPEN){
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'function');
				$token = $this->tokenizer->pop();
				if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'operator');
				$this->processParameters();
				$token = $this->tokenizer->pop();
				if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
				$this->print($token->type, $token->text, 'operator');
				
				$token = $this->tokenizer->peek();
				if($token->type == T_OBJECT_OPERATOR){
					$token = $this->tokenizer->pop();
					$this->print($token->type, $token->text, 'operator');
					$this->processMember();
				}
			}else{
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'constant');
				
				$token = $this->tokenizer->peek();
				if($token->type == T_DOUBLE_COLON){
					$token = $this->tokenizer->pop();
					$this->print($token->type, $token->text, 'operator');
					$this->processMember();
				}
			}
		}
		$this->processExpressionExtend();
	}
	
	public function processMember(){
		$token = $this->tokenizer->peek(1);
		if($token->type == T_WHITESPACE){
			$token = $this->tokenizer->peek(2);
		}
		
		if($token->type == T_PARENT_OPEN){
			$token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'member');
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'operator');
			$this->processParameters();
			$token = $this->tokenizer->pop();
			if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
			$this->print($token->type, $token->text, 'operator');
		}else{
			$token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, $token->type == T_VARIABLE ? 'variable' : 'member');
		}
		
		$token = $this->tokenizer->peek();
		if($token->type == T_OBJECT_OPERATOR){
			$token = $this->tokenizer->pop();
			$this->print($token->type, $token->text, 'operator');
			$this->processMember();
		}
	}
	
	public function processParameters(){
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		if(in_array($token->type, [T_PARENT_CLOSE, T_CODE_END])) return true;
		
		$this->processExpression();
		
		$token = $this->tokenizer->peek();
		if(in_array($token->type, [T_PARENT_CLOSE, T_CODE_END])) return true;
		
		if($token->type != T_SEPERATOR) throw new TokenException($token, 'Unexpected {token} expected ","');
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'operator');
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		$this->processParameters();
	}
}