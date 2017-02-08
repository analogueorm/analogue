<?php

namespace TestApp;

use Analogue\ORM\Entity;
use Illuminate\Support\Collection;

class Tag extends Entity
{

	public function __construct($text)
	{
		$this->text = $text;
	}

}
