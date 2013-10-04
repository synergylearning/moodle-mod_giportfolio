<?php
// This file is part of giportfolio module for Moodle - http://moodle.org/
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
 * giportfolio export pdf
 *
 * @package    giportfoliotool
 * @subpackage print
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module and TCPDF library
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
global $CFG, $DB, $USER, $PAGE, $SITE;
require_once($CFG->libdir.'/tcpdf/tcpdf.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$chapterid = optional_param('chapterid', 0, PARAM_INT); // Chapter ID.

// Security checks START - teachers and students view.

$cm = get_coursemodule_from_id('giportfolio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$giportfolio = $DB->get_record('giportfolio', array('id' => $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/giportfolio:view', $context);
require_capability('giportfoliotool/print:print', $context);

// Check all variables.
if ($chapterid) {
    // Single chapter printing - only visible!
    $chapter = $DB->get_record('giportfolio_chapters', array('id' => $chapterid, 'giportfolioid' => $giportfolio->id),
                               '*', MUST_EXIST);
} else {
    // Complete giportfolio.
    $chapter = false;
}

$PAGE->set_url('/mod/giportfolio/pdfgiportfolio.php', array('id' => $id, 'chapterid' => $chapterid));

unset($id);
unset($chapterid);

// Security checks END.

// Read chapters.
$chapters = giportfolio_preload_chapters($giportfolio);

// SYNERGY - add fake user chapters.
$additionalchapters = giportfolio_preload_userchapters($giportfolio, $userid = null);
if ($additionalchapters) {
    $chapters = $chapters + $additionalchapters;
}
// SYNERGY.

$strgiportfolios = get_string('modulenameplural', 'mod_giportfolio');
$strgiportfolio = get_string('modulename', 'mod_giportfolio');
$strtop = get_string('top', 'mod_giportfolio');

class PORTFOLIOPDF extends TCPDF {

    // Page header.
    public function Header() {
        $style = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 64, 128));
        $this->Line(10, 7, 200, 7, $style);

        // Set font.
        $this->SetFont('helvetica', 'B', 20);
    }

    // Page footer.
    public function Footer() {
        $style = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 64, 128));
        // Position at 15 mm from bottom.
        $this->SetY(-15);
        $this->Line(10, 280, 200, 280, $style);
        // Set font.
        $this->SetFont('helvetica', 'I', 8);
        // Page number.
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$stylev = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0));

$allchapters = $DB->get_records('giportfolio_chapters', array('giportfolioid' => $giportfolio->id), 'pagenum');

$alluserchapters = $DB->get_records('giportfolio_userchapters', array('giportfolioid' => $giportfolio->id, 'iduser' => $USER->id),
                                    'pagenum');
if ($alluserchapters) {
    $allchapters = $alluserchapters + $allchapters;
}

// Create new PDF document.
$pdf = new PORTFOLIOPDF($orientation = 'P', PDF_UNIT, $format = 'A4', true, 'UTF-8', false);

// If there is a file called 'glogo.jpg' in the pix/ subfolder, it will be added to the page.
// If there is a file called 'pdfklassenbuch_details.php' in this folder, it will be loaded to find the
// 'author' name for the PDF ($pdfauthorname) and the URL to link the logo to ($pdflogolink).
$pdfauthorname = $SITE->fullname;
$pdflogolink = '';
$pdfdetailsfile = $CFG->dirroot.'/mod/giportfolio/tool/print/pdfgiportfolio_details.php';
if (file_exists($pdfdetailsfile)) {
    require_once($pdfdetailsfile);
}

// Set document information.
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($pdfauthorname);
$pdf->SetTitle('Export Quiz Report');
$pdf->SetSubject('Export Quiz Report');
$pdf->SetKeywords('Export Quiz Reporte');

// Set default header data.
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);

// Set header and footer fonts.
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font.
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins.
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks.
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// Set image scale factor.
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set default font subsetting mode.
$pdf->setFontSubsetting(true);
$pdf->SetFont('helvetica', 'B', 20);

// Add a page.
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// Set cell padding.
$pdf->setCellPaddings(1, 1, 1, 1);

// Set cell margins.
$pdf->setCellMargins(1, 1, 1, 1);

$pdf->SetFillColor(256, 256, 256);

if (file_exists($CFG->dirroot.'/mod/giportfolio/tool/print/pix/glogo.jpg')) {
    $pdf->Image('pix/glogo.jpg', 170, 10, 30, 15, 'JPG', $pdflogolink, '', true, 170, '', false, false,
                0, false, false, false);
}
$pdf->MultiCell(155, 4, $giportfolio->name, 0, 'C', 1, 0, '', '', true);
$pdf->Ln(20);
$pdf->SetFont('helvetica', '', 8);

