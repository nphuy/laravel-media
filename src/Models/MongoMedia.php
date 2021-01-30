<?php

namespace HNP\LaravelMedia\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;
use HNP\LaravelMedia\LaravelMediaObserver;
use Jenssegers\Mongodb\Relations\MorphTo;

class MongoMedia extends Eloquent
{
    use HasFactory;
    protected $collection = 'media';
    protected $casts = [
        'custom_properties' => 'array',
    ];
    protected $fillable = [
        "collection_name",
        "name",
        "file_name",
        "mime_type",
        "disk",
        "size"
    ];
    public static function boot()
	{
        parent::boot();
        static::observe(app(LaravelMediaObserver::class));
    }
    public function model()
    {
        return $this->morphTo();
    }
    public function getPath(){
        $id = $this->id;
        $file_name = $this->file_name;
        return Storage::disk($this->disk)->path("{$id}/{$file_name}");
    }
    public function getFullUrl($coversion_name = null){
        $id = $this->id;
        $file_name = $this->file_name;
        if(!empty($coversion_name)){
            $custom_properties = $this->custom_properties;
            // dd($custom_properties['generated_conversions']);
            if(!empty($custom_properties['generated_conversions']) && !empty($custom_properties['generated_conversions'][$coversion_name])){
                $name = $this->name;
                $extension = $this->extension;
                return Storage::disk($this->disk)->url("{$id}/conversions/{$name}_{$coversion_name}.{$extension}");
            }
        }
        return Storage::disk($this->disk)->url("{$id}/{$file_name}");
    }
    public function crop($size){
        $id = $this->id;
        $file_name = $this->file_name;

        
        $image = Image::make(Storage::disk($this->disk)->get(("{$id}/{$file_name}")))->fit($size["width"], $size["height"], function ($constraint) {
            $constraint->upsize();
        })->stream();
        $new_filename = "{$this->name}_{$size['name']}.{$this->extension}";
        Storage::disk($this->disk)->put("{$this->id}/conversions/{$new_filename}", $image);
        return $this;
    }
}
