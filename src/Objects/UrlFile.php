<?php
namespace HNP\LaravelMedia\Objects;
use Illuminate\Http\File;

class UrlFile{
    private $file;
    private $name;
    private $extension;

    public function create($file, $name, $extension): UrlFile{
        $this->file = $file;
        $this->name = $name;
        $this->extension = $extension;
        return $this;
    }
    public function getFile(){
        return new File($this->file);
    }
    public function getClientOriginalName(){
        return $this->name;
    }
    public function getExtension(){
        return $this->extension;
    }
    public function getFileName(){
        $name = $this->getClientOriginalName();
        $extension = $this->getExtension();
        return str_replace(".{$extension}", "", $name);
    }
    public function getSize(){
        return filesize($this->file);
    }
    public function getMimeType(){
        return mime_content_type($this->file);
    }
}