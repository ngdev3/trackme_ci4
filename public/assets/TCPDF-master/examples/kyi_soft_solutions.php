<?php
//============================================================+
// File name   : example_001.php
// Begin       : 2008-03-04
// Last Update : 2013-05-14
//
// Descripxion : Example 001 for TCPDF class
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

// set some language-dependent strings (opxional)
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
// This method has several opxions, check the source code documentation for more information.
$pdf->AddPage();

// set text shadow effect
$pdf->setTextShadow(array('enabled'=>true, 'depxh_w'=>0, 'depxh_h'=>0, 'color'=>array(0,0,0), 'opacity'=>0, 'blend_mode'=>'Normal'));

// Set some content to print
$html = <<<EOD

<table style="font-size:10px;">
<tbody>
	<tr>
		<td align="right">Tax Invoice</td>
	</tr>
	<tr>
		<td align="left">KYI Soft Solutions Pvt Ltd</td>
	</tr>
	<tr>
		<td>
			<table>
				<tbody>
					<tr>
						<td colspan="5">
							<table>
								<tbody>
									<tr><td>B-58, 2nd Floor, Sector-64, Noida,<br>(U.P.) 201301</td></tr>
									<tr><td><strong>contact:</strong> +91 9650539090</td></tr>
									<tr><td><strong>Telephone: </strong> 0120-4268187</td></tr>
									<tr><td><strong>Email:</strong>accounts@kyisolutions.com </td></tr>
									<tr><td><strong>Website:</strong> www.kyisolutions.com</td></tr>
									<tr><td><strong>GSTIN: 09AAGCK4564L1Z0</strong></td></tr>
									<tr><td><strong>PAN NO.: AAGCK4564L</strong></td></tr>
								</tbody>
							</table>
						</td>
						<td colspan="5">
							<table>
								<tbody>
									<tr>
										<td style="border-top:1px solid #000;border-left:1px solid #000;"><strong> Invoice Number </strong></td>
										<td style="border-top:1px solid #000;border-right:1px solid #000;" align="right"> KYI/SOFT/2017-18/0123 </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"><strong> Invoice Date </strong></td>
										<td style="border-right:1px solid #000;" align="right">3/16/2018</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;" bgcolor="#808080" style="color:#fff;"> Invoice To: </td>
										<td style="border-right:1px solid #000;" bgcolor="#808080" style="color:#fff;" align="right">&nbsp;</td>
									</tr>
									
									<tr>
										<td style="border-left:1px solid #000;"><strong> Company Name </strong> </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> Address </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;">&nbsp; </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"><strong> GSTIN </strong></td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> State : </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;border-bottom:1px solid #000"><strong> State Code : </strong></td>
										<td style="border-right:1px solid #000;border-bottom:1px solid #000" align="right">&nbsp;</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					

					<tr>
						<td border="1" width="32" height="30"> S. No </td>
						<td border="1" width="150"> Particulars </td>
						<td border="1" width="80"> HSN/SAC Code </td>
						<td border="1" width="50"> UOM </td>
						<td border="1" width="120"> Rate </td>
						<td border="1" width="191"> Amount </td>
					</tr>
					<tr>
						<td border="1" height="25"> 1 </td>
						<td border="1"> SMS Campaigning </td>
						<td border="1"> 998361 </td>
						<td border="1"> 100000 </td>
						<td border="1"> Rs. 0.10 </td>
						<td border="1"> Rs. 10,000.00 </td>
					</tr>
					<tr>
						<td border="1" height="25"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
					</tr>
					<tr>
						<td border="1" height="25"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
					</tr>
					<tr>
						<td border="1" height="25"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
					</tr>
					<tr>
						<td border="1" height="25"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
						<td border="1"> &nbsp; </td>
					</tr>
					
					<tr>
						<td colspan="4">
							<table>
								<tbody>
									<tr>
										<td colspan="3">  </td>
									</tr>
									<tr>
										<td colspan="2">
											<table>
												<tbody>
													<tr>
														<td border="1" bgcolor="#808080" height="20" style="color:#fff;">
															Amount in Words :
														</td>
													</tr><tr>
														<td border="1">
															Rupees Eleven Thousand Eight Hundred Only
														</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
									<tr>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td colspan="2">
											<table>
												<tbody>
													<tr>
														<td border="1" bgcolor="#808080" height="20" style="color:#fff;">
															Banking Details
														</td>
													</tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;">	Bank: </td></tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;" border="">	Account Type:&nbsp;&nbsp; Current Account </td></tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;" border="">	Account Number: </td></tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;border-bottom:1px solid #000;">	IFSC Code: </td></tr>
												</tbody>
											</table>
										</td>
									</tr>
									
									<tr> <td> &nbsp;</td> </tr>
									
									<tr>
										<td colspan="2">
											<table>
												<tbody>
													<tr>
														<td border="1" bgcolor="#808080" height="20" style="color:#fff;">
															T & C
														</td>
													</tr><tr>
														<td border="1">
															All disputes are subject to GB Nagar Court Jurisdiction.
														</td>
													</tr>
												</tbody>
											</table>
										</td>
									</tr>
									<tr> <td> &nbsp; </td></tr>
									<tr>
										<td colspan="2">
											<table>
												<tbody>
													<tr>
														<td border="1" bgcolor="#808080" height="20" style="color:#fff;">
															Invoice Status
														</td>
													</tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;"> Invoice Status: </td></tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;" border="">	Invoice Due Date: </td></tr>
													<tr><td style="border-left:1px solid #000; border-right:1px solid #000;border-bottom:1px solid #000;">	Payment Date: </td></tr>
												</tbody>
											</table>
										</td>
									</tr>
									
								</tbody>
							</table>
						</td>
						<td colspan="2">
							<table>
								<tbody>
									<tr>
										<td style="border-top:1px solid #000;border-left:1px solid #000;"> Subtotal </td>
										<td style="border-top:1px solid #000;border-right:1px solid #000;" align="right"> Rs.10,000.00 </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> Discount </td>
										<td style="border-right:1px solid #000;" align="right"> Rs. 0.00</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> Total Amount Before GST </td>
										<td style="border-right:1px solid #000;" align="right"> Rs.10,000.00 </td>
									</tr>
									
									<tr>
										<td style="border-left:1px solid #000;"> CGST @ 0 %  </td>
										<td style="border-right:1px solid #000;" align="right"> Rs.0.00</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> SGST @ 0 % </td>
										<td style="border-right:1px solid #000;" align="right"> Rs. 0.00 </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> IGST @ 18 % </td>
										<td style="border-right:1px solid #000;" align="right"> Rs.1,800.00 </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> GST Payble on Reverse Charge </td>
										<td style="border-right:1px solid #000;" align="right"> &nbsp; </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> &nbsp; </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> Total Amount after GST </td>
										<td style="border-right:1px solid #000;" align="right"> Rs.11,800.00 </td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;"> &nbsp; </td>
										<td style="border-right:1px solid #000;" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td style="border-left:1px solid #000;border-bottom:1px solid #000"> Rounded Off Value </td>
										<td style="border-right:1px solid #000;border-bottom:1px solid #000" align="right"> Rs.11,800.00 </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td> &nbsp; </td>
										<td> &nbsp; </td>
									</tr>
									<tr>
										<td colspan="2"><center> For KYI Soft Solutions Pvt Ltd </center></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					
					
				</tbody>
			</table>
		</td>
	</tr>
</tbody>
</table>

EOD;


// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 0, 0, true, '', true);

// ---------------------------------------------------------

// Close and output PDF document
// This method has several opxions, check the source code documentation for more information.
$pdf->Output('example_001.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
