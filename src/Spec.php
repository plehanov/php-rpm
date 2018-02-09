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
        'install' => "rm -rf %{buildroot}\nmkdir -p %{buildroot}\ncp -rp * %{buildroot}\n",
        'changelog' => '',
    ];
    private $inlineBlocks = [
        'files' => [],
        'defattr' => [644, 'root', 'root', 755]
    ];

    public function __get(string $name): ?string
    {
        if (array_key_exists($name, $this->keys)) {
            return (string) $this->keys[$name];
        }
        if (array_key_exists($name, $this->blocks)) {
            return (string) $this->blocks[$name];
        }
        if ($name === 'files') {
            $out = '';
            foreach ((array)$this->inlineBlocks['files'] as $file) {
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
     * @param string|array  $prop - name or key-value array
     * @param null $value
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

    public function addPerm($file, $mode = null, $user = null, $group = null): self
    {
        $elem = $this->inlineBlocks['files'][$file] ?? null;

        if ($elem && \is_array($this->inlineBlocks['files'][$file])) {
            if ($elem[1] === null && $elem[2] === null && $elem[3] === null) {
                $this->inlineBlocks['files'][$file][0] = $file;
            }
        } elseif ($mode || $user || $group) {
            $this->inlineBlocks['files'][$file] = [$file, $mode ?? '-', $user ?? '-', $group ?? '-'];
        } else {
            $this->inlineBlocks['files'][$file] = $file;
        }

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
        foreach ($this->blocks as $block => $value) {
            $result .= "\n%{$block}\n{$value}\n";
        }
        $result .= "\n%files\n%defattr(" . implode(',', $this->inlineBlocks['defattr']) . ")\n";
        foreach ((array)$this->inlineBlocks['files'] as $element) {
            $result .= \is_string($element) ? "$element\n" : "%attr({$element[1]},{$element[2]},{$element[3]}) {$element[0]}\n";
        }

        return $result;
    }
}