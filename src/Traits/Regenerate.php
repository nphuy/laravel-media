<?php
namespace HNP\LaravelMedia\Traits;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;
use Intervention\Image\Exception\NotReadableException;

trait Regenerate
{
    public function regenerate(){
        try {
            $model = $this->model;
            $model->registerMediaConversions();
            $watermark = $model->getWatermark();
            // dd($watermark);
            $id = $this->id;
            $disk_name = $this->disk;
            $file_name = $this->file_name;
            $original_name = $this->original_name;
            
            if($file_name != $original_name){
                if(Storage::disk($disk_name)->exists("{$id}/{$original_name}")){
                    if(Storage::disk($disk_name)->exists("{$id}/{$file_name}")){
                        Storage::disk($disk_name)->delete("{$id}/{$file_name}");
                    }
                    if($original_name && Storage::disk($disk_name)->exists("{$id}/{$original_name}")){
                        Storage::disk($disk_name)->copy("{$id}/{$original_name}", "{$id}/{$file_name}");
                    }
                }
            }
        
        
            if($watermark){
                if(!Storage::disk($disk_name)->exists("{$id}/{$original_name}") || !$this->original_name || $original_name == $file_name){
                    $random_name = md5(Str::uuid());
                    $random_file_name = "{$random_name}.{$this->extension}";
                    $original_name = $random_file_name;
                    $this->original_name = $original_name;
                    if(!Storage::disk($disk_name)->exists("{$id}/{$original_name}") && Storage::disk($disk_name)->exists("{$id}/{$file_name}")){
                        Storage::disk($disk_name)->copy("{$id}/{$file_name}", "{$id}/{$original_name}");
                    }
                    // 
                    

                }
                if(Storage::disk($disk_name)->exists("{$id}/{$original_name}")){
                    try{
                        $img = Image::make(Storage::disk($disk_name)->get(("{$id}/{$original_name}")));
                        $new_image = $img->insert($watermark->getPath(), $watermark->getPosition(),$watermark->getX(), $watermark->getY())->stream();
                        Storage::disk($disk_name)->put("{$id}/{$file_name}", $new_image);
                    }catch(NotReadableException $e){

                    }
                    
                }
                
                // Storage::copy('old/file.jpg', 'new/file.jpg');
            }else{
                Storage::disk($disk_name)->delete("{$id}/{$original_name}");
                $this->original_name = $file_name;
            }
            
            $conversions = $model->getConversions()->where("collection", $this->collection_name);
            $this->destroyConversionFolder();
            $this->custom_properties = [];
            $this->save();
            // dd($model->getWatermark());
            if($conversions->count()){
                $this->performConversions($model, $this, $conversions);
            }
        } catch (Exception $exception) {
        }
        
        
    }
    protected function performConversions($model, $media, $conversions){
        foreach($conversions as $conversion){
            PerformConversionsJob::dispatch($media, $conversion);
        }
    }
}