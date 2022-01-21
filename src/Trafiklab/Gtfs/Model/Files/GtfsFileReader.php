<?php

namespace Trafiklab\Gtfs\Model\Files;


use Trafiklab\Gtfs\Model\Entities\Trip;
use Trafiklab\Gtfs\Model\GtfsArchive;
use Trafiklab\Gtfs\Util\Internal\GtfsParserUtil;

class GtfsFileReader
{
    private $fileDataHandle;
    private $fields;

    public function __construct(private GtfsArchive $gtfsArchive, private string $filePath, private string $dataModelClass)
    {
    }

    public function __destruct()
    {
        $this->closeHandle();
    }

    protected function initHandle()
    {
        if ($this->fileDataHandle === null) {
            $fields = [];
            $this->fileDataHandle = GtfsParserUtil::initCSVHandle($this->filePath, $fields);
            $this->fields = $fields;
        }
    }

    protected function closeHandle()
    {
        if ($this->fileDataHandle != null) {
            fclose($this->fileDataHandle);
            $this->fileDataHandle = null;
        }
    }

    /**
     * Allows an easy and efficient access to read the file row per row 
     *
     * @return mixed
     */
    public function next(): mixed
    {
        $this->initHandle();
        $row = fgetcsv($this->fileDataHandle);
        if ($row === false) {
            $this->closeHandle();
            return false;
        }

        // Read a data row
        $rowData = [];
        foreach ($row as $k => $value) {
            $rowData[$this->fields[$k]] = $value;
        }
        return new $this->dataModelClass($this->gtfsArchive, $rowData);
    }

    /**
     * Returns all the rows
     *
     * @return array
     */
    public function getAllDataRows(): array
    {
        return GtfsParserUtil::deserializeCSV(
            $this->gtfsArchive,
            $this->filePath,
            $this->dataModelClass
        );
    }
}
