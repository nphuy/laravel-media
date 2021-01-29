<?php

namespace HNP\LaravelMedia;
use Illuminate\Support\Facades\Storage;

class LaravelMediaObserver{

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