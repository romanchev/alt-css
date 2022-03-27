/**
* shell.c -- restricted shell on the CSS server.
*
* This file is covered by the GNU General Public License
* version 3, or (at your option) any later version, which
* should be included with sources as the file COPYING.
*
* Copyright (C) 2021, Leonid Krivoshein <klark@altlinux.org>
*/
#ifdef HAVE_CONFIG
#include "config.h"
#endif

#define _GNU_SOURCE
#include <locale.h>
#include <errno.h>
#include <error.h>
#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <limits.h>
#include <syslog.h>
#include <ctype.h>
#include <pwd.h>

#ifndef NOOP
#define NOOP()		((void) 0)
#endif

#ifndef MAX_ARGS
#define MAX_ARGS	256
#endif


static char path[ PATH_MAX ];
static char *args[ MAX_ARGS ];


static void restricted_shell (char* command) {
  char *e, *p = command;
  int i, cnt = 0;

  /* set locale */

  setlocale (LC_ALL, SYSTEM_LOCALE);

  /* separate the command */

  while ((*p != '\0') && !isblank (*p++))
     cnt ++;
  if (!cnt)
     error (EXIT_FAILURE, 0, "invalid command");
  e = strndup (command, cnt);
  snprintf(path, sizeof(path), "%s/%s", EXEC_DIR, e);
  args[0] = path;
  free (e);

  /* build new arguments array */

  for (i=1; i < MAX_ARGS-1; i++) {
     while ((*p != '\0') && isblank (*p))
        p ++;
     if (*p == '\0') {
        i --;
        break;
     }
     e = p;
     cnt = 0;
     while ((*p != '\0') && !isblank (*p++))
        cnt ++;
     args[i] = strndup (e, cnt);
  }

  /* try to execute command */

  args[ i+1 ] = NULL;
  execv (path, args);
  error (EXIT_FAILURE, errno, "execv: %s", command);
}

int main (int argc, char** argv) {
  struct passwd* pw = NULL;
  char* username = NULL;
  char* homedir = NULL;
  char* tmpdir = NULL;
  uid_t uid = getuid();
  size_t n;

  /* security check */

  if (uid < 500)
     error (EXIT_FAILURE, 0, "disallowed uid");
  if ((pw = getpwuid (uid)) == NULL)
     error (EXIT_FAILURE, errno, "getpwuid");
  n = strlen (USERNAME_PREFIX);
  username = &pw->pw_name[ n ];
  if ((strlen (pw->pw_name) < n) || !username[0] ||
	strncmp (pw->pw_name, USERNAME_PREFIX, n))
  {
     error (EXIT_FAILURE, 0, "disallowed username");
  }
  if ((asprintf (&homedir, "%s/%s", HOMEDIRS, username) < 0) ||
      (asprintf (&tmpdir, "%s/%s/tmp", HOMEDIRS, username) < 0))
  {
     error (EXIT_FAILURE, errno, "asprintf");
  }
  if (strcmp (pw->pw_dir, homedir))
     error (EXIT_FAILURE, 0, "invalid home directory");
  if (chdir (tmpdir) < 0) {
     if (unlink (tmpdir))
	NOOP (); /* ignore error here */
     if (!chdir (homedir) && (mkdir (tmpdir, 0700) < 0))
	NOOP (); /* ignore error here */
  }
  if ((chdir (tmpdir) < 0) || (chdir (homedir) < 0))
     error (EXIT_FAILURE, errno, "chdir");

  /* setup new environment */

  if (clearenv () < 0)
     error (EXIT_FAILURE, errno, "clearenv");
  if ((setenv ("USER", pw->pw_name, 1) < 0) ||
      (setenv ("LOGNAME", pw->pw_name, 1) < 0) ||
      (setenv ("HOME", homedir, 1) < 0) ||
      (setenv ("PATH", "/bin:/usr/bin", 1) < 0) ||
      (setenv ("LANG", SYSTEM_LOCALE, 1) < 0) ||
      (setenv ("TMPDIR", tmpdir, 1) < 0) ||
      (setenv ("CSS_USER", username, 1) < 0) ||
      (setenv ("CSS_LIBDIR", EXEC_DIR, 1) < 0))
  {
     error (EXIT_FAILURE, errno, "setenv");
  }

  /* check arguments and log this dial */

  if (argc < 3)
     error (EXIT_FAILURE, 0, "Not enough arguments.");
  else if (argc > 3)
     error (EXIT_FAILURE, 0, "Too many arguments.");
  if (strncmp ("-c", argv[1], 3))
     error (EXIT_FAILURE, EINVAL, "%s", argv[1]);
  openlog (program_invocation_short_name, LOG_PID, LOG_USER);
  syslog (LOG_INFO, "%s: %s", username, argv[2]);
  closelog ();

  /* parse and execute command */

  restricted_shell (argv[2]);
  return EXIT_FAILURE;
}

