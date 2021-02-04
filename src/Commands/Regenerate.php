<?php
namespace HNP\LaravelMedia\Commands;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;
use HNP\LaravelMedia\Models\Media;
use HNP\LaravelMedia\Models\MongoMedia;

class Regenerate extends Command
{
    use ConfirmableTrait;
    protected $signature = 'laravel-media:regenerate {modelType?} {--ids=*}';
    protected $description = 'Regenerate the derived images of media';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }
        $mediaFiles = $this->getMediaToBeRegenerated();
        $progressBar = $this->output->createProgressBar($mediaFiles->count());
        $mediaFiles->each(function ($media) use ($progressBar) {
            try{
                $media->regenerate();
            }catch(Exception $e){}
            $progressBar->advance();
           
        });
        $progressBar->finish();
        $this->info('All done!');
        // dd($this->getMediaToBeRegenerated());
        // return 0;
    }
    protected function getMediaToBeRegenerated(){
        $modelType = $this->argument('modelType') ?? '';
        $mediaIds = $this->getMediaIds();
        $mediaClass = config("hnp-media.media_model");
        
        if ($modelType === '' && count($mediaIds) === 0) {
            return $mediaClass::all();
        }

        if (! count($mediaIds)) {
            return $mediaClass::where('model_type', $modelType)->get();
        }
        // dd($modelType, $mediaIds);
        $id_field = app($mediaClass) instanceof MongoMedia ? "_id" : "id";
        // dd(app($mediaClass),$id_field);
        return $mediaClass::whereIn($id_field, $mediaIds)->get();
    }
    protected function getMediaIds(): array
    {
        $mediaIds = $this->option('ids');

        if (! is_array($mediaIds)) {
            $mediaIds = explode(',', $mediaIds);
        }

        if (count($mediaIds) === 1 && Str::contains($mediaIds[0], ',')) {
            $mediaIds = explode(',', $mediaIds[0]);
        }

        return $mediaIds;
    }
}