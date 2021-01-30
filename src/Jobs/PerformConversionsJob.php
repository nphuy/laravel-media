<?php

namespace HNP\LaravelMedia\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;

class PerformConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $media, $conversion;
    public function __construct($media, $conversion)
    {
        $this->media = $media;
        $this->conversion = $conversion;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;
        $conversion = $this->conversion;
        $id = $media->id;
        $file_name = $media->file_name;
        $conversion_name = $conversion->getName();
        $conversion_width = $conversion->getWidth();
        $conversion_height = $conversion->getHeight();
        
        $image = Image::make(Storage::disk($media->disk)->get(("{$id}/{$file_name}")))->fit($conversion_width, $conversion_height, function ($constraint) {
            $constraint->upsize();
        })->stream();
        $new_filename = "{$media->name}_{$conversion_name}.{$media->extension}";
        Storage::disk($media->disk)->put("{$media->id}/conversions/{$new_filename}", $image);
        $custom_properties = $media->custom_properties;
        if(!empty($custom_properties['generated_conversions'])){
            $generated_conversions = $custom_properties['generated_conversions'];
            $generated_conversions[$conversion_name] = true;
            $custom_properties['generated_conversions'] = $generated_conversions;
        }
        else{
            $custom_properties['generated_conversions'][$conversion_name] = true;
        }
        $media->custom_properties = $custom_properties;
        $media->save();
    }
}
