<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library files to help with PDF generation
 *
 * @package   giportfoliotool_print
 * @copyright 2014 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function pdfgiportfolio_fix_image_links($html) {
    global $CFG;

    $html = pdfgiportfolio_fix_svg_images($html);

    $baseurl = new moodle_url('/pluginfile.php');
    $baseurl = preg_quote($baseurl->out());
    $regex = "|<img[^>]*src=\"({$baseurl}([^\"]*))|";
    if (preg_match_all($regex, $html, $matches)) {
        $fs = get_file_storage();
        foreach ($matches[2] as $params) {
            if (substr($params, 0, 1) == '?') {
                $pos = strpos($params, 'file=');
                $params = substr($params, $pos + 5);
            } else {
                if (($pos = strpos($params, '?')) !== false) {
                    $params = substr($params, 0, $pos - 1);
                }
            }
            $params = urldecode($params);
            $params = explode('/', $params);
            array_shift($params); // Remove empty first param.
            $contextid = (int)array_shift($params);
            $component = clean_param(array_shift($params), PARAM_COMPONENT);
            $filearea  = clean_param(array_shift($params), PARAM_AREA);
            $itemid = array_shift($params);

            if (empty($params)) {
                $filename = $itemid;
                $itemid = 0;
            } else {
                $filename = array_pop($params);
            }

            if (empty($params)) {
                $filepath = '/';
            } else {
                $filepath = '/'.implode('/', $params).'/';
            }

            if (!$file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename)) {
                if ($itemid) {
                    $filepath = '/'.$itemid.$filepath; // See if there was no itemid in the original URL.
                    $itemid = 0;
                    $file = $fs->get_file($contextid, $component, $filename, $itemid, $filepath, $filename);
                }
            }

            if (!$file) {
                $content = file_get_contents($CFG->dirroot.'/pix/spacer.gif');
            } else {
                $content = $file->get_content();
            }
            $content = '@'.base64_encode($content);
            $html = str_replace($matches[1], $content, $html);
        }
    }

    return $html;
}

function pdfgiportfolio_fix_svg_images($html) {
    $baseurl = new moodle_url('/theme/image.php');
    $baseurl = preg_quote($baseurl->out());
    $html = preg_replace_callback("|({$baseurl})([^\"']*)|", function($matches) {
        global $CFG;
        if (substr($matches[2], 0, 1) == '?') {
            // Not using slash arguments.
            $sep = '&';
            if (strpos($matches[2], '&amp;') !== false) {
                $sep = '&amp;';
            }

            // See if the file can be rewritten as a direct link to the file.
            $parts = explode($sep, $matches[2]);
            $params = array();
            foreach ($parts as $part) {
                $keyvalue = explode('=', $part, 2);
                if (count($keyvalue) < 2) {
                    continue;
                }
                $params[$keyvalue[0]] = $keyvalue[1];
            }
            if (isset($params['component']) && $params['component'] == 'core') {
                if (isset($params['image'])) {
                    $filepath = urldecode($params['image']);
                    $filepath = $CFG->dirroot.'/pix/'.$filepath;
                    foreach (array('.gif', '.png') as $ext) {
                        if (file_exists($filepath.$ext)) {
                            return '@'.base64_encode(file_get_contents($filepath.$ext));
                        }
                    }
                }
            }

            // Rewrite the non-slash arguments URL.
            if (strpos($matches[2], 'svg=0') !== false) {
                return $matches[0]; // svg=0 already set => nothing to change.
            }
            return $matches[1].$matches[2].$sep.'svg=0'; // Add 'svg=0' to parameters
        }

        // Slash arguments.

        // See if the file can be rewritten as a direct link to the file.
        $parts = explode('/', $matches[2]);
        if ($parts[2] == 'core') {
            $parts = array_slice($parts, 4); // Remove 'theme', 'core' and 'iteration' params
            $filepath = implode('/', $parts);
            $filepath = $CFG->dirroot.'/pix/'.$filepath;
            foreach (array('.gif', '.png') as $ext) {
                if (file_exists($filepath.$ext)) {
                    return '@'.base64_encode(file_get_contents($filepath.$ext));
                }
            }
        }

        if (substr($matches[1], 4) == '/_s/') {
            return $matches[0]; // /_s/ prefix already set => nothing to change.
        }
        return $matches[1].'/_s'.$matches[2]; // Add /_s/ prefix to the start of the path.
    }, $html);
    return $html;
}