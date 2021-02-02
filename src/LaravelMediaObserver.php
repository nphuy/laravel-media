<?php

namespace HNP\LaravelMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaravelMediaObserver{

    public function creating($media){
        $media->uuid = (string) Str::uuid();
    }
    public function created($media)
    {
        
    }
    public function updated($media)
    {
    }
    public function deleting($media)
    {
        Storage::disk($media->disk)->deleteDirectory($media->id);
    }
}