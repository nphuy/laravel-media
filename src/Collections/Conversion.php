<?php
namespace HNP\LaravelMedia\Collections;

class Conversion extends \Illuminate\Database\Eloquent\Collection
{
    public function __construct($items)
    {
        parent::__construct($items);
    }
}