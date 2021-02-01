<?php
namespace HNP\LaravelMedia\Downloaders;
use HNP\LaravelMedia\Exceptions\UnreachableUrl;

class DefaultDownloader implements Downloader{
    public function getTempFile(string $url): string
    {
        if (!$stream = @fopen($url, 'r')) {
            throw UnreachableUrl::create($url);
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'laravel-media');
        // dd($temporaryFile);
        file_put_contents($temporaryFile, $stream);

        return $temporaryFile;
    }
}