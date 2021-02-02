<?php
namespace HNP\LaravelMedia\Traits;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;

trait Regenerate
{
    public function regenerate(){
        // dd($this->getConversionPath());
        $model = $this->model;
        // dd($this);
        $conversions = $model->getConversions()->where("collection", $this->collection_name);
        $this->destroyConversionFolder();
        $this->custom_properties = [];
        $this->save();
        if($conversions->count()){
            $this->performConversions($model, $this, $conversions);
        }
        
    }
    protected function performConversions($model, $media, $conversions){
        // $model = $media->model;
        // dd($model);
        // $conversions = $model->getConversions();
        foreach($conversions as $conversion){
            // dd($media, $conversion);
            PerformConversionsJob::dispatch($media, $conversion);
        }
    }
}