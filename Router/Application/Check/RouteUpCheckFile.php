<?php

namespace Core\Router\Application\Check;


use Core\Router\Domain\Repositories\CSVReaderRepository;

class RouteUpCheckFile
{

    private static array $expectedRequiredHeaders = ['Stop id', 'Stop type', 'Stop latitude', 'Stop longitude', 'Passenger list', 'Number of passengers'];
    private static array $expectedOptionalHeaders = [];
    private static int $totalColumns = 6;

    public function __construct(
        private CSVReaderRepository $CSVReader
    ){}

    public function __invoke($file) {
        $this->CSVReader->load($file);
        $validate_headers = $this->validateHeaders($this->CSVReader->getHeaders());
        if (!$validate_headers['is_valid']){
            return [
                'is_valid' => false,
                'message' => $validate_headers['messsage'] ?? ''
            ];
        }

        return [
            'is_valid' => true,
        ];
    }

    public function validateHeaders($headers): array{
        if (count($headers) !== self::$totalColumns){
            return [
                'is_valid' => false,
                'message' => "The total columns must be "
                    . self::$totalColumns . " but the file has " . count($headers)
            ];
        }

        // Validate required headers
        foreach (self::$expectedRequiredHeaders as $header) {
            if (!in_array($header, $headers)) {
                return [
                    'is_valid' => false,
                    'message' => "Missing column '$header'"
                ];
            }
        }

        // Validate optional headers
        $optionalFound = false;
        if (empty(self::$expectedOptionalHeaders)) $optionalFound = true;
        foreach (self::$expectedOptionalHeaders as $header) {
            if (in_array($header, $headers)) {
                $optionalFound = true;
                break;
            }
        }

        if (!$optionalFound) {
            return [
                'is_valid' => false,
                'message' => "The columns " . implode(", ", self::$expectedOptionalHeaders) . " are required"
            ];
        }

        return [
            'is_valid' => true,
        ];
    }

}
