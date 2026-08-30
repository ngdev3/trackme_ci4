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
$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0, 'depth_h'=>0, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

// Set some content to print
$html = <<<EOD

<table width="100%" cellpadding="0" cellspacing="0" style="font-family: arial;font-size:12px;">
	<tbody>
		<tr>
				<td width="100">
					Name :Gerard Clayson <br>
					Date : 24.11.17
				</td>
				<td width="450" style="background-color:#f2f2f2;"> 
					<table>
						<tbody>
							<tr>
								<td width="80%" align="center"> &nbsp;</td>
								<td width="20%" align="center"  style="border:1px solid #ccc;"> 
									Only enter value 1-5 in grey cells below
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td width="100"> &nbsp; </td>
		</tr>
		<tr>
				<td>&nbsp;</td>
				<td>
					<table>
						<tbody>
							<tr>
								<td width="80%" align="center"> &nbsp;</td>
								<td width="20%" height="20px" align="center"> 
									Score
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td> &nbsp; </td>
		</tr>
		<tr>
			<td> &nbsp; </td>
			<td>
					<table>
						<tbody>
							<tr>
								<td width="15%" align="center"> &nbsp;</td>
								<td width="75%" align="center" bgcolor="#d9d9d9"> <b>Sales Meeting Scores </b></td>
								<td width="10%" align="center" bgcolor="#d9d9d9" style="border:1px solid #a6a6a6;"> 
									Value
								</td>
							</tr>
						</tbody>
					</table>
			</td>
			<td> &nbsp; </td>
		</tr>
		
		<tr>
				<td bgcolor="#f2f2f2" style="border:1px solid #d9d9d9;" align="center"> Prospect </td>
				<td>
						<table border="0" cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Prospects
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Prospects
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									1
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Products & Services
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Products & Services
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									2
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Diary
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Appointment Slots
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									3
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Pricing
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Is clear in relation to pricing options
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									4
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Multi-Tasking
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Is capable of concentrating when Multi-Tasking
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									5
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Preparation
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Has clearly prepared and there is no sign of shuffling or stuttering
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									6
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td> <img src="graph.png" /> </td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		
		
		<tr>
				<td bgcolor="#f2f2f2" style="border:1px solid #d9d9d9;" align="center"> Prospect </td>
				<td>
						<table border="0" cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Prospects
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Prospects
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									1
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Products & Services
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Products & Services
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									2
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Diary
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Quickly accesses information relating to Appointment Slots
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									3
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Pricing
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Is clear in relation to pricing options
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									4
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Multi-Tasking
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Is capable of concentrating when Multi-Tasking
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									5
								</td>
							</tr>
							<tr>
								<td width="15%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
										Preparation
								</td>
								<td width="75%" align="center" style="font-size:10px; border:1px solid #a6a6a6;">
											Has clearly prepared and there is no sign of shuffling or stuttering
								</td>
								<td width="10%" align="center" bgcolor="#f2f2f2" style="border:1px solid #a6a6a6;"> 
									6
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td> <img src="graph.png" /> </td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		
		
		
		
	</tbody>
</table>

<table width="50%" cellpadding="0"border="1" cellspacing="0" style="font-family: arial;font-size:12px;">
	<tbody>
		<tr>
			<td height="20px" width="60%" style="border:1px solid #d9d9d9; color:#ff0000" align="center">Any Red</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ff0000" align="center">=</td>
			<td height="20px" width="40%" style="border:1px solid #d9d9d9; color:#ff0000" align="center">RED</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ff0000" align="center">5 Ambers</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ff0000" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ff0000" align="center">RED</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">4 Ambers 1 Green </td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">AMBER</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">3 Ambers 2 Green</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">AMBER</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">3 Green 2 Amber</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#ffc000" align="center">AMBER</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">4 Green 1 Amber</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">GREEN</td>
		</tr>
		<tr>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">5 Green</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">=</td>
			<td height="20px" style="border:1px solid #d9d9d9; color:#00b057" align="center">GREEN</td>
		</tr>
	</tbody>
</table>
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
