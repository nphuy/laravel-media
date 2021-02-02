<?php
namespace HNP\LaravelMedia\Objects;
use Illuminate\Support\Facades\Storage;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;
use HNP\LaravelMedia\Exceptions\DiskDoesNotExist;
use HNP\LaravelMedia\Exceptions\UnknownType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use HNP\LaravelMedia\Objects\UrlFile;

class FileAdder {
    private $file;
    private $model;
    private $conversions;
    public function create($model, $file, ConversionCollection $conversions): FileAdder{
        $this->model = $model;
        $this->file = $file;
        $this->conversions = $conversions;
        return $this;
    }
    public function createFromUrl($model, $file, ConversionCollection $conversions): FileAdder{
        $this->model = $model;
        $this->url_file = $file;
        $this->conversions = $conversions;
        return $this;
    }
    public function usingName($file_name): FileAdder{
        dd($file_name);
    }
    public function toMediaCollection($collection = 'default'){
        $file = $this->file;
        if ($file instanceof UrlFile) {
            return $this->toMediaCollectionFromUrl($collection, $file);
        }
        if ($file instanceof UploadedFile) {
            return $this->toMediaCollectionFromUpload($collection, $file);
        }
        throw UnknownType::create();
        dd($this->file instanceof UrlFile);
    }
    public function toMediaCollectionFromUrl($collection, $file){
        $disk_name = config("hnp-media.disk_name");
        $model = $this->model;
        if(empty(config("filesystems.disks.{$disk_name}"))){
            throw DiskDoesNotExist::create($disk_name);
        }
        $mediaClass = config('hnp-media.media_model');
        $media = new $mediaClass();
        // dd($file->getMimeType());
        $file_name = $file->getClientOriginalName();
        $extension = $file->getExtension();
        $name = $file->getFileName();
        $mime = $file->getMimeType();
        $size = $file->getSize();
        // dd($file->getFile());
        $media->collection_name = $collection;
        $media->name = $name;
        $media->file_name = $file_name;
        $media->mime_type = $mime;
        $media->disk = $disk_name;
        $media->size = $size;
        $media->extension = $extension;
        $media->custom_properties = [];
        $media = $model->media()->save($media);
        Storage::disk($disk_name)->putFileAs($media->id, $file->getFile(), $file_name);
        $this->performConversions($media);
        return $media;
        dd($file, $collection);
    }
    public function toMediaCollectionFromUpload($collection, $file){
        
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