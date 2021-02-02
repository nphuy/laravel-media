<?php
namespace HNP\LaravelMedia;
use Illuminate\Database\Eloquent\Model;

interface Media{
    public function model();
    public function destroyConversionFolder(): void;
    public function getConversionPath(): string;
    public function getPath(): string;
    public function getFullUrl(string $conversion_name = null): string;
}