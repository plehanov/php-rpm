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
        'prep' => '%autosetup',
        'build' => '',
        'install' => "rm -rf %{buildroot}\nmkdir -p %{buildroot}\ncp -rp * %{buildroot}\n",
        'files' => '',
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
    public function setBlock($prop, $value = null): self
    {
        if (\is_array($prop)){
            $this->blocks = array_merge($this->blocks, $prop);
        } elseif($value !== null) {
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

    public function addPerm($file, $mode = '-', $user = '-', $group = '-'): self
    {
        $this->inlineBlocks['files'][] = ['attr', "({$mode},{$user},{$group}) {$file}"];

        return $this;
    }

    public function __toString(): string
    {
        $spec = '';
        foreach ($this->keys as $key => $value) {
            if ($value === '') {
                continue;
            }
            $spec .= sprintf('%s: %s' . "\n", $key, $value);
        }
        foreach ($this->blocks as $block => $value) {
            $spec .= "\n" . '%' . $block . "\n";
            if ($block === 'files') {
                $spec .= '%defattr(' . implode(',', $this->inlineBlocks['defattr']) . ")\n";
            }
            $spec .= $value . "\n";
            if (array_key_exists($block, $this->inlineBlocks)) {
                foreach ((array)$this->inlineBlocks[$block] as [$k, $v]) {
                    $spec .= "%{$k}{$v}\n";
                }
            }
        }

        return $spec;
    }
}