<?php
namespace HNP\LaravelMedia\Downloaders;

interface Downloader
{
    public function getTempFile(string $url): string;
}