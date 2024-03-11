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

	function __toString() : string {
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
		return H('?' .$q);
	}

	function with(string $key, $value) : self
	{
		$Ret = clone $this;
		$Ret->a[$key] = $value;
		return $Ret;
	}

	function has($key)
	{
		return array_key_exists($key, $this->a);
	}

	function without($key)
	{
		$Ret = clone $this;
		unset($Ret->a[$key]);
		return $Ret;
	}

	function __invoke(...$a) {
		return $this->with(...$a);
	}
}
