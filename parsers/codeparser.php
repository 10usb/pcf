<?php
namespace pcf\parsers;

use pcf\TokenException;
use pcf\Whitespace;

class CodeParser extends ExpressionParser {
	/**
	 * 
	 * @var integer
	 */
	protected $indent;
	
	/**
	 * 
	 * @var pcf\Whitespace
	 */
	protected $whitespace;
	
	/**
	 * 
	 * @param pcf\Tokenizer $tokenizer
	 */
	public function __construct($tokenizer, $indent){
		parent::__construct($tokenizer);
		$this->indent		= $indent;
		$this->whitespace	= new Whitespace();
	}

	
	public function processCodeLines(){
		$this->whitespace->clear();
		while($token = $this->tokenizer->peek()){
			if($this->processCodeMultiLines($token)){
				break;
			}
		}
	}
	
	public function processCodeMultiLines($token){
		if($token->type == T_BLOCK_ClOSE){
			return true;
		}elseif($token->type == T_WHITESPACE){
			$this->tokenizer->pop();
			$this->whitespace->append($token->text);
		}else{
			if($this->processCode($this->whitespace->get($this->indent))){
				$this->print(T_WHITESPACE, "\n", 'whitespace');
				$this->whitespace->clear();
			}else return true;
		
		}
		return false;
	}
	
	public function processCode($whitespace = false){
		$token = $this->tokenizer->peek();
		switch($token->type){
			case T_BLOCK_CLOSE: return false;
			case T_VARIABLE:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processVariable();
				$token = $this->tokenizer->pop();
				if($token->type!=T_CODE_END || $token->text!=';') throw new TokenException($token, 'Unexpected {token} expected ";"');
				$this->print($token->type, $token->text, 'operator');
			break;
			case T_STRING:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processConstant();
				$token = $this->tokenizer->pop();
				if($token->type!=T_CODE_END || $token->text!=';') throw new TokenException($token, 'Unexpected {token} expected ";"');
				$this->print($token->type, $token->text, 'operator');
			break;
			case T_ECHO:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'keyword');
				$token = $this->tokenizer->peek();
				if($token->type == T_WHITESPACE) $this->tokenizer->pop();
				$this->print(T_WHITESPACE, " ", 'whitespace');
				$this->processParameters();
				$token = $this->tokenizer->pop();
				if($token->type!=T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
				$this->print($token->type, $token->text, 'operator');
			break;
			case T_INCLUDE: case T_INCLUDE_ONCE: case T_REQUIRE: case T_REQUIRE_ONCE:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'keyword');
				$token = $this->tokenizer->peek();
				if($token->type == T_WHITESPACE) $this->tokenizer->pop();
				$this->print(T_WHITESPACE, " ", 'whitespace');
				$this->processExpression();
				$token = $this->tokenizer->pop();
				if($token->type!=T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
				$this->print($token->type, $token->text, 'operator');
			break;
			case T_RETURN; case T_BREAK: case T_CONTINUE:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'keyword');
				
				$token = $this->tokenizer->peek();
				if($token->type == T_WHITESPACE){
					$token = $this->tokenizer->peek(1);
				}
				if($token->type!=T_CODE_END){
					if($this->tokenizer->peek()->type == T_WHITESPACE) $this->tokenizer->pop();
					$this->print(T_WHITESPACE, " ", 'whitespace');
					$this->processExpression();
				}
				
				$token = $this->tokenizer->pop();
				if($token->type!=T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
				$this->print($token->type, $token->text, 'operator');
			break;
			case T_WHILE:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processWhile();
			break;
			case T_IF:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processIf();
			break;
			case T_TRY:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processTry();
			break;
			case T_DO:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processDo();
			break;
			case T_FOR:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processFor();
			break;
			case T_FOREACH:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$this->processForeach();
			break;
			case T_SWITCH:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$parser = new SwitchParser($this->tokenizer, $this->indent);
				$parser->process();
			break;
			case T_COMMENT:
				if($whitespace) $this->print(T_WHITESPACE, $whitespace, 'whitespace');
				$token = $this->tokenizer->pop();
				$this->print($token->type, $token->text, 'comment');
			break;
			default: throw new TokenException($token, "{token} not supported");
		}
		return true;
	}
	
	public function processBlock(){
		$token = $this->tokenizer->pop();
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->print($token->type, $token->text, 'operator');
		$this->print(T_WHITESPACE, "\n", 'whitespace');
		$this->indent++;
		$this->processCodeLines();
		$this->indent--;
		if($this->indent) $this->print(T_WHITESPACE, str_repeat("\t", $this->indent), 'whitespace');
		$token = $this->tokenizer->pop();
		if($token->type != T_BLOCK_CLOSE) throw new TokenException($token, 'Unexpected {token} expected "}"');
		$this->print($token->type, $token->text, 'operator');
	}

	public function processWhile(){
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
		if($token->type == T_WHITESPACE) $token= $this->tokenizer->popAndPeek();
		
		if($token->type == T_BLOCK_OPEN){
			$this->processBlock();
		}else{
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processCode();
		}
	}

