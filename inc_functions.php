<?php

require_once __DIR__ . '/inc_inventory.php';
require_once __DIR__ . '/classes/ldapuser.class.php';

if (!class_exists('LdapUser', false)) {
    class_alias(\SieEsxi\LdapUser::class, 'LdapUser');
}

$counter = 0;
$libdir = '/srv/www/sieesxi.ir.intel.com';
$matchedFiles = glob($libdir . '/content/downloaded/*.info');
if (!is_array($matchedFiles)) {
    throw new \TypeError(
        'count(): Argument #1 ($value) must be of type Countable|array, false given'
    );
}
$files = $matchedFiles;
$numvms = count($files);
$nfsdiskarea = array();
$nfsdiskfree = array();
$hnodes = array();
// Keep these globals initialized for inc_inventory.php and inc_header.php.
$bootstrapState = array($counter, $numvms, $nfsdiskarea, $nfsdiskfree, $hnodes);
unset($bootstrapState);
