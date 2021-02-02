<?php
namespace HNP\LaravelMedia;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Objects\FileAdder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use HNP\LaravelMedia\Collections\Media as MediaCollection;
use HNP\LaravelMedia\Media;

interface HasMedia{
    public function registerMediaConversions(): void;
    public function shouldDeletePreservingMedia(): bool;
    public function deletePreservingMedia(): bool;
    public function getConversions(): ConversionCollection;
    public function addMedia(UploadedFile $file): FileAdder;
    public function addMediaFromUrl(string $url, ...$allowedMimeTypes): FileAdder;
    public function getMedia($collection = "default"): MediaCollection;
    public function getFirstMedia($collection = "default"): ?Media;
}