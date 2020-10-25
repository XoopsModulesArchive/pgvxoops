<?php

/**
 * UFPDF, Unicode Free PDF generator
 * Version:  0.1
 *           based on FPDF 1.52 by Olivier PLATHEY
 * Date:     2004-09-01
 * Author:   Steven Wittens <steven@acko.net>
 * License:  GPL
 *
 * UFPDF is a modification of FPDF to support Unicode through UTF-8.
 * @see     fpdf.php
 * @see     reportpdf.php
 * @version $Id: ufpdf.php,v 1.2 2006/01/09 00:46:35 skenow Exp $
 */
if (!class_exists('UFPDF')) {
    define('UFPDF_VERSION', '0.1');

    if (file_exists('fpdf.php')) {
        require_once __DIR__ . '/fpdf.php';
    } elseif (file_exists('ufpdf/fpdf.php')) {
        require_once __DIR__ . '/ufpdf/fpdf.php';
    }

    /**
     * Main UFPDF class for creating Unicode PDF documents
     *
     * derives from FPDF class
     * @see FPDF
     */

    class UFPDF extends FPDF
    {
        public $embed_fonts;

        /*******************************************************************************
         *                                                                              *
         *                               Public methods                                 *
         *                                                                              *
         ******************************************************************************
         * @param string $orientation
         * @param string $unit
         * @param string $format
         */

        public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
        {
            $this->embed_fonts = true;

            parent::__construct($orientation, $unit, $format);
        }

        public function SetEmbedFonts($embed)
        {
            $this->embed_fonts = $embed;
        }

        public function GetStringWidth($s)
        {
            //Get width of a string in the current font

            $s = (string)$s;

            $codepoints = $this->utf8_to_codepoints($s);

            $cw = &$this->CurrentFont['cw'];

            $w = 0;

            //print " [$s]";

            foreach ($codepoints as $indexval => $cp) {
                //print "[$cp]";

                if (isset($cw[$cp])) {
                    $w += $cw[$cp];
                } elseif (isset($cw[ord($cp)])) {
                    $w += $cw[ord($cp)];
                } elseif (isset($cw[chr($cp)])) {
                    $w += $cw[chr($cp)];
                } else {
                    $w += 500;
                }

                //-- adjust width for incorrect hebrew chars
                //if ($cp>1480 && $cp < 1550) $w -= $cw[$cp]/1.8;
            }

            return $w * $this->FontSize / 1000;
        }

        public function AddFont($family, $style = '', $file = '')
        {
            //Add a TrueType or Type1 font

            $family = mb_strtolower($family);

            if ('arial' == $family) {
                $family = 'helvetica';
            }

            $style = mb_strtoupper($style);

            if ('IB' == $style) {
                $style = 'BI';
            }

            if (isset($this->fonts[$family . $style])) {
                $this->Error('Font already added: ' . $family . ' ' . $style);
            }

            if ('' == $file) {
                $file = str_replace(' ', '', $family) . mb_strtolower($style) . '.php';
            }

            if (defined('FPDF_FONTPATH')) {
                $file = FPDF_FONTPATH . $file;
            }

            include $file;

            if (!isset($name)) {
                $this->Error('Could not include font definition file');
            }

            $i = count($this->fonts) + 1;

            $this->fonts[$family . $style] = ['i' => $i, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'file' => $file, 'ctg' => $ctg];

            if ($file) {
                if ('TrueTypeUnicode' == $type) {
                    $this->FontFiles[$file] = ['length1' => $originalsize];
                } else {
                    $this->FontFiles[$file] = ['length1' => $size1, 'length2' => $size2];
                }
            }
        }

        public function Text($x, $y, $txt)
        {
            //Output a string

            $s = sprintf('BT %.2f %.2f Td %s Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escapetext($txt));

            if ($this->underline and '' != $txt) {
                $s .= ' ' . $this->_dounderline($x, $y, $this->GetStringWidth($txt), $txt);
            }

            if ($this->ColorFlag) {
                $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
            }

            $this->_out($s);
        }

        public function AcceptPageBreak()
        {
            //Accept automatic page break or not

            return $this->AutoPageBreak;
        }

        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
        {
            //Output a cell

            $k = $this->k;

            if ($this->y + $h > $this->PageBreakTrigger and !$this->InFooter and $this->AcceptPageBreak()) {
                //Automatic page break

                $x = $this->x;

                $ws = $this->ws;

                if ($ws > 0) {
                    $this->ws = 0;

                    $this->_out('0 Tw');
                }

                $this->AddPage($this->CurOrientation);

                $this->x = $x;

                if ($ws > 0) {
                    $this->ws = $ws;

                    $this->_out(sprintf('%.3f Tw', $ws * $k));
                }
            }

            if (0 == $w) {
                $w = $this->w - $this->rMargin - $this->x;
            }

            $s = '';

            if (1 == $fill or 1 == $border) {
                if (1 == $fill) {
                    $op = (1 == $border) ? 'B' : 'f';
                } else {
                    $op = 'S';
                }

                $s = sprintf('%.2f %.2f %.2f %.2f re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
            }

            if (is_string($border)) {
                $x = $this->x;

                $y = $this->y;

                if (is_int(mb_strpos($border, 'L'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
                }

                if (is_int(mb_strpos($border, 'T'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
                }

                if (is_int(mb_strpos($border, 'R'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }

                if (is_int(mb_strpos($border, 'B'))) {
                    $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }
            }

            if ('' != $txt) {
                $width = $this->GetStringWidth($txt);

                if ('R' == $align) {
                    $dx = $w - $this->cMargin - $width;
                } elseif ('C' == $align) {
                    $dx = ($w - $width) / 2;
                } else {
                    $dx = $this->cMargin;
                }

                if ($this->ColorFlag) {
                    $s .= 'q ' . $this->TextColor . ' ';
                }

                $txtstring = $this->_escapetext($txt);

                $s .= sprintf('BT %.2f %.2f Td %s Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txtstring);

                if ($this->underline) {
                    $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $width, $txt);
                }

                if ($this->ColorFlag) {
                    $s .= ' Q';
                }

                if ($link) {
                    $this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $width, $this->FontSize, $link);
                }
            }

            if ($s) {
                $this->_out($s);
            }

            $this->lasth = $h;

            if ($ln > 0) {
                //Go to next line

                $this->y += $h;

                if (1 == $ln) {
                    $this->x = $this->lMargin;
                }
            } else {
                $this->x += $w;
            }
        }

        public function AliasNbPages($alias = '{nb}')
        {
            //Define an alias for total number of pages

            $this->AliasNbPages = $this->utf8_to_utf16be($alias, false);
        }

        /*******************************************************************************
         *                                                                              *
         *                              Protected methods                               *
         *                                                                              *
         ******************************************************************************
         * @param $font
         */

        public function _puttruetypeunicode($font)
        {
            //Type0 Font

            $this->_newobj();

            $this->_out('<</Type /Font');

            $this->_out('/Subtype /Type0');

            $this->_out('/BaseFont /' . $font['name']);

            $this->_out('/Encoding /Identity-H');

            $this->_out('/DescendantFonts [' . ($this->n + 1) . ' 0 R]');

            $this->_out('>>');

            $this->_out('endobj');

            //CIDFont

            $this->_newobj();

            $this->_out('<</Type /Font');

            $this->_out('/Subtype /CIDFontType2');

            $this->_out('/BaseFont /' . $font['name']);

            $this->_out('/CIDSystemInfo <</Registry (Adobe) /Ordering (UCS) /Supplement 0>>');

            $this->_out('/FontDescriptor ' . ($this->n + 1) . ' 0 R');

            $c = 0;

            $widths = '';

            foreach ($font['cw'] as $i => $w) {
                $widths .= $i . ' [' . $w . '] ';
            }

            $this->_out('/W [' . $widths . ']');

            $this->_out('/CIDToGIDMap ' . ($this->n + 2) . ' 0 R');

            $this->_out('>>');

            $this->_out('endobj');

            //Font descriptor

            $this->_newobj();

            $this->_out('<</Type /FontDescriptor');

            $this->_out('/FontName /' . $font['name']);

            $s = '';

            foreach ($font['desc'] as $k => $v) {
                $s .= ' /' . $k . ' ' . $v;
            }

            if ($font['file']) {
                $s .= ' /FontFile2 ' . $this->FontFiles[$font['file']]['n'] . ' 0 R';
            }

            $this->_out($s);

            $this->_out('>>');

            $this->_out('endobj');

            //Embed CIDToGIDMap

            $this->_newobj();

            if (defined('FPDF_FONTPATH')) {
                $file = FPDF_FONTPATH . $font['ctg'];
            } else {
                $file = $font['ctg'];
            }

            $size = filesize($file);

            if (!$size) {
                $this->Error('Font file not found');
            }

            $this->_out('<</Length ' . $size);

            if ('.z' == mb_substr($file, -2)) {
                $this->_out('/Filter /FlateDecode');
            }

            $this->_out('>>');

            $f = fopen($file, 'rb');

            $this->_putstream(fread($f, $size));

            fclose($f);

            $this->_out('endobj');
        }

        public function _dounderline($x, $y, $width, $txt)
        {
            //Underline text

            $up = $this->CurrentFont['up'];

            $ut = $this->CurrentFont['ut'];

            $w = $width + $this->ws * mb_substr_count($txt, ' ');

            return sprintf('%.2f %.2f %.2f %.2f re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
        }

        public function _textstring($s)
        {
            //Convert to UTF-16BE

            $s = $this->utf8_to_utf16be($s);

            //Escape necessary characters

            return '(' . strtr($s, [')' => '\\)', '(' => '\\(', '\\' => '\\\\']) . ')';
        }

        public function _escapetext($s)
        {
            //Convert to UTF-16BE

            $s = $this->utf8_to_utf16be($s, false);

            //Escape necessary characters

            return '(' . strtr($s, [')' => '\\)', '(' => '\\(', '\\' => '\\\\']) . ')';
        }

        public function _putinfo()
        {
            $this->_out('/Producer ' . $this->_textstring('UFPDF ' . UFPDF_VERSION));

            if (!empty($this->title)) {
                $this->_out('/Title ' . $this->_textstring($this->title));
            }

            if (!empty($this->subject)) {
                $this->_out('/Subject ' . $this->_textstring($this->subject));
            }

            if (!empty($this->author)) {
                $this->_out('/Author ' . $this->_textstring($this->author));
            }

            if (!empty($this->keywords)) {
                $this->_out('/Keywords ' . $this->_textstring($this->keywords));
            }

            if (!empty($this->creator)) {
                $this->_out('/Creator ' . $this->_textstring($this->creator));
            }

            $this->_out('/CreationDate ' . $this->_textstring('D:' . date('YmdHis')));
        }

        public function _putpages()
        {
            $nb = $this->page;

            if (!empty($this->AliasNbPages)) {
                $nbstr = $this->utf8_to_utf16be($nb, false);

                //Replace number of pages

                for ($n = 1; $n <= $nb; $n++) {
                    $this->pages[$n] = str_replace($this->AliasNbPages, $nbstr, $this->pages[$n]);
                }
            }

            if ('P' == $this->DefOrientation) {
                $wPt = $this->fwPt;

                $hPt = $this->fhPt;
            } else {
                $wPt = $this->fhPt;

                $hPt = $this->fwPt;
            }

            $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';

            for ($n = 1; $n <= $nb; $n++) {
                //Page

                $this->_newobj();

                $this->_out('<</Type /Page');

                $this->_out('/Parent 1 0 R');

                if (isset($this->OrientationChanges[$n])) {
                    $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $hPt, $wPt));
                }

                $this->_out('/Resources 2 0 R');

                if (isset($this->PageLinks[$n])) {
                    //Links

                    $annots = '/Annots [';

                    foreach ($this->PageLinks[$n] as $indexval => $pl) {
                        $rect = sprintf('%.2f %.2f %.2f %.2f', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);

                        $annots .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';

                        if (is_string($pl[4])) {
                            $annots .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
                        } else {
                            $l = $this->links[$pl[4]];

                            $h = isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;

                            $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>', 1 + 2 * $l[0], $h - $l[1] * $this->k);
                        }
                    }

                    $this->_out($annots . ']');
                }

                $this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');

                $this->_out('endobj');

                //Page content

                $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];

                $this->_newobj();

                $this->_out('<<' . $filter . '/Length ' . mb_strlen($p) . '>>');

                $this->_putstream($p);

                $this->_out('endobj');
            }

            //Pages root

            $this->offsets[1] = mb_strlen($this->buffer);

            $this->_out('1 0 obj');

            $this->_out('<</Type /Pages');

            $kids = '/Kids [';

            for ($i = 0; $i < $nb; $i++) {
                $kids .= (3 + 2 * $i) . ' 0 R ';
            }

            $this->_out($kids . ']');

            $this->_out('/Count ' . $nb);

            $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $wPt, $hPt));

            $this->_out('>>');

            $this->_out('endobj');
        }

        // UTF-8 to UTF-16BE conversion.

        // Correctly handles all illegal UTF-8 sequences.

        public function utf8_to_utf16be(&$txt, $bom = true)
        {
            if (!$this->embed_fonts) {
                return $txt;
            }

            $l = mb_strlen($txt);

            $txt .= ' ';

            $out = $bom ? "\xFE\xFF" : '';

            for ($i = 0; $i < $l; ++$i) {
                $c = ord($txt[$i]);

                // ASCII

                if ($c < 0x80) {
                    $out .= "\x00" . $txt[$i];
                } // Lost continuation byte

                elseif ($c < 0xC0) {
                    $out .= "\xFF\xFD";

                    continue;
                } // Multibyte sequence leading byte

                else {
                    if ($c < 0xE0) {
                        $s = 2;
                    } elseif ($c < 0xF0) {
                        $s = 3;
                    } elseif ($c < 0xF8) {
                        $s = 4;
                    } // 5/6 byte sequences not possible for Unicode.

                    else {
                        $out .= "\xFF\xFD";

                        while (ord($txt[$i + 1]) >= 0x80 && ord($txt[$i + 1]) < 0xC0) {
                            ++$i;
                        }

                        continue;
                    }

                    $q = [$c];

                    // Fetch rest of sequence

                    while (ord($txt[$i + 1]) >= 0x80 && ord($txt[$i + 1]) < 0xC0) {
                        ++$i;

                        $q[] = ord($txt[$i]);
                    }

                    // Check length

                    if (count($q) != $s) {
                        $out .= "\xFF\xFD";

                        continue;
                    }

                    switch ($s) {
                        case 2:
                            $cp = (($q[0] ^ 0xC0) << 6) | ($q[1] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x80) {
                                $out .= "\xFF\xFD";
                            } else {
                                $out .= chr($cp >> 8);

                                $out .= chr($cp & 0xFF);
                            }
                            continue 2;
                        case 3:
                            $cp = (($q[0] ^ 0xE0) << 12) | (($q[1] ^ 0x80) << 6) | ($q[2] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x800) {
                                $out .= "\xFF\xFD";
                            } // Check for UTF-8 encoded surrogates (caused by a bad UTF-8 encoder)
                            elseif ($c > 0xD800 && $c < 0xDFFF) {
                                $out .= "\xFF\xFD";
                            } else {
                                $out .= chr($cp >> 8);

                                $out .= chr($cp & 0xFF);
                            }
                            continue 2;
                        case 4:
                            $cp = (($q[0] ^ 0xF0) << 18) | (($q[1] ^ 0x80) << 12) | (($q[2] ^ 0x80) << 6) | ($q[3] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x10000) {
                                $out .= "\xFF\xFD";
                            } // Outside of the Unicode range
                            elseif ($cp >= 0x10FFFF) {
                                $out .= "\xFF\xFD";
                            } else {
                                // Use surrogates

                                $cp -= 0x10000;

                                $s1 = 0xD800 | ($cp >> 10);

                                $s2 = 0xDC00 | ($cp & 0x3FF);

                                $out .= chr($s1 >> 8);

                                $out .= chr($s1 & 0xFF);

                                $out .= chr($s2 >> 8);

                                $out .= chr($s2 & 0xFF);
                            }
                            continue 2;
                    }
                }
            }

            return $out;
        }

        // UTF-8 to codepoint array conversion.

        // Correctly handles all illegal UTF-8 sequences.

        public function utf8_to_codepoints(&$txt)
        {
            $l = mb_strlen($txt);

            $txt .= ' ';

            $out = [];

            for ($i = 0; $i < $l; ++$i) {
                $c = ord($txt[$i]);

                // ASCII

                if ($c < 0x80) {
                    $out[] = ord($txt[$i]);
                } // Lost continuation byte

                elseif ($c < 0xC0) {
                    $out[] = 0xFFFD;

                    continue;
                } // Multibyte sequence leading byte

                else {
                    if ($c < 0xE0) {
                        $s = 2;
                    } elseif ($c < 0xF0) {
                        $s = 3;
                    } elseif ($c < 0xF8) {
                        $s = 4;
                    } // 5/6 byte sequences not possible for Unicode.

                    else {
                        $out[] = 0xFFFD;

                        while (ord($txt[$i + 1]) >= 0x80 && ord($txt[$i + 1]) < 0xC0) {
                            ++$i;
                        }

                        continue;
                    }

                    $q = [$c];

                    // Fetch rest of sequence

                    while (ord($txt[$i + 1]) >= 0x80 && ord($txt[$i + 1]) < 0xC0) {
                        ++$i;

                        $q[] = ord($txt[$i]);
                    }

                    // Check length

                    if (count($q) != $s) {
                        $out[] = 0xFFFD;

                        continue;
                    }

                    switch ($s) {
                        case 2:
                            $cp = (($q[0] ^ 0xC0) << 6) | ($q[1] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x80) {
                                $out[] = 0xFFFD;
                            } else {
                                $out[] = $cp;
                            }
                            continue 2;
                        case 3:
                            $cp = (($q[0] ^ 0xE0) << 12) | (($q[1] ^ 0x80) << 6) | ($q[2] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x800) {
                                $out[] = 0xFFFD;
                            } // Check for UTF-8 encoded surrogates (caused by a bad UTF-8 encoder)
                            elseif ($c > 0xD800 && $c < 0xDFFF) {
                                $out[] = 0xFFFD;
                            } else {
                                $out[] = $cp;
                            }
                            continue 2;
                        case 4:
                            $cp = (($q[0] ^ 0xF0) << 18) | (($q[1] ^ 0x80) << 12) | (($q[2] ^ 0x80) << 6) | ($q[3] ^ 0x80);
                            // Overlong sequence
                            if ($cp < 0x10000) {
                                $out[] = 0xFFFD;
                            } // Outside of the Unicode range
                            elseif ($cp >= 0x10FFFF) {
                                $out[] = 0xFFFD;
                            } else {
                                $out[] = $cp;
                            }
                            continue 2;
                    }
                }
            }

            return $out;
        }

        //End of class
    }
}
