<?php
declare(strict_types=1);

namespace plehanov\rpm;

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

    public function setSpec(Spec $spec)
    {
        $this->spec = $spec;
        return $this;
    }

    public function getBuildPath(): string
    {
        return $this->buildPath;
    }

    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    public function setOutputPath($path)
    {
        $this->outputPath = $path;
        return $this;
    }

    public function addMount($sourcePath, $destinationPath)
    {
        $this->mountPoints[$sourcePath] = $destinationPath;
        return $this;
    }

    public function movePackage(string $destinationPath)
    {
        return rename("{$this->buildPath}/rpmbuild/RPMS/{$this->spec->BuildArch}/{$this->spec->Name}-{$this->spec->Version}-{$this->spec->Release}.{$this->spec->BuildArch}.rpm",
            $destinationPath);
    }

    public function run()
    {
        if (!is_dir("{$this->buildPath}/rpmbuild/SOURCES")) {
            mkdir("{$this->buildPath}/rpmbuild/SOURCES", 0777, true);
        }
        if (!is_dir("{$this->buildPath}/rpmbuild/SPECS")) {
            mkdir("{$this->buildPath}/rpmbuild/SPECS", 0777, true);
        }

        if (file_exists($this->getOutputPath())) {
            exec(sprintf(PHP_OS === 'Windows' ? 'rd /s /q %s' : 'rm -rf %s', escapeshellarg($this->getOutputPath())));
        }

        if (!is_dir($this->getOutputPath())) {
            mkdir($this->getOutputPath(), 0777, true);
        }

        foreach ($this->mountPoints as $path => $dest) {
            $this->pathToPath($path, $this->getOutputPath() . DIRECTORY_SEPARATOR . $dest);
        }

        $files_section = null;
        foreach ($this->mountPoints as $sourcePath => $destinationPath) {
            $len = \strlen((string)$files_section);
            if (is_dir($sourcePath)) {
                $files_section .= ($len > 0 ? "\n" : null) . rtrim($destinationPath, '/') . '/';
            } else {
                $files_section .= ($len > 0 ? "\n" : null) . "%attr({$this->spec->defaultAttrFileMode()},{$this->spec->defaultAttrUser()},{$this->spec->defaultAttrGroup()}) {$destinationPath}";
            }
        }

        $this->spec->setBlock('files', $files_section);

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

    private function pathToPath($path, $dest): void
    {
        if (is_dir($path)) {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $element) {
                if (!\in_array((string)$element, ['.', '..'], true)) {
                    $fullPath = $path . DIRECTORY_SEPARATOR . $element;
                    if (is_dir($fullPath)) {
                        $this->pathToPath($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    } else {
                        $this->copy($fullPath, $dest . DIRECTORY_SEPARATOR . $element);
                    }
                }
            }
        } else {
            if (is_file($path)) {
                $this->copy($path, $dest);
            }
        }
    }

    private function copy($sourcePath, $destinationPath): void
    {
        $destinationFolder = \dirname($destinationPath);
        if (!file_exists($destinationFolder)) {
            mkdir($destinationFolder, 0755, true);
        }
        copy($sourcePath, $destinationPath);
        if (fileperms($sourcePath) !== fileperms($destinationPath)) {
            chmod($destinationPath, fileperms($sourcePath));
        }
    }
}