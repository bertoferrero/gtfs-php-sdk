<?php


namespace Trafiklab\Gtfs\Model;


use Exception;
use ZipArchive;
use Trafiklab\Gtfs\Model\Entities\Stop;
use Trafiklab\Gtfs\Model\Entities\Trip;
use Trafiklab\Gtfs\Model\Entities\Route;
use Trafiklab\Gtfs\Model\Entities\Agency;
use Trafiklab\Gtfs\Model\Entities\FeedInfo;
use Trafiklab\Gtfs\Model\Entities\StopTime;
use Trafiklab\Gtfs\Model\Entities\Transfer;
use Trafiklab\Gtfs\Model\Entities\Frequency;
use Trafiklab\Gtfs\Util\Internal\ArrayCache;
use Trafiklab\Gtfs\Model\Entities\ShapePoint;
use Trafiklab\Gtfs\Model\Files\GtfsStopsFile;
use Trafiklab\Gtfs\Model\Files\GtfsTripsFile;
use Trafiklab\Gtfs\Model\Files\GtfsAgencyFile;
use Trafiklab\Gtfs\Model\Files\GtfsFileReader;
use Trafiklab\Gtfs\Model\Files\GtfsRoutesFile;
use Trafiklab\Gtfs\Model\Files\GtfsShapesFile;
use Trafiklab\Gtfs\Model\Entities\CalendarDate;
use Trafiklab\Gtfs\Model\Entities\CalendarEntry;
use Trafiklab\Gtfs\Model\Files\GtfsCalendarFile;
use Trafiklab\Gtfs\Model\Files\GtfsFeedInfoFile;
use Trafiklab\Gtfs\Model\Files\GtfsStopTimesFile;
use Trafiklab\Gtfs\Model\Files\GtfsTransfersFile;
use Trafiklab\Gtfs\Model\Files\GtfsFrequenciesFile;
use Trafiklab\Gtfs\Model\Files\GtfsCalendarDatesFile;

class GtfsArchive
{
    use ArrayCache;

    private const AGENCY_TXT = "agency.txt";
    private const STOPS_TXT = "stops.txt";
    private const ROUTES_TXT = "routes.txt";
    private const TRIPS_TXT = "trips.txt";
    private const STOP_TIMES_TXT = "stop_times.txt";
    private const CALENDAR_TXT = "calendar.txt";
    private const CALENDAR_DATES_TXT = "calendar_dates.txt";
    private const FARE_ATTRIBUTES_TXT = "fare_attributes.txt"; // Unsupported at this moment
    private const FARE_RULES_TXT = "fare_rules.txt"; // Unsupported at this moment
    private const SHAPES_TXT = "shapes.txt";
    private const FREQUENCIES_TXT = "frequencies.txt"; // Unsupported at this moment
    private const TRANSFERS_TXT = "transfers.txt";
    private const PATHWAYS_TXT = "pathways.txt"; // Unsupported at this moment
    private const LEVELS_TXT = "levels.txt"; // Unsupported at this moment
    private const FEED_INFO_TXT = "feed_info.txt";

    private const TEMP_ROOT = "/tmp/gtfs/";

    private $fileRoot;

    private function __construct(string $fileRoot)
    {
        $this->fileRoot = $fileRoot;
    }

    /**
     * Download a GTFS zipfile.
     *
     * @param string $url The URL that points to the archive.
     *
     * @return GtfsArchive The downloaded archive.
     * @throws Exception
     */
    public static function createFromUrl(string $url): GtfsArchive
    {
        $downloadedArchive = self::downloadFile($url);
        $fileRoot = self::extractFiles($downloadedArchive, true);
        return new GtfsArchive($fileRoot);
    }

    /**
     * Open a local GTFS zipfile.
     *
     * @param string $path The path that points to the archive.
     *
     * @return GtfsArchive The downloaded archive.
     * @throws Exception
     */
    public static function createFromPath(string $path): GtfsArchive
    {
        $fileRoot = self::extractFiles($path);
        return new GtfsArchive($fileRoot);
    }

    /**
     * Download and extract the latest GTFS data set
     *
     * @param string $url
     *
     * @return string
     */
    private static function downloadFile(string $url): string
    {
        $temp_file = self::TEMP_ROOT . md5($url) . ".zip";

        if (!file_exists($temp_file)) {
            // Download zip file with GTFS data.
            file_put_contents($temp_file, file_get_contents($url));
        }

        return $temp_file;
    }

    private static function extractFiles(string $archiveFilePath, bool $deleteArchive = false)
    {
        // Load the zip file.
        $zip = new ZipArchive();
        if ($zip->open($archiveFilePath) != 'true') {
            throw new Exception('Could not open the GTFS archive');
        }
        // Extract the zip file and remove it.
        $extractionPath = substr($archiveFilePath, 0, strlen($archiveFilePath) - 4) . '/';
        $zip->extractTo($extractionPath);
        $zip->close();

        if ($deleteArchive) {
            unlink($archiveFilePath);
        }

        return $extractionPath;
    }

    /**
     * @return GtfsFileReader
     */
    public function getAgencyFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::AGENCY_TXT, Agency::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getCalendarDatesFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::CALENDAR_DATES_TXT, CalendarDate::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getCalendarFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::CALENDAR_TXT, CalendarEntry::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getFeedInfoFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::FEED_INFO_TXT, FeedInfo::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getRoutesFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::ROUTES_TXT, Route::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getShapesFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::SHAPES_TXT, ShapePoint::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getStopsFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::STOPS_TXT, Stop::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getStopTimesFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::STOP_TIMES_TXT, StopTime::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getTransfersFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::TRANSFERS_TXT, Transfer::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getTripsFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::TRIPS_TXT, Trip::class);
    }

    /**
     * @return GtfsFileReader
     */
    public function getFrequenciesFile(): GtfsFileReader
    {
        return $this->loadGtfsFileThroughReader(self::FREQUENCIES_TXT, Frequency::class);
    }

    /**
     * Delete the uncompressed files. This should be done as a cleanup when you're ready.
     */
    public function deleteUncompressedFiles()
    {
        // Remove temporary data.
        if (!file_exists($this->fileRoot)) {
            return;
        }
        $files = scandir($this->fileRoot);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                // Remove all extracted files from the zip file.
                unlink($this->fileRoot . '/' . $file);
            }
        }
        reset($files);
        // Remove the empty folder.
        rmdir($this->fileRoot);
    }

    private function loadGtfsFileThroughReader(string $file, string $class)
    {
        return new GtfsFileReader($this, $this->fileRoot . $file, $class);
    }
}
