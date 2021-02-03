<?php
namespace HNP\LaravelMedia\Exceptions;

class WatermarkPositionNotExitst extends FileCannotBeAdded
{
    public static function create($position): self
    {
        return new static("There is no watermark position named `{$position}`");
    }
}