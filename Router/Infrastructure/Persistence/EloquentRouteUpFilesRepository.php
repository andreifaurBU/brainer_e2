<?php

namespace Core\Router\Infrastructure\Persistence;

use Core\Router\Domain\Repositories\RouteUpFileRepository;
use Core\Router\Infrastructure\Persistence\Model\RouteUpFile;


class EloquentRouteUpFilesRepository implements RouteUpFileRepository
{
    public function saveRouteUpFile($file) {
        $file_name = $file->getClientOriginalName();
        $file_type = $file->getClientMimeType();
        $file_size = $file->getSize();
        $fileContents = file_get_contents($file);
        $newFile = new RouteUpFile();
        $newFile->file_name = $file_name;
        $newFile->file_type = $file_type;
        $newFile->file_size = $file_size;
        $newFile->file_content = $fileContents;
        if ($newFile->save()) return $newFile;
        else return null;
    }

    public function getRouteUpFile($file_id)
    {
        return RouteUpFile::where('id', $file_id)->first();
    }
}