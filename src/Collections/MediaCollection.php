<?php
namespace HNP\LaravelMedia\Collections;

class MediaCollection extends \Illuminate\Database\Eloquent\Collection
{
    public function __construct($items)
    {
        parent::__construct($items);
    }
}