<?php

declare(strict_types=1);

namespace Romanpravda\Scormpackager;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Romanpravda\Scormpackager\Exceptions\DestinationNotSetException;
use Romanpravda\Scormpackager\Exceptions\IdentifierNotSetException;
use Romanpravda\Scormpackager\Exceptions\SourceNotSetException;
use Romanpravda\Scormpackager\Exceptions\TitleNotSetException;
use Romanpravda\Scormpackager\Exceptions\VersionIsNotSupportedException;
use Romanpravda\Scormpackager\Exceptions\VersionNotSetException;
use Romanpravda\Scormpackager\Helpers\ScormVersions;
use Romanpravda\Scormpackager\Helpers\XMLFromArrayCreator;
use Romanpravda\Scormpackager\Schemas\AbstractScormSchema;
use Romanpravda\Scormpackager\Schemas\Scorm12Schema;
use Romanpravda\Scormpackager\Schemas\Scorm2004Schema;
use Throwable;

class Packager
{
    /**
     * Version of SCORM package
     *
     * @var string
     */
    private $version;

    /**
     * Organization name for SCORM package
     *
     * @var string
     */
    private $organization;

    /**
     * Course title for SCORM package
     *
     * @var string
     */
    private $title;

    /**
     * Course identifier for SCORM package
     *
     * @var string
     */
    private $identifier;

    /**
     * Passing score for course in SCORM package
     *
     * @var int
     */
    private $masteryScore;

    /**
     * Starting page for SCORM package
     *
     * @var string
     */
    private $startingPage;

    /**
     * Source directory for SCORM package
     *
     * @var string
     */
    private $source;

    /**
     * Destination directory for SCORM package
     *
     * @var string
     */
    private $destination;

    /**
     * Packager constructor.
     *
     * @param array $config
     *
     * @throws Throwable
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * Applying config
     *
     * @param array $config
     *
     * @throws Throwable
     */
    private function setConfig(array $config)
    {
        throw_if(!isset($config['title']), TitleNotSetException::class);
        throw_if(!isset($config['identifier']), IdentifierNotSetException::class);
        throw_if(!isset($config['version']), VersionNotSetException::class);
        throw_if(!isset($config['source']), SourceNotSetException::class);
        throw_if(!isset($config['destination']), DestinationNotSetException::class);

        $this->setTitle($config['title']);
        $this->setIdentifier($config['identifier']);
        $this->setVersion(ScormVersions::normalizeScormVersion($config['version']));
        $this->setSource($config['source']);
        $this->setDestination($config['destination']);
        $this->setMasteryScore($config['masteryScore'] ?? 80);
        $this->setStartingPage($config['startingPage'] ?? 'index.html');
        $this->setOrganization($config['organization'] ?? '');
    }

    /**
     * Set course title for SCORM package
     *
     * @param mixed $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Get course title for SCORM package
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set course identifier for SCORM package
     *
     * @param mixed $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Get course identifier for SCORM package
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set version of SCORM package
     *
     * @param mixed $version
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    /**
     * Get version of SCORM package
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set source directory for SCORM package
     *
     * @param mixed $source
     */
    public function setSource(string $source)
    {
        $this->source = $source;
    }

    /**
     * Get destination directory for SCORM package
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set destination directory for SCORM package
     *
     * @param mixed $destination
     */
    public function setDestination(string $destination)
    {
        $this->destination = $destination;
    }

    /**
     * Get destination directory for SCORM package
     *
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Set passing score for course in SCORM package
     *
     * @param mixed $masteryScore
     */
    public function setMasteryScore(int $masteryScore)
    {
        $this->masteryScore = $masteryScore;
    }

    /**
     * Get passing score for course in SCORM package
     *
     * @return int
     */
    public function getMasteryScore(): int
    {
        return $this->masteryScore;
    }

    /**
     * Set starting page for SCORM package
     *
     * @param mixed $startingPage
     */
    public function setStartingPage(string $startingPage)
    {
        $this->startingPage = $startingPage;
    }

    /**
     * Get starting page for SCORM package
     *
     * @return string
     */
    public function getStartingPage(): string
    {
        return $this->startingPage;
    }

    /**
     * Set organization name for SCORM package
     *
     * @param mixed $organization
     */
    public function setOrganization(string $organization)
    {
        $this->organization = $organization;
    }

    /**
     * Get organization name for SCORM package
     *
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * Build SCORM package
     *
     * @throws Throwable
     */
    public function buildPackage()
    {
        $this->createDestinationDirectory();
        $this->copyFilesForPackage();
        $this->createManifestFile();
        $this->copyDefinitionFiles();
    }

    /**
     * Create directory for package files
     */
    private function createDestinationDirectory()
    {
        mkdir($this->getDestination());
    }

    /**
     * Copy files for package into destination directory
     */
    private function copyFilesForPackage()
    {
        $filesForPackage = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->getSource(), RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        copy_files($filesForPackage, $this->getSource(), $this->getDestination());
    }

    /**
     * Copy SCORM manifest definition files into destination directory
     */
    private function copyDefinitionFiles()
    {
        $pathToDefinitionFiles = realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."dist".DIRECTORY_SEPARATOR."definitionFiles".DIRECTORY_SEPARATOR.$this->getVersion());
        $definitionFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathToDefinitionFiles, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        copy_files($definitionFiles, $pathToDefinitionFiles, $this->getDestination().DIRECTORY_SEPARATOR."definitionFiles");
    }

    /**
     * Create SCORM manifest file in destination directory
     *
     * @throws VersionIsNotSupportedException
     *
     * @throws Throwable
     */
    private function createManifestFile()
    {
        $schema = $this->getScormManifestSchema();

        $xml = XMLFromArrayCreator::createManifestXMLFromSchema($schema);

        $doc = dom_import_simplexml($xml)->ownerDocument;
        $doc->encoding = 'UTF-8';

        $xml->asXML($this->getDestination().DIRECTORY_SEPARATOR."imsmanifest.xml");
    }

    /**
     * Returns SCORM manifest's schema
     *
     * @return array
     *
     * @throws VersionIsNotSupportedException
     */
    private function getScormManifestSchema(): array
    {
        /** @var AbstractScormSchema $scormSchemaClass */
        switch ($this->getVersion()) {
            case ScormVersions::SCORM__1_2__VERSION:
                $scormVersionForSchema = '1.2';
                $scormSchemaClass = Scorm12Schema::class;
                break;
            case ScormVersions::SCORM__2004_3__VERSION:
                $scormVersionForSchema = '2004 3rd Edition';
                $scormSchemaClass = Scorm2004Schema::class;
                break;
            case ScormVersions::SCORM__2004_4__VERSION:
                $scormVersionForSchema = '2004 4th Edition';
                $scormSchemaClass = Scorm2004Schema::class;
                break;
            default:
                throw new VersionIsNotSupportedException();
        }

        return $scormSchemaClass::getSchema(
            $this->getTitle(),
            $this->getIdentifier(),
            $this->getOrganization(),
            $scormVersionForSchema,
            $this->getMasteryScore(),
            $this->getStartingPage(),
            $this->getDestination()
        );
    }
}