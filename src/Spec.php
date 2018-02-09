<?php
declare(strict_types=1);

namespace Plehanov\RPM;

/**
 * @property mixed|null Name
 * @property mixed|null Version
 * @property mixed|null Release
 * @property mixed|null Summary
 * @property mixed|null Group
 * @property mixed|null License
 * @property mixed|null URL
 * @property mixed|null BuildRequires
 * @property mixed|null BuildArch
 * @property mixed|null Requires
 * @property mixed|null description
 * @property mixed|null prep
 * @property mixed|null build
 * @property mixed|null install
 * @property mixed|null files
 * @property mixed|null changelog
 */
class Spec
{
    protected $destinationFolderMask = '%{destroot}';
    protected $destinationFolder = '';

    private $keys = [
        'Name' => '',
        'Version' => '0.1',
        'Release' => '1',
        'Summary' => '...',
        'Group' => '',
        'License' => 'free',
        'URL' => '',
        'BuildRequires' => '',
        'BuildArch' => 'noarch',
        'Requires' => '',
    ];

    private $blocks = [
        'description' => '',
        'prep' => '%autosetup -c package',
        'build' => '',
        'install' => ['rm -rf %{buildroot}', 'mkdir -p %{buildroot}', 'cp -rp * %{buildroot}'],
        'changelog' => '',
    ];
    private $inlineBlocks = [
        'files' => [],
        'exclude' => [],
        'defattr' => [644, 'root', 'root', 755]
    ];

    public function __get(string $name)
    {
        if (array_key_exists($name, $this->keys)) {
            return (string) $this->keys[$name];
        }
        if (array_key_exists($name, $this->blocks)) {
            return $this->blocks[$name];
        }

        if (\in_array($name, ['files', 'exclude'], true)) {
            $out = '';
            foreach ((array)$this->inlineBlocks[$name] as $file) {
                $out .= (\is_string($file) ? $file : $file[0]) . "\n";
            }
            return $out;
        }

        return null;
    }

    public function __set(string $name, $value): void
    {
        if (array_key_exists($name, $this->keys)) {
            $this->keys[$name] = $value;
        }
        if (array_key_exists($name, $this->blocks)) {
            $this->blocks[$name] = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->keys) || array_key_exists($name, $this->blocks);
    }
    
    public function setDestinationFolder(string $path): self
    {
        $this->destinationFolder = $path;

        return $this;
    }

    /**
     * @param string|array  $prop - name or key-value array
     * @param null $value
     * @return Spec
     */
    public function setProp($prop, $value = null): self
    {
        if (\is_array($prop)){
            $this->keys = array_merge($this->keys, $prop);
        } elseif($value !== null) {
            $this->keys[$prop] = $value;
        }

        return $this;
    }

    /**
     * @param string|array $prop - name or key-value array
     * @param string       $value
     * @return Spec
     */
    public function setBlock($prop, $value = ''): self
    {
        if (\is_array($prop)){
            $this->blocks = array_merge($this->blocks, $prop);
        } elseif($value !== '') {
            $this->blocks[$prop] = $value;
        }

        return $this;
    }

    public function setInlineProp(string $block, $value): self
    {
        $this->inlineBlocks[$block] = $value;

        return $this;
    }

    public function appendInstallCommand(string $command): self
    {
        $this->blocks['install'][] = $command;

        return $this;
    }

    public function setDefAttr(int $fileMode = 644, string $user = 'root', string $group = 'root', int $dirMode = 755): self
    {
        $this->inlineBlocks['defattr'] = [$fileMode, $user, $group, $dirMode];

        return $this;
    }

    public function defAttrMode(): int
    {
        return (int)$this->inlineBlocks['defattr'][0];
    }

    public function defAttrUser(): string
    {
        return (string)$this->inlineBlocks['defattr'][1];
    }

    public function defAttrGroup(): string
    {
        return (string)$this->inlineBlocks['defattr'][2];
    }

    /**
     * @param string $entity - file or folder
     * @param null   $mode
     * @param null   $user
     * @param null   $group
     * @return Spec
     */
    public function addPerm(string $entity, $mode = null, $user = null, $group = null): self
    {
        $elem = $this->inlineBlocks['files'][$entity] ?? null;

        if ($elem && \is_array($this->inlineBlocks['files'][$entity])) {
            if ($elem[1] === null && $elem[2] === null && $elem[3] === null) {
                $this->inlineBlocks['files'][$entity][0] = $entity;
            }
        } elseif ($mode || $user || $group) {
            $this->inlineBlocks['files'][$entity] = [$entity, $mode ?? '-', $user ?? '-', $group ?? '-'];
        } else {
            $this->inlineBlocks['files'][$entity] = $entity;
        }

        return $this;
    }

    /**
     * @param string $entity - /opt/myapp/[bin|data|whatever]
     * @param bool   $isFolder
     * @return Spec
     */
    public function addExclude(string $entity, $isFolder = false): self
    {
        $this->inlineBlocks['exclude'][$entity] = $isFolder;

        return $this;
    }

    public function __toString(): string
    {
        return $this->keysToString() . $this->blocksToString();
    }

    protected function keysToString(): string
    {
        $result = '';
        foreach ($this->keys as $key => $value) {
            if ($value === '') {
                continue;
            }
            $result .= sprintf('%s: %s' . "\n", $key, $value);
        }

        return $result;
    }

    protected function blocksToString(): string
    {
        $result = '';
        foreach ($this->blocks as $block => $element) {
            $result .= \is_string($element) ? "\n%{$block}\n{$element}\n" : ("\n%{$block}\n" . implode("\n", $element) . "\n");
        }

        $result .= "\n%files\n%defattr(" . implode(',', $this->inlineBlocks['defattr']) . ")\n";

        foreach ((array)$this->inlineBlocks['files'] as $element) {
            $result .= \is_string($element) ? "$element\n" : "%attr({$element[1]},{$element[2]},{$element[3]}) {$element[0]}\n";
        }
        foreach ((array)$this->inlineBlocks['exclude'] as $element => $isFolder) {
            $result .= $isFolder ?  "%exclude %dir $element\n" : "%exclude $element\n";
        }

        return $this->replaceFolderMask($result);
    }

    /**
     * @param string $path - method remove last dash
     * @return string
     */
    protected function replaceFolderMask(string $path): string
    {
        return str_replace($this->destinationFolderMask, rtrim($this->destinationFolder, DIRECTORY_SEPARATOR), $path);
    }
}