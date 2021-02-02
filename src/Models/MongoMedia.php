<?php

namespace HNP\LaravelMedia\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;
use HNP\LaravelMedia\LaravelMediaObserver;
use Jenssegers\Mongodb\Relations\MorphTo;
use HNP\LaravelMedia\Traits\Regenerate as RegenerateTrait;
use HNP\LaravelMedia\Collections\Media as MediaCollection;
use HNP\LaravelMedia\Media as MediaInteface;

class MongoMedia extends Eloquent implements MediaInteface
{
    use HasFactory;
    use RegenerateTrait;
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
    public function newCollection(array $models = [])
    {
        return new MediaCollection($models, self::class);
    }
    public function model()
    {
        return $this->morphTo();
    }
    public function destroyConversionFolder(): void{
        $id = $this->id;
        if( Storage::disk($this->disk)->exists("{$id}/conversions")){
            Storage::disk($this->disk)->deleteDirectory("{$id}/conversions");
            // Storage::disk($this->disk)->makeDirectory("{$id}/conversions");
        }
        
    }
    public function getConversionPath(): string{
        $id = $this->id;
        return Storage::disk($this->disk)->path("{$id}/conversions");
    }
    public function getPath(): string{
        $id = $this->id;
        $file_name = $this->file_name;
        return Storage::disk($this->disk)->path("{$id}/{$file_name}");
    }

    public function getFullUrl(string $coversion_name = null): string{
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
    
}
