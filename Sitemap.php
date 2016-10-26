<?php
namespace artemPerepechai\sitemap;

use XMLWriter;

/**
 * A class for generating Sitemaps (http://www.sitemaps.org/)
 *
 * @author origin Alexander Makarov <sam@rmcreative.ru>
 * @author vendor Artem Perepechai aperepechai@gmail.com
 */
class Sitemap
{
    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

    /**
     * @var integer Maximum allowed number of URLs in a single file.
     */
    private $maxUrls = 50000;

    /**
     * @var integer number of URLs added
     */
    private $urlsCount = 0;

    /**
     * @var string path to the file to be written
     */
    private $filePath;

    /**
     * @var integer number of files written
     */
    private $fileCount = 0;

    /**
     * @var array path of files written
     */
    private $writtenFilePaths = array();

    /**
     * @var integer number of URLs to be kept in memory before writing it to file
     */
    private $bufferSize = 1000;

    /**
     * @var bool if XML should be indented
     */
    private $useIndent = true;

    /**
     * @var array valid values for frequency parameter
     */
    private $validFrequencies = array(
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER
    );


    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @var string
     */
    private $alternateLanguage = array();

    /**
     * @var string item's priority (0.0-1.0). Default null is equal to 0.5
     */
    private $priority;

    /**
     * @var float change frequency. Use one of self:: constants here
     */
    private $frequency;

    /**
     * @var integer last modification timestamp
     */
    private $lastModified;

    /**
     * @var string location item URL
     */
    private $location;


    /**
     * @param string $filePath path of the file to write to
     * @throws \InvalidArgumentException
     */
    public function __construct($filePath)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }

        $this->filePath = $filePath;
    }

    /**
     * Get array of generated files
     * @return array
     */
    public function getWrittenFilePath()
    {
        return $this->writtenFilePaths;
    }
    
    /**
     * Creates new file
     */
    private function createNewFile()
    {
        $this->fileCount++;
        $filePath = $this->getCurrentFilePath();
        $this->writtenFilePaths[] = $filePath;
        @unlink($filePath);

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent($this->useIndent);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
    }

    /**
     * Writes closing tags to current file
     */
    private function finishFile()
    {
        if ($this->writer !== null) {
            $this->writer->endElement();
            $this->writer->endDocument();
            $this->flush();
        }
    }

    /**
     * Finishes writing
     */
    public function write()
    {
        $this->finishFile();
    }

    /**
     * Flushes buffer into file
     */
    private function flush()
    {
        file_put_contents($this->getCurrentFilePath(), $this->writer->flush(true), FILE_APPEND);
    }

    /**
     * @param $lang
     * @param $hreflang
     * @return $this
     */
    public function setAlternateLanguage($lang, $hreflang)
    {
        $this->alternateLanguage[$lang] = $hreflang;
        return $this;
    }

    /**
     * @param $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        if (!is_numeric($priority) || $priority < 0 || $priority > 1) {
            throw new \InvalidArgumentException(
                "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: {$priority}."
            );
        }
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param $frequency
     * @return $this
     */
    public function setFrequency($frequency)
    {
        if (!in_array($frequency, $this->validFrequencies, true)) {
            throw new \InvalidArgumentException(
                'Please specify valid changeFrequency. Valid values are: '
                . implode(', ', $this->validFrequencies)
                . "You have specified: {$frequency}."
            );
        }
        $this->frequency = $frequency;
        return $this;
    }

    /**
     * @param $lastModified
     * @return $this
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
        return $this;
    }

    /**
     * @param $location
     * @return $this
     */
    public function setLocation($location)
    {
        if (false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }
        $this->location = $location;
        return $this;
    }

    private function unsetLocation()
    {
        $this->location = null;
    }

    private function unSetAlternateLanguage()
    {
        $this->alternateLanguage = array();
    }

    private function unSetPriority()
    {
        $this->priority = null;
    }

    private function unSetFrequency()
    {
        $this->frequency = null;
    }

    private function unSetLastModifiedElement()
    {
        $this->lastModified = null;
    }

    private function addAlternateLanguageElement()
    {
        $this->buildAlternateLanguageElements();
        $this->unSetAlternateLanguage();

    }

    private function addPriorityElement()
    {
        $this->writer->writeElement('priority', number_format($this->priority, 1, '.', ','));
        $this->unSetPriority();
    }

    private function addLocationElement()
    {
        $this->writer->writeElement('loc', $this->location);
        $this->unsetLocation();
    }

    private function addFrequencyElement()
    {
        $this->writer->writeElement('changefreq', $this->frequency);
        $this->unSetFrequency();
    }

    private function addLastModifiedElement()
    {
        $this->writer->writeElement('lastmod', date('c', $this->lastModified));
        $this->unSetLastModifiedElement();
    }

    /**
     * @return bool Check necessity to add Alternate Language Element
     */
    private function needAlternateLanguageElement()
    {
        return !empty($this->alternateLanguage);
    }

    private function buildAlternateLanguageElements()
    {
        foreach ($this->alternateLanguage as $alternateLanguage => $alternateLanguageHref) {
            $this->writer->startElement('xhtml:link');
            $this->writer->startAttribute('rel');
            $this->writer->text('alternate');
            $this->writer->endAttribute();

            $this->writer->startAttribute('hreflang');
            $this->writer->text($alternateLanguage);
            $this->writer->endAttribute();

            $this->writer->startAttribute('href');
            $this->writer->text($alternateLanguageHref);
            $this->writer->endAttribute();
            $this->writer->endElement();
        }
    }

    /**
     * Adds a new item to sitemap
     *
     * @throws \InvalidArgumentException
     */
    public function addItem()
    {
        if ($this->urlsCount === 0) {
            $this->createNewFile();
        } elseif ($this->urlsCount % $this->maxUrls === 0) {
            $this->finishFile();
            $this->createNewFile();
        }

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }
        $this->writer->startElement('url');

        if($this->location) {
            $this->addLocationElement();
        }

        if ($this->lastModified) {
            $this->addLastModifiedElement();
        }

        if ($this->frequency) {
            $this->addFrequencyElement();
        }

        if($this->priority) {
            $this->addPriorityElement();
        }

        if($this->needAlternateLanguageElement()) {
            $this->addAlternateLanguageElement();
        }

        $this->writer->endElement();

        $this->urlsCount++;
    }

    /**
     * @return string path of currently opened file
     */
    private function getCurrentFilePath()
    {
        if ($this->fileCount < 2) {
            return $this->filePath;
        }

        $parts = pathinfo($this->filePath);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '_' . $this->fileCount . '.' . $parts['extension'];
    }

    /**
     * Returns an array of URLs written
     *
     * @param string $baseUrl base URL of all the sitemaps written
     * @return array URLs of sitemaps written
     */
    public function getSitemapUrls($baseUrl)
    {
        $urls = array();
        foreach ($this->writtenFilePaths as $file) {
            $urls[] = $baseUrl . pathinfo($file, PATHINFO_BASENAME);
        }
        return $urls;
    }

    /**
     * Sets maximum number of URLs to write in a single file.
     * Default is 50000.
     * @param integer $number
     */
    public function setMaxUrls($number)
    {
        $this->maxUrls = (int)$number;
    }

    /**
     * Sets number of URLs to be kept in memory before writing it to file.
     * Default is 1000.
     *
     * @param integer $number
     */
    public function setBufferSize($number)
    {
        $this->bufferSize = (int)$number;
    }


    /**
     * Sets if XML should be indented.
     * Default is true.
     *
     * @param bool $value
     */
    public function setUseIndent($value)
    {
        $this->useIndent = (bool)$value;
    }
}