$intro = file_rewrite_pluginfile_urls($giportfolio->intro, 'pluginfile.php', $context->id, 'mod_giportfolio', 'intro', '');

$fs = get_file_storage();
preg_match_all('/<img[^>]+>/i', $intro, $result);

$images = array(); // Will stores the images links.
$file = array(); // Will store files array.
$wfile = array(); // Will store file width.
$hfile = array(); // Will store file height.

for ($img = 0; $img < count($result[0]); $img++) {
    // Get images links inside html content.
    if (preg_match('%<img\s.*?src=".*?/?([^/]+?(\.gif|\.png|\.jpg|\.jpeg))"%s', $result[0][$img], $regs)) {
        preg_match_all('/(width|height)=("[^"]*")/i', $result[0][$img], $dimensiuni); // Get width and height for the resized image.
        $image = $regs[1]; // Get the name of the image.
        $fullpath = "/$context->id/mod_giportfolio/intro/$image";
        array_push($file, $fs->get_file_by_hash(sha1($fullpath)));
        array_push($images, $result[0][$img]);
        array_push($wfile, $dimensiuni[2][0]);
        array_push($hfile, $dimensiuni[2][1]);
    } else {
        $image = "";
    }
}

$htmlentity = giportfolio_explode_x($images, $intro);
$lungime = count($htmlentity);
for ($mo = 0; $mo < $lungime; $mo++) {
    $lo = 0;
    $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $htmlentity[$mo], $border = 0, $ln = 1, $fill = 0, $reseth = true,
                        $align = '', $autopadding = true);
    if ($mo != $lungime - 1 && $file[$mo]) {
        $heighinit = 0;
        $heighimage = 0;
        $wfile = str_replace('"', '', $wfile[$lo]);
        $hfile = str_replace('"', '', $hfile[$lo]);
        $hmm = ($wfile * 25.4) / 72;
        $imgdata = $file[$mo]->get_content();
        $heighinit = $pdf->GetY();
        $pdf->Image('@'.$imgdata, $x = '', $y = '');
        $heighimage = $pdf->getImageRBY();
        $pdf->Ln($heighimage - $heighinit);
    }
    $lo++;
}

$pdf->Ln(5);
$site = '<a href="'.$CFG->wwwroot.'">'.format_string($SITE->fullname, true, array('context' => $context)).'</a>';
$pdf->MultiCell(35, 4, get_string('site'), 0, 'L', 0, 0, '', '', true);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $site, $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);
$pdf->MultiCell(35, 4, get_string('course'), 0, 'L', 0, 0, '', '', true);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $course->fullname, $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);
$pdf->MultiCell(35, 4, get_string('modulename', 'mod_giportfolio'), 0, 'L', 0, 0, '', '', true);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $giportfolio->name, $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);
$pdf->MultiCell(35, 4, get_string('printedby', 'giportfoliotool_print'), 0, 'L', 0, 0, '', '', true);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', fullname($USER, true), $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);
$pdf->MultiCell(35, 4, get_string('printdate', 'giportfoliotool_print'), 0, 'L', 0, 0, '', '', true);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', userdate(time()), $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);
$pdf->Ln(5);

list($toc, $titles) = giportfoliotool_print_get_toc($chapters, $giportfolio, $cm);
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $toc, $border = 0, $ln = 1, $fill = 0, $reseth = true,
                    $align = '', $autopadding = true);

$link1 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id.'&chapterid=';
$link2 = $CFG->wwwroot.'/mod/giportfolio/viewgiportfolio.php?id='.$course->id;

