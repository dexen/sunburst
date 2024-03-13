<?php

function td(...$a) { echo '<pre>'; foreach ($a as $v) var_dump($a); echo "\n--\ntd()"; die(3); }
function U($str) { return rawurlencode($str); }
function H($str) { return htmlspecialchars($str); }
function Hcdata($str) {
		# i suppose HTML5 extended that to "</TAG-NAME", but ehhhh
	if (strpos($str, '</') === false)
		return $str;
	else
		throw new \Exception('Unhandled sequence inside CDATA');
}

interface HCtxProvider
{
	function hctxSelector() : string;
}

class HCtx {
	protected $a = [];

	function __construct() {}

	function toQueryString() : string {
		$a = [];
		foreach ($this->a as $key => $value) {
			if (is_string($value))
				$selector = $value;
			else if (is_int($value))
				$selector = $value;
			else if ($value instanceof HCtxProvider)
				$selector = $value->hctxSelector();
			else
				throw new \Exception(sprintf('unhandled value type "%s" for key "%s"', gettype($value), $key));
			$a[$key] = $selector; }
		$q = http_build_query($a);
		return '?' .$q;
	}

	function __toString() : string {
		return H($this->toQueryString());
	}

	function with(...$a) : self
	{
		$Ret = clone $this;
		while ($a) {
			$key = array_shift($a);
			$Ret->a[$key] = array_shift($a); }
		return $Ret;
	}

	function has($key)
	{
		return array_key_exists($key, $this->a);
	}

	function without(...$keys)
	{
		$Ret = clone $this;
		foreach ($keys as $key)
			unset($Ret->a[$key]);
		return $Ret;
	}

	function __invoke(...$a) {
		return $this->with(...$a);
	}

	function asHiddenInputsH() : string {
		$a = [];
		foreach ($this->a as $key => $value) {
			if (is_string($value))
				$selector = $value;
			else if (is_int($value))
				$selector = $value;
			else if ($value instanceof HCtxProvider)
				$selector = $value->hctxSelector();
			else
				throw new \Exception(sprintf('unhandled value type "%s" for key "%s"', gettype($value), $key));
			$a[] = '<input type="hidden" name="' .H($key) .'" value="' .H($selector) .'"/>'; }
		return implode($a);
	}
}
