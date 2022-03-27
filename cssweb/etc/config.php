<?php

$empty		= "&nbsp;";
$GITPH		= ".placeholder";
$dateFmtRegex	= "\d\d\.\d\d\.\d\d\d\d";
$dateFormat	= "d.m.Y";

// Hardware platforms
$hw_platforms = array (
    "x86_64"  => "Intel x64, amd64",
    "i586"    => "Intel x32, IA32",
    "aarch64" => "ARM64, ARMv8",
    "armh"    => "ARM32, ARMv7",
    "e2k"     => "Эльбрус, v3",
    "e2kv4"   => "Эльбрус, v4",
//  "e2kv5"   => "Эльбрус, v5",
    "mipsel"  => "MIPS32/LE",
    "ppc64le" => "Power 8/9, OpenPower",
);

// Compatibility extension rules
$comp_ext_rules = array (
    "8SP" => array (
	"c81ws"  => ":c81srv",
	"c81srv" => "c81ws:",
    ),
    "P9"  => array (
	"c9fws"  => ":c9fsrv",
	"c9fsrv" => "c9fws:",
	"p9ws"   => "p9edu:p9edu,p9srv",
	"p9edu"  => "p9ws:p9srv",
	"p9srv"  => "p9ws,p9edu:p9edu",
    ),
    "P10" => array (
	"p10ws"  => "p10edu:p10edu,p10srv",
	"p10edu" => "p10ws:p10srv",
	"p10srv" => "p10ws,p10edu:p10edu",
    ),
);

// Available platforms for the table columns
$avail_platforms = array (
    "8SP" => array (
	"c81ws"  => "x86_64,i586,e2k,e2kv4",
	"c81srv" => "x86_64,i586,e2k,e2kv4",
    ),
    "P9"  => array (
	"c9fws"  => "x86_64,i586,aarch64,armh,e2k,e2kv4",
	"c9fsrv" => "x86_64,i586,aarch64,e2k,e2kv4,ppc64le",
	"p9ws"   => "x86_64,i586,aarch64,armh,e2k,e2kv4,mipsel",
	"p9edu"  => "x86_64,i586,aarch64,e2k,e2kv4",
	"p9srv"  => "x86_64,aarch64,e2k,e2kv4,ppc64le",
    ),
    "P10" => array (
	"p10ws"  => "x86_64,i586,aarch64,e2k,e2kv4",
	"p10edu" => "x86_64,i586,aarch64,e2k,e2kv4",
	"p10srv" => "x86_64,aarch64,e2k,e2kv4",
    ),
);

// Suitable codes
define("SUITES_UNIVERSAL", 1);
define("SUITES_DESKTOP",   2);
define("SUITES_SERVER",    3);
define("SUITES_NOEXPAND",  4);
//
$SUITES = array (
    "Universal" => SUITES_UNIVERSAL,
    "Desktop"   => SUITES_DESKTOP,
    "Server"    => SUITES_SERVER,
    "NoExpand"  => SUITES_NOEXPAND
);

// Constants
define("FCAT_NameIDX", 0);
define("FCAT_FullIDX", 1);
define("FCAT_SuitIDX", 2);
define("FCAT_DisbIDX", 3);
define("FCAT_DefsIDX", 4); /* optional */
//
define("DIST_NameIDX", 0);
define("DIST_DescIDX", 1);
define("DIST_DateIDX", 2);
define("DIST_ArchIDX", 3);
define("DIST_HideIDX", 4);
define("DIST_LablIDX", 5);
//
define("VEND_NameIDX", 0);
define("VEND_PageIDX", 1);
define("VEND_NoteIDX", 2); /* optional */
//
define("PROD_VendIDX", 0);
define("PROD_CatgIDX", 1);
define("PROD_NameIDX", 2);
define("PROD_PageIDX", 3);
define("PROD_SuitIDX", 4);
define("PROD_InstIDX", 5);
define("PROD_NoteIDX", 6); /* optional */
//
define("CTAB_DcolIDX", 0);
define("CTAB_ArchIDX", 1);
define("CTAB_VendIDX", 2);
define("CTAB_ProdIDX", 3);
define("CTAB_VersIDX", 4);
define("CTAB_CompIDX", 5);
define("CTAB_CertIDX", 6);
define("CTAB_SuitIDX", 7);
define("CTAB_InstIDX", 8);
define("CTAB_NoteIDX", 9); /* optional */
//
define("IGNORE_PRODINST", true);
define("IGNORE_CERTNUMB", true);

?>