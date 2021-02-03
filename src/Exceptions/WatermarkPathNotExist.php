<?php
namespace HNP\LaravelMedia\Exceptions;

class WatermarkPathNotExist extends FileCannotBeAdded
{
    public static function create($path): self
    {
        return new static("Watermark path `{$path}` does not exist");
    }
}