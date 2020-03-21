<?php
/* Generate lookup table from unicode.org mapping file (SHIFTJIS.TXT by default). */
/*
    libzint - the open source barcode library
    Copyright (C) 2008-2019 Robin Stuart <rstuart114@gmail.com>
*/
/* vim: set ts=4 sw=4 et : */

$basename = basename(__FILE__);
$dirname = dirname(__FILE__);

$opts = getopt('d:f:o:s:');
$data_dirname = isset($opts['d']) ? $opts['d'] : ($dirname . '/data'); // Where to load file from.
$file_name = isset($opts['f']) ? $opts['f'] : 'SHIFTJIS.TXT'; // Name of file.
$out_dirname = isset($opts['o']) ? $opts['o'] : ($dirname . '/..'); // Where to put output.
$suffix_name = isset($opts['s']) ? $opts['s'] : 'sjis_tab'; // Suffix of table and output file.

$file = $data_dirname . '/' . $file_name;

// Read the file.

if (($get = file_get_contents($file)) === false) {
    error_log($error = "$basename: ERROR: Could not read mapping file \"$file\"");
    exit($error . PHP_EOL);
}

$lines = explode("\n", $get);

// Parse the file.

$tab_lines = array();
$sort = array();
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strncmp($line, '0x', 2) !== 0) {
        continue;
    }
    $tab_lines[] = preg_replace_callback('/^0x([0-9A-F]{2,4})[ \t]+0x([0-9A-F]{4}).*$/', function ($matches) {
        global $sort;
        $mb = hexdec($matches[1]);
        $unicode = hexdec($matches[2]);
        $sort[] = $unicode;
        return sprintf("    0x%04X, 0x%04X,", $mb, $unicode);
    }, $line);
}

array_multisort($sort, $tab_lines);

// Output.

$out = array();
$out[] = '/* Generated by ' . $basename . ' from ' . $file_name . ' */';
$out[] = 'static const unsigned short test_' . $suffix_name . '[] = {';
$out = array_merge($out, $tab_lines);
$out[] = '};';

file_put_contents($out_dirname . '/test_' . $suffix_name . '.h', implode("\n", $out) . "\n");
