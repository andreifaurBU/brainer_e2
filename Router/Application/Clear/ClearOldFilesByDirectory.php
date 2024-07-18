<?php

namespace Core\Router\Application\Clear;

class ClearOldFilesByDirectory
{

    public function __construct(){}

    public function __invoke(string $directory, int $days_ago = 7) {
        try {

            $num_files_deleted = 0;
            $limit_date = strtotime("-$days_ago days");
            $files = scandir(storage_path($directory));

            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $file_date = filemtime(storage_path($directory . '/' . $file));
                if ($file_date < $limit_date) {
                    unlink(storage_path($directory . '/' . $file));
                    $num_files_deleted++;
                }
            }

            return [
                'num_files' => $num_files_deleted,
                'success' => true,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }
    }

}
