<?php

namespace tempest\templating;

use \tempest\templating\Template;


class Token
{

	const B_ESCAPE = '!';
	const B_EMPTY = '?';


	private $base;
	private $behaviour;
	private $context;
	private $parts;
	private $behaviours;
	private $hooks;


	public function __construct($stack, $index)
	{
		$this->base = $stack[0][$index];
		$this->context = $stack[2][$index];
		$this->context = ltrim($this->context, '@');
		$this->behaviours = str_split($stack[1][$index]);
		$this->hooks = preg_split('/[\|\s]+/', trim($stack[4][$index], '| '));

		$parts = preg_split(PATTERN_DOTS, trim($stack[3][$index], '.'));

		foreach($parts as $part)
		{
			$this->parts[] = new TokenPart($part);
		}
	}


	public function replace($subject, $value)
	{
		return str_replace($this->base, $this->toText($value), $subject);
	}


	private function toText($value)
	{
		if(is_array($value))
		{
			$value = '';
			trigger_error("Attempted insertion of array into token <code>$this->base</code>.");
		}

		if(is_object($value))
		{
			if(method_exists($value, '__toString')) $value = $value->__toString();
			else
			{
				$value = '';
				trigger_error("Attempted insertion of <code>" . get_class($value) . "</code> into token <code>$this->base</code> where a <code>__toString()</code> method was not defined.");
			}
		}

		if($value === true) $value = 'true';
		if($value === false) $value = 'false';
		if($value === null) $value = 'null';


		// The value needs to be escaped.
		if(in_array(self::B_ESCAPE, $this->behaviours)) $value = htmlspecialchars($value);


		// The value should be interpreted as an empty string if it is null or empty.
		if(in_array(self::B_EMPTY, $this->behaviours))
		{
			if($value === 'null') return '';
		}


		// Apply hooks.
		$hookHandler = Template::getHookHandler();
		foreach($this->hooks as $hook)
		{
			if(strlen($hook) === 0) continue;

			if(method_exists($hookHandler, $hook)) $value = $hookHandler->$hook($value);
			else trigger_error("<code>" . get_class($hookHandler) . "</code> does not define a hook named <code>$hook</code>.");
		}


		return $value;
	}


	public function isContextual(){ return strlen($this->context) > 0; }
	public function isMultipart(){ return count($this->parts) > 1; }


	public function getBase(){ return $this->base; }
	public function getContext(){ return $this->context; }
	public function getParts(){ return $this->parts; }

}