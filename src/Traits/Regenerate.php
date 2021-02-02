<?php
namespace HNP\LaravelMedia\Traits;
use HNP\LaravelMedia\Jobs\PerformConversionsJob;

trait Regenerate
{
    public function regenerate(){
        $model = $this->model;
        $conversions = $model->getConversions()->where("collection", $this->collection_name);
        $this->destroyConversionFolder();
        $this->custom_properties = [];
        $this->save();
        if($conversions->count()){
            $this->performConversions($model, $this, $conversions);
        }
        
    }
    protected function performConversions($model, $media, $conversions){
        foreach($conversions as $conversion){
            PerformConversionsJob::dispatch($media, $conversion);
        }
    }
}