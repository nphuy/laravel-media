<?php
namespace HNP\LaravelMedia\Collections;

class Media extends \Illuminate\Database\Eloquent\Collection
{
    private $instance;
    public function __construct($items, $instance = null)
    {
        $this->instance = $instance;
        parent::__construct($items);
    }
}