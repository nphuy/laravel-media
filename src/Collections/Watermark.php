<?php
namespace HNP\LaravelMedia\Collections;

class Watermark extends \Illuminate\Database\Eloquent\Collection
{
    public function __construct($items)
    {
        parent::__construct($items);
    }
}