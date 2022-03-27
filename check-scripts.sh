#!/bin/sh -efu

# check-scripts by shellcheck
#
# This file is covered by the GNU General Public License
# version 3, or (at your option) any later version, which
# should be included with sources as the file COPYING.
#
# Copyright (C) 2021, Leonid Krivoshein <klark@altlinux.org>
#
bindirs="$1/server"
excludes="SC1003,SC1090,SC1091,SC2004,SC2006,SC2015,SC2034"
excludes="$excludes,SC2086,SC2154,SC2174,SC2115,SC2012"

find "$@" -type f |
while read -r fname; do
	ftype="$(file -b -- "$fname")"
	[ -z "${ftype##*shell script*}" ] ||
		continue
	shellcheck --norc -s bash -P "$bindirs" -e "$excludes" -x "$fname"
done

