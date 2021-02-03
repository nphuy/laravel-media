<?php
namespace HNP\LaravelMedia\Objects;
use HNP\LaravelMedia\Exceptions\WatermarkPathNotExist;

class Watermark
{
    private $position;
    private $x;
    private $y;
    public $collection = 'default';
    private $path;

    public function getPath(){
        return $this->path;
    }

    public function getPosition(){
        return $this->position;
    }
    public function getX(){
        return $this->x;
    }
    public function getY(){
        return $this->y;
    }
    public function getCollectionName(){
        return $this->collection;
    }
    public function create(string $position, int $x, int $y): Watermark{
        $this->path = config("hnp-media.default_watermark");
        $this->position = $position;
        $this->x = $x;
        $this->y = $y;
        return $this;
    }
    public function toCollection($name = 'default'): Watermark{
        $this->collection = $name;
        return $this;
    }
    public function withPath(string $path): Watermark{
        if(!file_exists($path))
            throw WatermarkPathNotExist::create($path);
        $this->path = $path;
        return $this;
    }
}