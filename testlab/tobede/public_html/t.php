<?php

$fname = $_POST['fname'];
if ($fname == "") {
    $fname = $_GET['fname'];
}

$pdf_content = file_get_contents("/site/silvaco/lib/smartspice/4.31.15.C/docs/smartspice_notes.pdf");

echo base64_encode($pdf_content);
?>
