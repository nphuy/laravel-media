<?php 
namespace HNP\LaravelMedia\Traits;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use HNP\LaravelMedia\Models\Media;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use HNP\LaravelMedia\Collections\Media as MediaCollection;
use HNP\LaravelMedia\Collections\Conversion as ConversionCollection;
use HNP\LaravelMedia\Objects\FileAdder;
use HNP\LaravelMedia\Objects\Conversion as ConversionObject;
use HNP\LaravelMedia\Exceptions\InvalidUrl;
use HNP\LaravelMedia\Downloaders\DefaultDownloader;
use HNP\LaravelMedia\Exceptions\MimeTypeNotAllowed;
use HNP\LaravelMedia\Objects\UrlFile;

trait HasMedia
{
    
    protected $conversions = [];
    
    protected function registerMediaConversions(): void{
    }
    protected function addConversion(string $name, int $width, int $height){
        return $this->conversions[] = app(ConversionObject::class)->create($name, $width, $height);
    }
    public function getConversions(){
        $this->registerMediaConversions();
        // dd($this->conversions);
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
        return $this->morphMany(config("hnp-media.media_model"), 'model');
    }
    public function addMedia($file){
        // dd($this->getConversions());
        return app(FileAdder::class)->create($this, $file, $this->getConversions());
    }

    public function addMediaFromUrl(string $url, ...$allowedMimeTypes): FileAdder
    {
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            throw InvalidUrl::doesNotStartWithProtocol($url);
        }
        $temporaryFile = app(DefaultDownloader::class)->getTempFile($url);
        $this->guardAgainstInvalidMimeType($temporaryFile, $allowedMimeTypes);
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $filename = urldecode($filename);

        if ($filename === '') {
            $filename = 'file';
        }
        $mediaExtension = explode('/', mime_content_type($temporaryFile));

        if (!Str::contains($filename, '.')) {
            $filename = "{$filename}.{$mediaExtension[1]}";
        }
        
        $file_path = basename(parse_url($url)['path']);
        return app(FileAdder::class)
            ->create($this, app(UrlFile::class)->create($temporaryFile,  $filename, pathinfo($file_path, PATHINFO_EXTENSION)), $this->getConversions());
    }

    protected function guardAgainstInvalidMimeType(string $file, ...$allowedMimeTypes)
    {
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        if (empty($allowedMimeTypes)) {
            return;
        }

        $validation = Validator::make(
            ['file' => new File($file)],
            ['file' => 'mimetypes:' . implode(',', $allowedMimeTypes)]
        );

        if ($validation->fails()) {
            throw MimeTypeNotAllowed::create($file, $allowedMimeTypes);
        }
    }

    public function getFirstMedia($collection = "default"){
        return $this->media()->whereCollectionName($collection)->first();
    }
    public function getMedia($collection = "default"){
        $models = $this->media()->whereCollectionName($collection)->get();
        return new MediaCollection($models, self::class);
    }
}