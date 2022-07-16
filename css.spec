%define _unpackaged_files_terminate_build 1

%ifarch %e2k %mips riscv64
# shellcheck is not available on these architectures
%def_disable check
%endif

Name: css
Version: 1.7.5.20220717
Release: alt1

Summary: Compatibility Service Suite
License: GPLv3+
Group: Databases

Source: %name-%version.tar
Url: https://cssdev.basealt.space/
Packager: Leonid Krivoshein <klark@altlinux.org>

%{!?_disable_check:BuildRequires: shellcheck}

%description
This package contains common CSS build parts.

%package server
Summary: CSS server-side scripts and restricted shell
Group: System/Servers
Requires: /usr/bin/gs
Requires: /usr/bin/convert
Requires: bash-builtin-lockf >= 0:0.2
Requires: git-core
Requires: nginx
Requires: openssh-server
Requires: php7
Requires: php7-fpm-fcgi
Requires: php7-gd
Requires: php7-libs
Requires: php7-mbstring
Requires: php7-yaml
Requires: rsync
Requires: sudo

%description server
Server-side restricted shell and scripts for Compatibility Service Suite.

%package client
Summary: CSS client-side command-line interface
Group: Databases
BuildArch: noarch
Requires: git-core
Requires: openssh-clients

%description client
Client-side command-line interface for Compatibility Service Suite.

%package examples
Summary: CSS example database
Group: Databases
BuildArch: noarch

%description examples
Example database for Compatibility Service Suite.

%prep
%setup

%build
echo "%name" >PACKAGE
echo "%version" >VERSION
%make_build

%install
%makeinstall_std

%post server
grep -qs "/usr/bin/css-sh" /etc/shells ||
	echo "/usr/bin/css-sh" >>/etc/shells

%postun server
sed -i -e "/\/usr\/bin\/css\-sh/d" /etc/shells

%files server
%dir /etc/%name
%config(noreplace) /etc/%name/engine.php
%config(noreplace) /etc/%name/csi-server.conf
%_bindir/%name-admin
%_bindir/%name-sh
/usr/libexec/%name
/var/www/html/*

%files client
%ghost %config(noreplace) /etc/%name/csi-client.conf
%_bindir/csi
/usr/libexec/csi

%files examples
%doc examples/CSI

%check
./check-scripts.sh \
	%buildroot/usr/libexec/%name \
	%buildroot/usr/libexec/csi \
	%buildroot%_bindir/csi \
	%buildroot/var/www/html/bin

%changelog
* Sun Jul 17 2022 Leonid Krivoshein <klark@altlinux.org> 1.7.5.20220717-alt1
- Backup and restore with the static contents.
- Sort by dates added, at now it use by default.
- Document links widget now created on the fly.
- Last changes widget incrased to 20 items.
- Temporary fix for reestr.gov.ru URL's.
- Cache description updated.

