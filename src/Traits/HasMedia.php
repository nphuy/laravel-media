<?php 
namespace HNP\LaravelMedia\Traits;
use Illuminate\Support\Str;
use HNP\LaravelMedia\Models\Media;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use HNP\LaravelMedia\Collections\Media as MediaCollection;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Objects\FileAdder;


trait HasMedia
{
    
    protected $conversions = [];
    protected function addConversion(string $name, int $width, int $height){
        return $this->conversions[] = [
            "name"=>$name,
            "width"=>$width,
            "height"=>$height
        ];
    }
    public function getConversions(){
        $this->registerMediaConversions();
        return new ConversionCollection($this->conversions);
    }
    public function newCollection(array $models = [])
    {
         return new MediaCollection($models, self::class);
    }
    protected function getDiskName(){
        return !empty($this->media_diskname) ? $this->media_diskname : "public";
    }
    public function media(){
        return $this->morphMany(config("hnp_media.media_model"), 'model');
    }
    public function addMedia($file){
        // dd($this->getConversions());
        return app(FileAdder::class)->create($this, $file, $this->getConversions());
    }
    public function getFirstMedia($collection = "default"){
        return $this->media()->whereCollectionName($collection)->first();
    }
    public function getMedia($collection = "default"){
        $models = $this->media()->whereCollectionName($collection)->get();
        return new MediaCollection($models, self::class);
    }
}