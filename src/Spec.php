<?php
declare(strict_types=1);

namespace plehanov\rpm;

/**
 * @property-read mixed|null Name
 * @property-read mixed|null Version
 * @property-read mixed|null Release
 * @property-read mixed|null Summary
 * @property-read mixed|null Group
 * @property-read mixed|null License
 * @property-read mixed|null URL
 * @property-read mixed|null BuildRequires
 * @property-read mixed|null BuildArch
 * @property-read mixed|null Requires
 * @property-read mixed|null description
 * @property-read mixed|null prep
 * @property-read mixed|null build
 * @property-read mixed|null install
 * @property-read mixed|null files
 * @property-read mixed|null changelog
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
    private $inlineblocks = [
        'files' => [],
        // %defattr(<file mode>, <user>, <group>, <dir mode>)
        'defattr' => [644, 'root', 'root', 755]
    ];

    public function __get($prop)
    {
        if (array_key_exists($prop, $this->keys)) {
            return $this->keys[$prop];
        }
        if (array_key_exists($prop, $this->blocks)) {
            return $this->blocks[$prop];
        }

        return null;
    }

    public function setProp($prop, $value = null)
    {
        if (\is_array($prop)){
            $this->keys = array_merge($this->keys, $prop);
        } elseif($value !== null) {
            $this->keys[$prop] = $value;
        }

        return $this;
    }

    public function setBlock($prop, $value = null)
    {
        if (\is_array($prop)){
            $this->blocks = array_merge($this->blocks, $prop);
        } elseif($value !== null) {
            $this->blocks[$prop] = $value;
        }
        return $this;
    }

    public function setInlineProp($block, $value)
    {
        $this->inlineblocks[$block] = $value;
        return $this;
    }

    public function setDefaultAttr($fileMode, $user, $group, $dirMode)
    {
        $this->inlineblocks['defattr'] = [$fileMode, $user, $group, $dirMode];
        return $this;
    }

    public function defaultAttrFileMode(): int
    {
        return (int)$this->inlineblocks['defattr'][0];
    }

    public function defaultAttrUser(): string
    {
        return (string)$this->inlineblocks['defattr'][1];
    }

    public function defaultAttrGroup(): string
    {
        return (string)$this->inlineblocks['defattr'][2];
    }

    public function addPermission($file, $mode = '-', $user = '-', $group = '-')
    {
        $this->inlineblocks['files'][] = ['attr', "({$mode},{$user},{$group}) {$file}"];
        return $this;
    }

    public function __toString()
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
                $spec .= '%defattr(' . implode(',', $this->inlineblocks['defattr']) . ")\n";
            }
            $spec .= $value . "\n";
            if (array_key_exists($block, $this->inlineblocks)) {
                foreach ((array)$this->inlineblocks[$block] as [$k, $v]) {
                    $spec .= "%{$k}{$v}\n";
                }
            }
        }

        return $spec;
    }
}