foreach ($chapters as $ch) {
    $chapter = $allchapters[$ch->id];
    if ($chapter->hidden) {
        continue;
    }
    $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', '<div class="giportfolio_chapter"><a name="ch'.$ch->id.'"></a>',
                        $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
    if (!$giportfolio->customtitles) {
        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', '<p class="giportfolio_chapter_title">'.$titles[$ch->id].'</p>',
                            $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
    }
    $content = str_replace($link1, '#ch', $chapter->content);
    $content = str_replace($link2, '#top', $content);

    // Add suport for admin images in content.
    $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, 'mod_giportfolio', 'chapter', $ch->id);

    $fs = get_file_storage();
    preg_match_all('/<img[^>]+>/i', $content, $result);

    $images = array(); // Will stores the images links.
    $file = array(); // Will store files array.
    $wfile = array(); // Will store file width.
    $hfile = array(); // Will store file height.

    for ($img = 0; $img < count($result[0]); $img++) {
        // Get images links inside html content.
        if (preg_match('%<img\s.*?src=".*?/?([^/]+?(\.gif|\.png|\.jpg|\.jpeg))"%s', $result[0][$img], $regs)) {
            // Get width and height for the resized image.
            preg_match_all('/(width|height)=("[^"]*")/i', $result[0][$img], $dimensiuni);
            $image = $regs[1]; // Get the name of the image.
            $fullpath = "/$context->id/mod_giportfolio/chapter/$ch->id/$image";
            array_push($file, $fs->get_file_by_hash(sha1($fullpath)));
            array_push($images, $result[0][$img]);
            array_push($wfile, $dimensiuni[2][0]);
            array_push($hfile, $dimensiuni[2][1]);
        } else {
            $image = "";
        }
    }

    $htmlentity = giportfolio_explode_x($images, $content);
    $lungime = count($htmlentity);
    for ($mo = 0; $mo < $lungime; $mo++) {
        $lo = 0;
        $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $htmlentity[$mo], $border = 0, $ln = 1, $fill = 0, $reseth = true,
                            $align = '', $autopadding = true);
        if ($mo != $lungime - 1 && $file[$mo]) {
            $heighinit = 0;
            $heighimage = 0;
            $wfile = str_replace('"', '', $wfile[$lo]);
            $hfile = str_replace('"', '', $hfile[$lo]);
            $hmm = ($wfile * 25.4) / 72;
            $imgdata = $file[$mo]->get_content();
            $heighinit = $pdf->GetY();
            $pdf->Image('@'.$imgdata, $x = '', $y = '');
            $heighimage = $pdf->getImageRBY();
            $pdf->Ln($heighimage - $heighinit);
        }
        $lo++;
    }

    $contriblist = giportfolio_get_user_contributions($chapter->id, $chapter->giportfolioid, $USER->id);
    if ($contriblist) {
        foreach ($contriblist as $contrib) {
            $contribtitle = file_rewrite_pluginfile_urls($contrib->title, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                         'chapter', $contrib->chapterid);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', '<strong>'.$contribtitle.'</strong></br>', $border = 0, $ln = 1,
                                $fill = 0, $reseth = true, $align = '', $autopadding = true);
            $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', date('l jS F Y', $contrib->timemodified), $border = 0, $ln = 1,
                                $fill = 0, $reseth = true, $align = '', $autopadding = true);
            $contribtext = file_rewrite_pluginfile_urls($contrib->content, 'pluginfile.php', $context->id, 'mod_giportfolio',
                                                        'contribution', $contrib->id);

            $fs = get_file_storage();

            preg_match_all('/<img[^>]+>/i', $contribtext, $result);
            unset($images);
            unset($file);

            $images = array(); // Will stores the images links.
            $file = array(); // Will store files array.
            $wfile = array(); // Will store file width.
            $hfile = array(); // Will store file height.

            for ($img = 0; $img < count($result[0]); $img++) {
                // Get images links inside html content.
                if (preg_match('%<img\s.*?src=".*?/?([^/]+?(\.gif|\.png|\.jpg|\.jpeg))"%s', $result[0][$img], $regs)) {
                    // Get width and height for the resized image.
                    preg_match_all('/(width|height)=("[^"]*")/i', $result[0][$img], $dimensiuni);
                    $image = $regs[1]; // Get the name of the image.
                    $fullpath = "/$context->id/mod_giportfolio/contribution/$contrib->id/$image";
                    array_push($file, $fs->get_file_by_hash(sha1($fullpath)));
                    array_push($images, $result[0][$img]);
                    array_push($wfile, $dimensiuni[2][0]);
                    array_push($hfile, $dimensiuni[2][1]);
                } else {
                    $image = "";
                }
            }

            $htmlentity = giportfolio_explode_x($images, $contribtext);
            $lungime = count($htmlentity);
            for ($mo = 0; $mo < $lungime; $mo++) {
                $lo = 0;
                $pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $htmlentity[$mo], $border = 0, $ln = 1, $fill = 0,
                                    $reseth = true, $align = '', $autopadding = true);
                if ($mo != $lungime - 1 && $file[$mo]) {
                    $heighinit = 0;
                    $heighimage = 0;
                    $wfile = str_replace('"', '', $wfile[$lo]);
                    $hfile = str_replace('"', '', $hfile[$lo]);
                    $hmm = ($wfile * 25.4) / 72;
                    $imgdata = $file[$mo]->get_content();
                    $heighinit = $pdf->GetY();
                    $pdf->Image('@'.$imgdata, $x = '', $y = '');
                    $heighimage = $pdf->getImageRBY();
                    $pdf->Ln($heighimage - $heighinit);
                }
                $lo++;
                $pdf->Ln(2);
            }
        }
    }
}

$pdf->Output('pdfgiportfolio.pdf'.$giportfolio->name, 'I');
