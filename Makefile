#
# This file is covered by the GNU General Public License
# version 3, or (at your option) any later version, which
# should be included with sources as the file COPYING.
#
# Copyright (C) 2020-2022, Leonid Krivoshein <klark@altlinux.org>
#
DESTDIR =
bindir = /usr/bin
libexecdir = /usr/libexec
document_root = /var/www/html

PACKAGE_NAME = $(shell cat PACKAGE)
PACKAGE_VERSION = $(shell cat VERSION)
VERSION_MAJOR = $(shell cat VERSION |cut -f1 -d.)
VERSION_MINOR = $(shell cat VERSION |cut -f2 -d.)
VERSION_PATCH = $(shell cat VERSION |cut -f3 -d.)
EXEC_DIR = ${libexecdir}/${PACKAGE_NAME}
CLIENT_PROG_DIR = ${libexecdir}/csi
USERNAME_PREFIX = ${PACKAGE_NAME}_
SYSTEM_LOCALE = ru_RU.utf8
HOMEDIRS = /home

CPPFLAGS = -std=gnu99 -Wall -Wextra
CFLAGS = -pipe -O2 -g -DHAVE_CONFIG
TARGETS = src/css-sh src/csi

SERVER_SCRIPTS = $(shell ls server/*)
CLIENT_DIRS = accel actions messages templates

.PHONY: all install install-server-scripts copy-client-dirs clean

all: src/config.h ${TARGETS}

install: ${TARGETS} install-server-scripts copy-client-dirs
	install -d -m0755 ${DESTDIR}${bindir}
	install -p -m0755 ${TARGETS} ${DESTDIR}${bindir}/

clean:
	rm -f -- PACKAGE VERSION src/config.h ${TARGETS}

src/csi: src/csi.in
	sed -e 's,@PACKAGE_VERSION@,${PACKAGE_VERSION},g' \
	    -e 's,@VERSION_MAJOR@,${VERSION_MAJOR},g' \
	    -e 's,@VERSION_MINOR@,${VERSION_MINOR},g' \
	    -e 's,@USERNAME_PREFIX@,${USERNAME_PREFIX},g' \
	    -e 's,@libexecdir@,${libexecdir},g' \
		src/csi.in >src/csi
	chmod --reference=src/csi.in src/csi

src/config.h: src/config.h.in PACKAGE VERSION
	sed \
	    -e 's,@PACKAGE_NAME@,${PACKAGE_NAME},g' \
	    -e 's,@PACKAGE_VERSION@,${PACKAGE_VERSION},g' \
	    -e 's,@VERSION_MAJOR@,${VERSION_MAJOR},g' \
	    -e 's,@VERSION_MINOR@,${VERSION_MINOR},g' \
	    -e 's,@VERSION_PATCH@,${VERSION_PATCH},g' \
	    -e 's,@USERNAME_PREFIX@,${USERNAME_PREFIX},g' \
	    -e 's,@SYSTEM_LOCALE@,${SYSTEM_LOCALE},g' \
	    -e 's,@EXEC_DIR@,${EXEC_DIR},g' \
	    -e 's,@HOMEDIRS@,${HOMEDIRS},g' \
		src/config.h.in >src/config.h
	chmod --reference=src/config.h.in src/config.h

src/css-sh: src/config.h src/shell.c
	$(CC) $(CFLAGS) $(CPPFLAGS) -o src/css-sh src/shell.c

install-server-scripts: ${SERVER_SCRIPTS} src/css-admin
	install -d -m0755 ${DESTDIR}/etc/${PACKAGE_NAME}
	install -m0644 engine.php csi-server.conf ${DESTDIR}/etc/${PACKAGE_NAME}/
	:> ${DESTDIR}/etc/${PACKAGE_NAME}/csi-client.conf
	install -d -m0755 ${DESTDIR}${EXEC_DIR}
	install -p -m0755 $^ ${DESTDIR}${EXEC_DIR}/
	chmod -x ${DESTDIR}${EXEC_DIR}/csi-server-common
	mkdir -p -m0755 ${DESTDIR}${bindir}
	cp -Lf src/css-admin ${DESTDIR}${bindir}/
	chmod +x -- ${DESTDIR}${bindir}/css-admin
	install -d -m0755 ${DESTDIR}${document_root}
	cp -aRf cssweb/* ${DESTDIR}${document_root}/

copy-client-dirs: ${CLIENT_DIRS}
	install -d -m0755 ${DESTDIR}${CLIENT_PROG_DIR}
	cp -Lrf $^ ${DESTDIR}${CLIENT_PROG_DIR}/

