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
use HNP\LaravelMedia\Collections\Watermark as WatermarkCollection;
use HNP\LaravelMedia\Objects\FileAdder;
use HNP\LaravelMedia\Objects\Conversion as ConversionObject;
use HNP\LaravelMedia\Objects\Watermark as WatermarkObject;
use HNP\LaravelMedia\Exceptions\InvalidUrl;
use HNP\LaravelMedia\Downloaders\DefaultDownloader;
use HNP\LaravelMedia\Exceptions\MimeTypeNotAllowed;
use HNP\LaravelMedia\Exceptions\WatermarkPositionNotExitst;
use HNP\LaravelMedia\Objects\UrlFile;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Eloquent\SoftDeletes as MongoSoftDeletes;
use HNP\LaravelMedia\HasMedia;
use HNP\LaravelMedia\Media as MediaInteface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait InteractsWithMedia
{
    
    protected $conversions = [];
    protected $watermarks = [];
    protected bool $deletePreservingMedia = false;
    protected static array $watermark_positions = ["top-left", "top", "top-right", "left", "center", "right", "bottom-left", "bottom", "bottom-right"];

    public static function bootInteractsWithMedia()
    {
        static::deleting(function (HasMedia $model) {
            if ($model->shouldDeletePreservingMedia()) {
                return;
            }
            // dd(class_uses_recursive($model));
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                if (!$model->forceDeleting) {
                    return;
                }
            }

            $model->media()->cursor()->each(fn (MediaInteface $media) => $media->delete());
        });
    }
    public function deletePreservingMedia(): bool
    {
        $this->deletePreservingMedia = true;

        return $this->delete();
    }
    public function shouldDeletePreservingMedia(): bool
    {
        return $this->deletePreservingMedia ?? false;
    }
    public function registerMediaConversions(): void{
    }
    protected function addConversion(string $name, int $width, int $height){
        // dd($this->conversions[] = app(ConversionObject::class)->create($name, $width, $height));
        return $this->conversions[] = app(ConversionObject::class)->create($name, $width, $height);
    }

    protected function addWatermark(string $position, int $x, int $y){
        if(!in_array($position, self::$watermark_positions))
            throw WatermarkPositionNotExitst::create($position);
        return $this->watermarks[] = app(WatermarkObject::class)->create($position, $x, $y);
    }
    public function getConversions(): ConversionCollection{
        return new ConversionCollection($this->conversions);
    }
    public function getWatermarks(): WatermarkCollection{
        return new WatermarkCollection($this->watermarks);
    }

    public function getWatermark(){
        return $this->getWatermarks()->first();
    }

    public function getMediaConversions(){
        $this->registerMediaConversions();
        $conversions= $this->getConversions();
        $watermark = $this->getWatermark();
        return compact("conversions", "watermark");
    }
    
    protected function getDiskName(){
        return !empty($this->media_diskname) ? $this->media_diskname : "public";
    }
    public function media(){
        return $this->morphMany(config("hnp-media.media_model"), 'model');
    }
    public function addMedia(UploadedFile $file): FileAdder{
        // dd($this->getConversions());
        // dd($file);
        return app(FileAdder::class)->create($this, $file, $this->getMediaConversions());
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
            ->create($this, app(UrlFile::class)->create($temporaryFile,  $filename, pathinfo($file_path, PATHINFO_EXTENSION)), $this->getMediaConversions());
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

    public function getFirstMedia($collection = "default"): ?MediaInteface{
        return $this->media->where("collection_name", $collection)->first();
    }
    public function getMedia($collection = "default"): MediaCollection{
        $models = $this->media->where("collection_name",$collection);
        return new MediaCollection($models, self::class);
    }
}