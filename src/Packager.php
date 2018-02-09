<?php
declare(strict_types=1);

namespace Plehanov\RPM;

use DirectoryIterator;
use PharData;

class Packager
{
    /** @var Spec */
    private $spec;
    /** @var array */
    private $mountPoints = [];
    /** @var string */
    private $outputPath;
    /** @var string */
    private $buildPath;

    public function __construct()
    {
        $this->buildPath = $_SERVER['HOME'];
    }

    public function getSpec(): Spec
    {
        return $this->spec;
    }

    public function setSpec(Spec $spec): self
    {
        $this->spec = $spec;

        return $this;
    }

    public function getBuildPath(): string
    {
        return $this->buildPath;
    }

    public function setOutputPath(string $path): self
    {
        $this->outputPath = $path;

        return $this;
    }

    public function addMount(string $sourcePath, string $destinationPath): self
    {
        $this->mountPoints[rtrim($sourcePath, DIRECTORY_SEPARATOR)] = DIRECTORY_SEPARATOR . trim($destinationPath, DIRECTORY_SEPARATOR);

        return $this;
    }

    public function movePackage(string $destinationPath): bool
    {
        return rename(
            "{$this->buildPath}/rpmbuild/RPMS/{$this->spec->BuildArch}/{$this->spec->Name}-{$this->spec->Version}-{$this->spec->Release}.{$this->spec->BuildArch}.rpm",
            $destinationPath
        );
    }

    /**
     * @return Packager
     * @throws \RuntimeException
     */
    public function run(): self
    {
        if (file_exists($this->outputPath)) {
            exec(sprintf(PHP_OS === 'Windows' ? 'rd /s /q %s' : 'rm -rf %s', escapeshellarg($this->outputPath)));
        }
        self::createDirectory($this->outputPath);
        self::createDirectory("{$this->buildPath}/rpmbuild/SOURCES");
        self::createDirectory("{$this->buildPath}/rpmbuild/SPECS");

        foreach ($this->mountPoints as $sourcePath => $destPath) {
            $this->pathToPath($sourcePath, $this->outputPath . $destPath);
            $this->spec->addPerm($destPath);
        }

        if (file_exists("{$this->buildPath}/rpmbuild/SOURCES/{$this->spec->Name}.tar")) {
            unlink("{$this->buildPath}/rpmbuild/SOURCES/{$this->spec->Name}.tar");
        }
        $tar = new PharData("{$this->buildPath}/rpmbuild/SOURCES/{$this->spec->Name}.tar");
        $tar->buildFromDirectory($this->outputPath);
        $this->spec->setProp('Source0', "{$this->spec->Name}.tar");

        file_put_contents("{$this->buildPath}/rpmbuild/SPECS/{$this->spec->Name}.spec", (string)$this->spec);

        return $this;
    }

    public function build(): string
    {
        return "rpmbuild -bb {$this->buildPath}/rpmbuild/SPECS/{$this->spec->Name}.spec";
    }

    /**
     * @param string $path
     * @param string $destPath
     * @throws \RuntimeException
     */
    protected function pathToPath(string $path, string $destPath): void
    {
        if (is_dir($path)) {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $element) {
                if (!\in_array((string)$element, ['.', '..'], true)) {
                    $sourcePath = $path . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($sourcePath)) {
                        $this->pathToPath($sourcePath, $destPath . DIRECTORY_SEPARATOR . $element);
                    } else {
                        $this->copy($sourcePath, $destPath . DIRECTORY_SEPARATOR . $element);
                    }
                }
            }
        } else {
            $this->copy($path, $destPath);
        }
    }

    /**
     * @param string $sourcePath
     * @param string $destPath
     * @throws \RuntimeException
     */
    protected function copy(string $sourcePath, string $destPath): void
    {
        $destinationFolder = \dirname($destPath);
        self::createDirectory($destinationFolder);

        copy($sourcePath, $destPath);
        if (fileperms($sourcePath) !== fileperms($destPath)) {
            chmod($destPath, fileperms($sourcePath));
        }
    }

    /**
     * @param string $destFolder
     * @throws \RuntimeException
     */
    protected static function createDirectory(string $destFolder): void
    {
        if (!file_exists($destFolder) && !mkdir($destFolder, 0755, true) && !is_dir($destFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $destFolder));
        }
    }
}