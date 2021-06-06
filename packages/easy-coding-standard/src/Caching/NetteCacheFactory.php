<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Caching;

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
use Symplify\SmartFileSystem\SmartFileSystem;

final class NetteCacheFactory
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var SmartFileSystem
     */
    private $smartFileSystem;

    public function __construct(ParameterProvider $parameterProvider, SmartFileSystem $smartFileSystem)
    {
        $this->parameterProvider = $parameterProvider;
        $this->smartFileSystem = $smartFileSystem;
    }

    public function create(): Cache
    {
        $cacheDirectory = $this->parameterProvider->provideStringParameter(Option::CACHE_DIRECTORY);

        // ensure cache directory exists
        if (! $this->smartFileSystem->exists($cacheDirectory)) {
            $this->smartFileSystem->mkdir($cacheDirectory);
        }

        // journal is needed for tags support
        $jsonFileJournal = new JsonFileJournal($cacheDirectory . '/journal.json');
        $fileStorage = new FileStorage($cacheDirectory, $jsonFileJournal);

        // namespace is unique per project
        $namespace = md5(getcwd());
        return new Cache($fileStorage, $namespace);
    }
}
