<?php
namespace Core\Router\Infrastructure\Persistence\Libraries;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Table;
use Google\Cloud\BigQuery\Dataset;

class BigQueryLibrary
{
    protected BigQueryClient $bigQuery;
    private Table $table;
    private string $tableId;
    private string $projectId;
    private Dataset $dataset;
    private int $pageSize;

    public function __construct($tableId, $datasetId = null, $pageSize = 10, $projectId = null)
    {

        if (!isset($projectId)) {
            $projectId = env('DB_BQ_PROJECT_ID');
        }

        $this->bigQuery = new BigQueryClient([
            'projectId' => $projectId,
            'keyFilePath' => storage_path(env('DB_BQ_KEY_FILE')),
        ]);

        if (!isset($datasetId)) {
            $datasetId = env('DB_BQ_DATASET');
        }

        $this->projectId = $projectId;
        $this->tableId = $tableId;
        $this->dataset = $this->bigQuery->dataset($datasetId);
        $this->table = $this->dataset->table($tableId);
        $this->pageSize = $pageSize;
    }

    public function setTable($table): void
    {
        $this->tableId = $table;
        $this->table = $this->dataset->table($this->tableId);
    }

    public function insert($data): array
    {
        $insertedRows = $this->table->insertRow($data);
        return [
            "data" => $data,
            "success" => $insertedRows->isSuccessful(),
        ];
    }

    public function setPerPage(int $perPage) {
        $this->pageSize = $perPage;
    }

    public function select(string $sql, int|null $page = 1)
    {
        try {
            if ($page !== null){
                $offset = ($page - 1) * $this->pageSize;
                $sql .= " LIMIT {$this->pageSize} OFFSET {$offset}";
            }

            $queryJobConfig = $this->bigQuery->query($sql);
            $queryResults = $this->bigQuery->runQuery($queryJobConfig);

            $dataArray = [];
            foreach ($queryResults->rows() as $row) {
                $dataArray[] = $row;
            }

            return $dataArray;
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function getPagination(string $sql) {
        $queryJobConfig = $this->bigQuery->query($sql);
        foreach ($this->bigQuery->runQuery($queryJobConfig)->rows() as $row) {
            return [
                'total' => $row['total'],
                'per_page' => $this->pageSize,
                'last_page' => (int) ceil($row['total'] / $this->pageSize)
            ];
        }
    }

    public function selectAll(string $sql) {
        $queryJobConfig = $this->bigQuery->query($sql);
        $queryResults = $this->bigQuery->runQuery($queryJobConfig);
        $dataArray = [];
            foreach ($queryResults->rows() as $row) {
                $dataArray[] = $row['service_id'];
            }

            return $dataArray;
    }
}
