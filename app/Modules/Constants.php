<?php

namespace App\Modules;

use Nette;

class Constants
{
    public $constants;

    public function __construct(array $constants)
    {
        $this->constants = $constants;
    }
}