	public function processFor(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop(2);
		
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->tokenizer->pop();
		$this->print($token->type, $token->text, 'group');
		
		$this->processParameters();

		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
		$this->print($token->type, $token->text, 'operator');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $this->tokenizer->pop();
		$this->processExpression();
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
		$this->print($token->type, $token->text, 'operator');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $this->tokenizer->pop();
		$this->processParameters();
		
		$token = $this->tokenizer->pop();
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'group');

		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		
		if($token->type == T_BLOCK_OPEN){
			$this->processBlock();
		}else{
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processCode();
		}
	}

	public function processForeach(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop(2);
		
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->tokenizer->pop();
		$this->print($token->type, $token->text, 'group');
		
		$this->processExpression();
		
		$this->print(T_WHITESPACE, " ", 'whitespace');
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_VARIABLE) throw new TokenException($token, 'Unexpected {token} expected T_VARIABLE');
		$this->print($token->type, $token->text, 'variable');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		
		if($token->type = T_DOUBLE_ARROW){
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->print($token->type, $token->text, 'operator');
			$this->print(T_WHITESPACE, " ", 'whitespace');
			
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
			if($token->type != T_VARIABLE) throw new TokenException($token, 'Unexpected {token} expected T_VARIABLE');
			$this->print($token->type, $token->text, 'variable');
			
			$token = $this->tokenizer->pop();
			if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		}
		
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'group');

		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token= $this->tokenizer->popAndPeek();
		
		if($token->type == T_BLOCK_OPEN){
			$this->processBlock();
		}else{
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processCode();
		}
	}
	
	public function processIf(){
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
		if($token->type == T_WHITESPACE) $token= $this->tokenizer->popAndPeek();
		
		if($token->type == T_BLOCK_OPEN){
			$this->processBlock();
		}else{
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processCode();
		}
		
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE){
			$token = $this->tokenizer->peek(1);
			
			if($token->type == T_ELSE){
				$this->tokenizer->pop();
				$this->processElse();
			}elseif($token->type == T_ELSEIF){
				$this->tokenizer->pop();				
				$this->processIf();
			}
		}elseif($token->type == T_ELSE){
			$this->processElse();
		}elseif($token->type == T_ELSEIF){
			$this->processIf();
		}
	}
	
	public function processElse(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		
		if($token->type == T_BLOCK_OPEN){
			$this->processBlock();
		}else{
			$this->print(T_WHITESPACE, " ", 'whitespace');
			$this->processCode();
		}
	}
	
	public function processTry(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->processBlock();
		
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		
		$this->processCatch();
	}
	
	public function processCatch(){
		$token = $this->tokenizer->pop();
		if($token->type != T_CATCH) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->print($token->type, $token->text, 'keyword');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->print($token->type, $token->text, 'operator');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_STRING) throw new TokenException($token, 'Unexpected {token} expected T_STRING');
		$this->print($token->type, $token->text, 'constant');
		
		$token = $this->tokenizer->pop();
		if($token->type != T_WHITESPACE) throw new TokenException($token, 'Unexpected {token} expected T_WHITESPACE');
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		$token = $this->tokenizer->pop();
		if($token->type != T_VARIABLE) throw new TokenException($token, 'Unexpected {token} expected T_VARIABLE');
		$this->print($token->type, $token->text, 'variable');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'operator');
		
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->processBlock();

		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE){
			$token = $this->tokenizer->peek(1);
		}
		if($token->type == T_CATCH){
			if($this->tokenizer->peek()->type == T_WHITESPACE) $this->tokenizer->pop();
			$this->processCatch();
		}
	}
	
	public function processDo(){
		$token = $this->tokenizer->pop();
		$this->print($token->type, $token->text, 'keyword');
		
		$token = $this->tokenizer->peek();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->popAndPeek();
		$this->print(T_WHITESPACE, " ", 'whitespace');
		
		if($token->type != T_BLOCK_OPEN) throw new TokenException($token, 'Unexpected {token} expected "{"');
		$this->processBlock();
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		
		if($token->type != T_WHILE) throw new TokenException($token, 'Unexpected {token} expected "while"');
		$this->print($token->type, $token->text, 'keyword');
		
		$token = $this->tokenizer->pop();
		if($token->type == T_WHITESPACE) $token = $this->tokenizer->pop();
		
		if($token->type != T_PARENT_OPEN) throw new TokenException($token, 'Unexpected {token} expected "("');
		$this->print($token->type, $token->text, 'operator');
		$this->processExpression();		
		$token = $this->tokenizer->pop();
		if($token->type != T_PARENT_CLOSE) throw new TokenException($token, 'Unexpected {token} expected ")"');
		$this->print($token->type, $token->text, 'operator');

		$token = $this->tokenizer->pop();
		if($token->type!=T_CODE_END) throw new TokenException($token, 'Unexpected {token} expected ";"');
		$this->print($token->type, $token->text, 'operator');
	}
}