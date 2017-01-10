<?php
namespace pcf;

class TokenException extends \Exception {
	public function __construct($token, $message, $previous = null) {
		$token_name = '"'.$token->text.'" of type '.token_name($token->type).'('.$token->type.')';
		parent::__construct(str_replace('{token}', $token_name, $message), $code, $previous);
	}
}