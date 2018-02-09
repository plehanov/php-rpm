<?php

use Plehanov\RPM\Spec;

class SpecTest extends PHPUnit\Framework\TestCase
{
    public function testSimple() {
        $spec = new Spec();
        $spec->setProp('Name','simplepackage')
            ->setProp('Version', '1.0.0')
            ->setProp('Release', '1')
            ->setBlock('Description', 'My software description')
            ->addPerm('/etc/sample-package/bin/index.php', 644);
        $this->assertEquals(<<<SPEC
Name: simplepackage
Version: 1.0.0
Release: 1
Summary: ...
License: free
BuildArch: noarch

%description


%prep
%autosetup -c package

%build


%install
rm -rf %{buildroot}
mkdir -p %{buildroot}
cp -rp * %{buildroot}


%changelog


%Description
My software description

%files
%defattr(644,root,root,755)
%attr(644,-,-) /etc/sample-package/bin/index.php

SPEC
        , (string)$spec);
    }

    public function testComplex()
    {
        $spec = new Spec();
        $spec->setProp('Name','simplepackage')
            ->setProp('Version', '1.0.0')
            ->setProp('Release', '1')
            ->setProp('Summary', 'test')
            ->setProp('Group', 'group')
            ->setProp('License', 'GPL')
            ->setProp('URL', 'url')
            ->setProp('BuildRequires', 'buildRequires')
            ->setProp('BuildArch', 'noarch')
            ->setProp('Requires', 'requires')
            ->setBlock('description', 'Long..........
Very long')
            ->setBlock('prep', '%autosetup -c package')
            ->setBlock('build', '')
            ->setBlock('install', 'rm -rf %{buildroot}
mkdir -p %{buildroot}%{_bindir}
mkdir -p %{buildroot}%{_libdir}/%{name}
cp -p binary %{buildroot}%{_bindir}/binary
cp -p src/* %{buildroot}%{_libdir}/%{name}/')
            ->setDefAttr(664, 'apache', 'apache', 775)
            ->addPerm('%{buildroot}%{bindir}/binary1', 644)
            ->addPerm('%{buildroot}%{bindir}/binary2')
            ->addPerm('%{buildroot}%{bindir}/binary3', 644,'apache', 'apache')
            ->addPerm('%{buildroot}%{bindir}/binary3')
            ->addPerm('%{buildroot}%{bindir}/binary2', 644,'apache')
            ->addPerm('%{buildroot}%{bindir}/binary1')
            ->addPerm('%{buildroot}%{bindir}/binary')
            ->addPerm('%{buildroot}%{_libdir}/%{name}/*')
            ->setBlock('changelog', '- 1.0.0.');
        $this->assertEquals(<<<SPEC
Name: simplepackage
Version: 1.0.0
Release: 1
Summary: test
Group: group
License: GPL
URL: url
BuildRequires: buildRequires
BuildArch: noarch
Requires: requires

%description
Long..........
Very long

%prep
%autosetup -c package

%build


%install
rm -rf %{buildroot}
mkdir -p %{buildroot}%{_bindir}
mkdir -p %{buildroot}%{_libdir}/%{name}
cp -p binary %{buildroot}%{_bindir}/binary
cp -p src/* %{buildroot}%{_libdir}/%{name}/

%changelog
- 1.0.0.

%files
%defattr(664,apache,apache,775)
%attr(644,-,-) %{buildroot}%{bindir}/binary1
%attr(644,apache,-) %{buildroot}%{bindir}/binary2
%attr(644,apache,apache) %{buildroot}%{bindir}/binary3
%{buildroot}%{bindir}/binary
%{buildroot}%{_libdir}/%{name}/*

SPEC
        , (string)$spec);
    }
}