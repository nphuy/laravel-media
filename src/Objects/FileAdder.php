<?php
namespace HNP\LaravelMedia\Objects;
use Illuminate\Support\Facades\Storage;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;
use \HNP\LaravelMedia\Exceptions\DiskDoesNotExist;

class FileAdder{
    private $file;
    private $model;
    private $conversions;
    public function create($model, $file, ConversionCollection $conversions): FileAdder{
        $this->model = $model;
        $this->file = $file;
        $this->conversions = $conversions;
        return $this;
    }
    public function toMediaCollection($collection = "default"){
        
        $disk_name = config("hnp-media.disk_name");
        $file = $this->file;
        $model = $this->model;
        if(empty(config("filesystems.disks.{$disk_name}"))){
            throw DiskDoesNotExist::create($disk_name);
        }
        $mediaClass = config('hnp-media.media_model');
        $media = new $mediaClass();
        $file_name = $file->getClientOriginalName();
        $extension = $file->extension();
        $name = str_replace(".{$extension}", "", $file_name);
        $mime = $file->getMimeType();
        $size = $file->getSize();

        $media->collection_name = $collection;
        $media->name = $name;
        $media->file_name = $file_name;
        $media->mime_type = $mime;
        $media->disk = $disk_name;
        $media->size = $size;
        $media->extension = $extension;
        $media->custom_properties = [];
        $media = $model->media()->save($media);
        Storage::disk($disk_name)->putFileAs($media->id, $file, $file_name);
        
        $this->performConversions($media);
        
        return $media;
        dd($collection, $disk_name, $media);
    }
    protected function performConversions($media){
        $conversions = $this->conversions;
        foreach($conversions as $conversion){
            if($conversion->getCollectionName() == $media->collection_name)
                PerformConversionsJob::dispatch($media, $conversion);
        }
    }
}