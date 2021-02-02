<?php
namespace HNP\LaravelMedia\Objects;

class Conversion
{
    private $name;
    private $width;
    private $height;
    public $collection = 'default';

    public function getName(){
        return $this->name;
    }
    public function getWidth(){
        return $this->width;
    }
    public function getHeight(){
        return $this->height;
    }
    public function getCollectionName(){
        return $this->collection;
    }
    public function create(string $name, int $width, int $height): Conversion{
        $this->name = $name;
        $this->width = $width;
        $this->height = $height;
        return $this;
    }
    public function toCollection($name = 'default'){
        $this->collection = $name;
        return $this;
    }
}