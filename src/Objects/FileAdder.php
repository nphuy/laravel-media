<?php
namespace HNP\LaravelMedia\Objects;
use Illuminate\Support\Facades\Storage;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;
use HNP\LaravelMedia\Exceptions\DiskDoesNotExist;
use HNP\LaravelMedia\Exceptions\UnknownType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use HNP\LaravelMedia\Objects\UrlFile;
use Intervention\Image\Facades\Image as Image;
use Illuminate\Support\Str;

class FileAdder {
    private $file;
    private $model;
    private $conversions;
    private $watermarks;
    private $disk_name;

    public function create($model, $file, array $conversion): FileAdder{
        $this->model = $model;
        $this->file = $file;
        $this->conversions = $conversion["conversions"];
        $this->watermark = $conversion["watermark"];
        // dd($this);
        return $this;
    }
    public function createFromUrl($model, $file, ConversionCollection $conversions): FileAdder{
        $this->model = $model;
        $this->url_file = $file;
        $this->conversions = $conversion["conversions"];
        $this->watermark = $conversion["watermark"];
        $this->conversions = $conversions;
        return $this;
    }
    protected function setDiskName($disk_name): void{
        $this->disk_name = $disk_name ?? config("hnp-media.disk_name");
    }
    protected function getDiskName(){
        return $this->disk_name;
    }
    public function toMediaCollection($collection = 'default', ?string $disk_name = null){
        $this->setDiskName($disk_name);
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
        // dd($this->getDiskName());
        $disk_name = $this->getDiskName();
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

        $random_name = md5(Str::uuid());
        $random_file_name = "{$random_name}.{$extension}";
        $original_name = $this->watermark ? $random_file_name : $file_name;
        $media->original_name = $original_name;

        $media->mime_type = $mime;
        $media->disk = $disk_name;
        $media->size = $size;
        $media->extension = $extension;
        $media->custom_properties = [];
        $media = $model->media()->save($media);
        Storage::disk($disk_name)->putFileAs($media->id, $file->getFile(), $original_name);
        if($this->watermark){
            $this->addWatermark($media);
        }
        $this->performConversions($media);
        return $media;
        dd($file, $collection);
    }
    public function toMediaCollectionFromUpload($collection, $file){
        
        $disk_name = $this->getDiskName();
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
        
        $random_name = md5(Str::uuid());
        $random_file_name = "{$random_name}.{$extension}";
        $original_name = $this->watermark ? $random_file_name : $file_name;
        $media->original_name = $original_name;

        $media->mime_type = $mime;
        $media->disk = $disk_name;
        $media->size = $size;
        $media->extension = $extension;
        $media->custom_properties = [];
        $media = $model->media()->save($media);
        Storage::disk($disk_name)->putFileAs($media->id, $file, $original_name);
        if($this->watermark){
            $this->addWatermark($media);
        }
        $this->performConversions($media);
        
        return $media;
        dd($collection, $disk_name, $media);
    }
    protected function addWatermark($media){
        $watermark = $this->watermark;
        $id = $media->id;
        $file_name = $media->original_name;
        $disk_name = $media->disk;
        $img = Image::make(Storage::disk($media->disk)->get(("{$id}/{$file_name}")));
        $new_image = $img->insert($watermark->getPath(), $watermark->getPosition(),$watermark->getX(), $watermark->getY())->stream();
        // dd($new_image);
        Storage::disk($disk_name)->put("{$media->id}/{$media->file_name}", $new_image);
    }
    protected function performConversions($media){
        $conversions = $this->conversions;
        foreach($conversions as $conversion){
            if($conversion->getCollectionName() == $media->collection_name)
                PerformConversionsJob::dispatch($media, $conversion);
        }
    }
}