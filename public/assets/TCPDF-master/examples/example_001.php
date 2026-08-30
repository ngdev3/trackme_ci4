<?php
//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Description : Example 001 for TCPDF class
//               Default Header and Footer
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com LTD
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Creates an example PDF TEST document using TCPDF
 * @package com.tecnick.tcpdf
 * @abstract TCPDF - Example: Default Header and Footer
 * @author Nicola Asuni
 * @since 2008-03-04
 */

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 001');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
$pdf->setFooterData(array(0,64,0), array(0,64,128));

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
$pdf->SetFont('dejavusans', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html = <<<EOD
<table border="0" cellpadding="0" cellspacing="0" style="font-family: arial; font-size:12px;">
  <tbody>
    <tr>
      <td bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;font-size:14px;vertical-align: middle;" align="center">Self awareness</td>
      <td bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" align="center">Managing emotions </td>
      <td bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" align="center">Motivating oneself </td>
      <td bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" align="center">Empathy </td>
      <td bgcolor="#72519d" style="color: #fff;padding:  10px 0px;" align="center">Social Skill </td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px; line-hight:30px;" align="center">1</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">2</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;" align="center">3</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;" align="center">4</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">5</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;" align="center">6</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">7</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">8</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">9</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">10</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">11</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">12</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">13</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">14</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">15</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;" align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">16</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">17</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">18</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">19</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">20</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">21</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">22</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">23</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">24</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">25</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">26</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">27</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">28</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">29</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">30</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">31</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">32</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">33</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">34</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">35</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">36</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">37</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">38</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">39</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">40</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">41</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">42</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">43</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">44</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">45</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">46</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">47</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">48</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">49</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">50</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">Total =(SA) </td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">Total =(ME) </td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">Total =(MO) </td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">Total = <br>(E)</td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
      <td><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">Total =(SS) </td>
              <td style="border: 1px solid #8cbdda; background-color: #fff;"  align="center">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
  </tbody>
</table>






<table border="0" cellpadding="0" cellspacing="0" style="font-family: arial;font-size:14px;">
  <tbody>
    <tr>
      <td width="10%" align="center" style="background-color: #72519d;color: #fff;padding: 10px 0px;vertical-align: middle;">35-50 </td>
      <td width="90%" style="background-color: #e9fbff;border: 0px solid #8f9fd1;padding-left: 10px;">This area is a <b><i>strength</i></b> for you.</td>
    </tr>
    <tr>
    <td width="10%" align="center" style="background-color: #f59500;color:  #fff;vertical-align: middle;">18-34 </td>
      <td width="90%" style="background-color: #e9fbff;border: 0px solid #8f9fd1;padding-left: 10px;"><b><i> Giving attention</i></b> to where you feel you are weakest will pay dividends. 			
      </td>
    </tr>
    <tr>
      <td width="10%" align="center" style="background-color: #b52f6d;color: #fff;padding: 10px 0px;">10-17 </td>
      <td width="90%" style="background-color: #e9fbff;border: 0px solid #8f9fd1;padding-left: 10px;"> Make this area a <b><i>development priority</i></b>. </td>
    </tr>
  </tbody>
</table>
</br>





<table  border="0" cellpadding="0" cellspacing="0" style="font-family: arial;font-size:14px;">
  <tbody>
    <tr>
      <td valign="middle" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" align="center" height="30"></td>
      <td valign="middle" bgcolor="#72519d" style="height:30px; color: #fff;padding:  10px 0px;border-right: 1px solid #fff;vertical-align:middle;" height="30" align="center">Strength </td>
      <td valign="middle" bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" height="30" align="center">Needs attention </td>
      <td valign="middle" bgcolor="#72519d" style="color: #fff;padding:  10px 0px;border-right: 1px solid #fff;" height="30" align="center">Development priority </td>
    </tr>
<tr valign="middle" style="height: 80px;">
  <td style="border: 1px solid #8cbdda;background-color: #00a9a7;padding: 10px 10px 10px 20px;color: #fff;"  align="left">Self awareness </td>
  <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 10px;"  align="center">&nbsp;</td>
  <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 10px;"  align="center">&nbsp;</td>
  <td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 10px;"  align="center">&nbsp;</td>      
</tr>
<tr valign="middle" style="height: 80px;">
	<td style="border: 1px solid #8cbdda;background-color: #00a9a7;padding: 10px 0px 10px 20px;color: #fff;"  align="left">Managing emotions </td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>

</tr>
<tr>
	<td style="border: 1px solid #8cbdda;background-color: #00a9a7;padding: 10px 0px 10px 20px;color: #fff;"  align="left">Motivating oneself </td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>

</tr>
<tr>
	<td style="border: 1px solid #8cbdda;background-color: #00a9a7;padding: 10px 0px 10px 20px;color: #fff;"  align="left">Empathy</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
</tr>
<tr>
	<td style="border: 1px solid #8cbdda;background-color: #00a9a7;padding: 10px 0px 10px 20px;color: #fff;"  align="left">Social Skill </td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
	<td style="border: 1px solid #8cbdda;background-color: #e9fbff;padding: 10px 0px;"  align="center">&nbsp;</td>
    </tr>
  </tbody>
</table>

<h1>Welcome to <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a>!</h1>
<i>This is the first example of TCPDF library.</i>
<p>This text is printed using the <i>writeHTMLCell()</i> method but you can also use: <i>Multicell(), writeHTML(), Write(), Cell() and Text()</i>.</p>
<p>Please check the source code documentation and other examples for further information.</p>
<p style="color:#CC0000;">TO IMPROVE AND EXPAND TCPDF I NEED YOUR SUPPORT, PLEASE <a href="http://sourceforge.net/donate/index.php?group_id=128076">MAKE A DONATION!</a></p>
EOD;


// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('example_001.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
