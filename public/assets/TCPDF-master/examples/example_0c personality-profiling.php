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

<style>

	.box_pad{padding:40px 40px; background-color:#d5272b;}
	.box_pad2{padding:40px 40px; background-color:#f19181;}
	
	.box_pad_green{padding:40px 40px; background-color:#40ae49;}
	
	.box_pad_green_light{padding:40px 40px; background-color:#b8da8f;}
	
	
	.box_pad_blue{padding:40px 40px; background-color:#275d8a;}
	.box_pad_blue_light{padding:40px 40px; background-color:#92d6df; color:#333}
	
	.box_pad_yellow{padding:40px 40px; background-color:#fdb72c;}
	.box_pad_yellow_light{padding:40px 40px; background-color:#f2f090;}
	
	
	
	
	
	.pad_left_10{padding-left:40px;}
</style>




<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-family: arial;font-size:14px;">
  <tbody>
    <tr>
      <td width="20%" style="color: #fff;padding:  10px 0px; align=">&nbsp;</td>
      <td width="20%" style="color: #d5272b;padding:  10px 0px;border-right: 1px solid #fff;" align="center">Doer (Pragmatic)</td>
      <td width="20%" style="color: #fdb72c;padding:  10px 0px;" align="center">Actor (Player)</td>
      <td width="20%" style="color: #54b15c;padding:  10px 0px;" align="center">Friend (Companion) </td>
      <td width="20%" style="color: #275d8a;padding:  10px 0px;" align="center">Thinker (Analyst)</td>
    </tr>
    <tr>
      <td align="center"><table width="100%" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">100</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">95</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">90</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">85</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">80</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">75</td>
            </tr>
			<tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">70</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">65</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">60</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">55</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">50</td>
            </tr>
			<tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">45</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">40</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">35</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">30</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">25</td>
            </tr>
			<tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">20</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">15</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">10</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">5</td>
            </tr>
            <tr>
              <td align="center" height="30px" style="color: #404041;padding: 10px 0px;">0</td>
            </tr>
          </tbody>
        </table>
	  </td>
      <td colspan="4" style="border: 1px solid #d1d2d4;background-color: #fff;padding: 10px 0px;" align="center"><br><br>
	  	<img src="barchart.jpg" alt="" />
	  </td>
    </tr>
   
   
   
	<tr>
		<td colspan="5"> 
			<table width="100%" align="right" cellpadding="0" cellspacing	="0" style="font-size:12px;">
			  <tbody>
			  	<tr>
					<td>
						&nbsp;
					</td>
				</tr>
				<tr vlaign="middle">
				 	<td width="450">
					
						My personality style is that of a
						
					</td>
					<td width="20">&nbsp;</td>
					<td width="150" align="left" bgcolor="#e7e7e8" height="30px" class="pad_left_10">
						df
					</td>
				</tr>
				<tr>
					<td>
						&nbsp;
					</td>
				</tr>
				<tr>
				 	<td width="450">
					
						Other style(s) (if any) that I am to operate in some of the time is/are
						
					</td>
					<td width="20">&nbsp;</td>
					<td width="150" align="left" bgcolor="#e7e7e8" height="30px">
						df
					</td>
				</tr>
			  </tbody>
			</table>
		</td>
	</tr>
	
  </tbody>
</table>


</br>









<br>
<br>
<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#000;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="100px" class="">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="" width="20px">&nbsp;</td>
	  <td height="20px" class="" style="font-size:16px;"> 
			Body Language
      </td>
    </tr>
  </tbody>
</table>
<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#000;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class=""> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Contralled and/or limited</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Whaen communicating, tends to learn forward, 
		  face the listener squarely and maintain constant eye contact</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Hand motions are limited topointing finger and other gestures
		  of domiance</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px; color:#fff;"> May show imptience by tapping finger, pencil etc.</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px; color:#fff;"> &nbsp; </li>
		</ul> 
      </td>
    </tr>
  </tbody>
</table>




<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#000;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="100px" class="">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="" width="20px">&nbsp;</td>
	  <td height="20px" class="" style="font-size:16px;"> 
			Voice
      </td>
    </tr>
  </tbody>
</table>
<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#000;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class=""> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Caries in tone, raised to emphasise points; relatively fast paced</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Strong and/or authoritative</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px; color:#fff;"> &nbsp; </li>
		</ul> 
      </td>
    </tr>
  </tbody>
</table>
<br>
<br>





</br>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 20px;font-family: arial;">
	<tbody>
		<tr>
			<td height="=200px"> &nbsp; </td>
		</tr>
	</tbody>
</table>
<br>






<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>





<br>
<br>


<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="10px" class="box_pad">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="box_pad" width="20px">&nbsp;</td>
	  <td height="20px" class="box_pad" style="font-size:16px;"> 
			Other Doers will see you as:
      </td>
    </tr>
  </tbody>
</table>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>





<br>
<br>
<br>




<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#231f20;padding: 90px;font-family: arial;font-size:12px;">
  <tbody>
  				
    <tr style="padding:100px;">
      <td colspan="2" class="box_pad2" height="10px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	&nbsp;
      </td>
    </tr>
	<tr style="padding:100px;">
      <td colspan="2" class="box_pad2" height="30px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	Your impact on Friends
      </td>
    </tr>
	
	
	<tr style="padding:100px;">
     <td class="box_pad2" height="20px"> 
		These are the Doer (your) qualities that a 
	  </td>
      <td class="box_pad2" height="20px">
      	These are the Doer (your) qualities that a 
      </td>
    </tr>
	
	<tr style="padding:100px;">
     <td class="box_pad2" height="20px"> 
		Thinker will relate to positively:
	  </td>
      <td class="box_pad2" height="20px">
      	Thinker will question or be adverse to: 
      </td>
    </tr>
	<tr>
	  <td class="box_pad2">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task – orientated </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Disciplined</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	  <td class="box_pad2">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Decisive</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;"> Risk taking (action orientated)  </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Impatient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Haste (in a hurry)</li>
		  <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	</tr>
	
  </tbody>
</table>




<br>
<br>
<br>
<br><br>





<br>
<br>


<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_green"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad_green"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad_green">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>

<br>
<br>
<br>
<br>



<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="10px" class="box_pad_green">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="box_pad_green" width="20px">&nbsp;</td>
	  <td height="20px" class="box_pad_green" style="font-size:16px;"> 
			Other Doers will see you as:
      </td>
    </tr>
  </tbody>
</table>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_green"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad_green"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad_green">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
<br>
<br>
<br>
<br>
<br>
<br>





<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#231f20;padding: 90px;font-family: arial;font-size:12px;">
  <tbody>
    <tr style="padding:100px;">
      <td colspan="2" class="box_pad_green_light" height="10px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	&nbsp;
      </td>
    </tr>
	<tr style="padding:100px;">
      <td colspan="2" class="box_pad_green_light" height="30px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	Your impact on Actors
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_green_light" height="20px"> 
		These are the Doer (your) qualities that a 
	  </td>
      <td class="box_pad_green_light" height="20px">
      	These are the Doer (your) qualities that a 
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_green_light" height="20px"> 
		Thinker will relate to positively:
	  </td>
      <td class="box_pad_green_light" height="20px">
      	Thinker will question or be adverse to: 
      </td>
    </tr>
	<tr>
	  <td class="box_pad_green_light">
      	<ul style="padding: 0px 0px;margin: 0px;padding-left:0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dgdfgdfg gffg</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">fgsdfs – sfdsd </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs df</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs fs</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	  <td class="box_pad_green_light">
      	<ul style="padding: 0px 25px;margin: 0px; padding-left:0px; margin:0px;">
          <li style="padding-left:0px; height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsf </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;"> Risk taking (action orientated)  </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf gsa</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf sfsf (in a hurry)</li>
		  <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsdf dsfsdf</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	</tr>
  </tbody>
</table>

<br>
<br>
<br>
<br>
<br>
<br>
<br>



<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_blue"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad_blue"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad_blue">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>

<br>
<br>
<br>
<br>



<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="10px" class="box_pad_blue">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="box_pad_blue" width="20px">&nbsp;</td>
	  <td height="20px" class="box_pad_blue" style="font-size:16px;"> 
			Other Doers will see you as:
      </td>
    </tr>
  </tbody>
</table>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_blue"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  &nbsp;<br>
		</ul> 
      </td>
	  <td class="box_pad_blue"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  &nbsp;<br>

		</ul> 
      </td>
      <td class="box_pad_blue">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
		  &nbsp;<br>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
<br>
<br>
<br>
<br>
<br>
<br>






<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#231f20;padding: 90px;font-family: arial;font-size:12px;">
  <tbody>
    <tr style="padding:100px;">
      <td colspan="2" class="box_pad_blue_light" height="10px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	&nbsp;
      </td>
    </tr>
	<tr style="padding:100px;">
      <td colspan="2" class="box_pad_blue_light" height="30px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	Your impact on Actors
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_blue_light" height="20px"> 
		These are the Doer (your) qualities that a 
	  </td>
      <td class="box_pad_blue_light" height="20px">
      	These are the Doer (your) qualities that a 
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_blue_light" height="20px"> 
		Thinker will relate to positively:
	  </td>
      <td class="box_pad_blue_light" height="20px">
      	Thinker will question or be adverse to: 
      </td>
    </tr>
	<tr>
	  <td class="box_pad_blue_light">
      	<ul style="padding: 0px 0px;margin: 0px;padding-left:0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dgdfgdfg gffg</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">fgsdfs – sfdsd </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs df</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs fs</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	  <td class="box_pad_blue_light">
      	<ul style="padding: 0px 25px;margin: 0px; padding-left:0px; margin:0px;">
          <li style="padding-left:0px; height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsf </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;"> Risk taking (action orientated)  </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf gsa</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf sfsf (in a hurry)</li>
		  <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsdf dsfsdf</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	</tr>
  </tbody>
</table>
<br>
<br>
<br>
<br>
<br>
<br>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_yellow"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
	  <td class="box_pad_yellow"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;"> &nbsp; </li>
		</ul> 
      </td>
      <td class="box_pad_yellow">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
        </ul>
      </td>
    </tr>
  </tbody>
</table>

<br>
<br>
<br>
<br>



<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
  	<tr>
		<td height="10px" class="box_pad_yellow">&nbsp;</td>
	</tr>
    <tr style="padding:100px;">
	  <td class="box_pad_yellow" width="20px">&nbsp;</td>
	  <td height="20px" class="box_pad_yellow" style="font-size:16px;"> 
			Other Doers will see you as:
      </td>
    </tr>
  </tbody>
</table>

<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#fff;padding: 90px;font-family: arial;font-size:10px;">
  <tbody>
    <tr style="padding:100px;">
	  <td class="box_pad_yellow"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  &nbsp;<br>
		</ul> 
      </td>
	  <td class="box_pad_yellow"> 
		<ul style="padding: 0px 25px;margin: 0px;">
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Action-oriented</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 13px;text-shadow:none;">Tough-minded </li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">In a hurry</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Dislikes small talk</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Short attention span</li>
		  <li style=" line-height:30px; padding: 5px 0px;font-size: 12px;text-shadow:none;">Commanding</li>
		  &nbsp;<br>

		</ul> 
      </td>
      <td class="box_pad_yellow">
      	<ul style="padding: 0px 25px;margin: 0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Efficient</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Competitive </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Demanding</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Task-oriented</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Logical</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">Takes risks based on facts</li>
		  &nbsp;<br>
        </ul>
      </td>
    </tr>
  </tbody>
</table>
<br>
<br>
<br>
<br>
<br>






<table border="0" width="100%" cellpadding="0" cellspacing="0" style="color:#231f20;padding: 90px;font-family: arial;font-size:12px;">
  <tbody>
    <tr style="padding:100px;">
      <td colspan="2" class="box_pad_yellow_light" height="10px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	&nbsp;
      </td>
    </tr>
	<tr style="padding:100px;">
      <td colspan="2" class="box_pad_yellow_light" height="30px" style="font-size:15px; padding-top:10px; margin-top:40px;">
      	Your impact on Actors
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_yellow_light" height="20px"> 
		These are the Doer (your) qualities that a 
	  </td>
      <td class="box_pad_yellow_light" height="20px">
      	These are the Doer (your) qualities that a 
      </td>
    </tr>
	<tr style="padding:100px;">
     <td class="box_pad_yellow_light" height="20px"> 
		Thinker will relate to positively:
	  </td>
      <td class="box_pad_yellow_light" height="20px">
      	Thinker will question or be adverse to: 
      </td>
    </tr>
	<tr>
	  <td class="box_pad_yellow_light">
      	<ul style="padding: 0px 0px;margin: 0px;padding-left:0px;">
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dgdfgdfg gffg</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">fgsdfs – sfdsd </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs df</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfs fs</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
	  <td class="box_pad_yellow_light">
      	<ul style="padding: 0px 25px;margin: 0px; padding-left:0px; margin:0px;">
          <li style="padding-left:0px; height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsf </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;"> Risk taking (action orientated)  </li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf gsa</li>
          <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">dfsf sfsf (in a hurry)</li>
		  <li style="height:50px; line-height:30px; padding: 5px 0px;font-size: 12px;">sdfsdf dsfsdf</li>
		  <li style="height:50px; line-height:10px; padding: 5px 0px;font-size: 12px;">&nbsp;</li>
        </ul>
      </td>
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
