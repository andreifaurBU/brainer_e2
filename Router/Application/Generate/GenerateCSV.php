<?php

namespace Core\Router\Application\Generate;

class GenerateCSV
{

    private static string $storagePATH = "storage/";

    public function __construct(){

    }

    public function __invoke(array $headers, array $rows, string $directory, string $delimiter = ";") {
        try {
            if (!file_exists(storage_path($directory))) {
                mkdir(storage_path($directory), 0777, true);
            }

            $filename = now()->format('Y-m-d_H:i:s') . '.csv';
            $filePath = $directory . '/'. $filename;
            $filePathDir = storage_path($filePath);
            $url = env('APP_URL') . $filePath;

            $file = fopen($filePathDir, 'a+');
            fputcsv($file, $headers, $delimiter);
            foreach ($rows as $row){
                fputcsv($file, $row, $delimiter);
            }
            fclose($file);

            return [
                'download_url' => str_replace('app/public/', self::$storagePATH, $url),
                'file_path' => $filePath,
                'success' => true,
            ];
        }catch (\Exception $exception){
            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }

    }

}
