<?php

@$json = @$_POST['excel']; /*接收到的是JSON字符串*/

if(empty($json)){
    echo 0;
    return;
}

$data = json_decode($json,true);

$total =  count($data);

require_once 'PHPExcel.php';
require_once 'phpExcel/Writer/Excel2007.php';
require_once 'phpExcel/Writer/Excel5.php';
include_once 'phpExcel/IOFactory.php';

$objExcel = new PHPExcel();
//设置属性 (这段代码无关紧要，其中的内容可以替换为你需要的)
$objExcel->getProperties()->setCreator("beneware");
$objExcel->getProperties()->setLastModifiedBy("Jerry");
$objExcel->getProperties()->setTitle("Beneware BPNP");
$objExcel->setActiveSheetIndex(0);

$i=0;



foreach($data as $k=>$v) {
    $row = $i+1;
    /*----------写入内容-------------*/
    $objExcel->getActiveSheet()->setCellValue('a'.$row, $v["DataID"]);
    $objExcel->getActiveSheet()->setCellValue('b'.$row, $v["PatientID"]);
    $objExcel->getActiveSheet()->setCellValue('c'.$row, $v["PatientName"]);
    $objExcel->getActiveSheet()->setCellValue('d'.$row, $v["PatientGenderDesc"]);
    $objExcel->getActiveSheet()->setCellValue('e'.$row, $v["DataClinic"]);
    $objExcel->getActiveSheet()->setCellValue('f'.$row, $v["DataInfo"]);
    $objExcel->getActiveSheet()->setCellValue('g'.$row, $v["DataType"]);
    $objExcel->getActiveSheet()->setCellValue('h'.$row, $v["DGSUserName"]);
    $objExcel->getActiveSheet()->setCellValue('i'.$row, $v["DGSResult"]);
    $objExcel->getActiveSheet()->setCellValue('j'.$row, $v["SubmitTime"]);
    $objExcel->getActiveSheet()->setCellValue('k'.$row, $v["Flag"]);
    $objExcel->getActiveSheet()->setCellValue('l'.$row, $v["DiagnosingTime"]);
    $objExcel->getActiveSheet()->setCellValue('m'.$row, $v["StatusDesc"]);
    $i++;
}

// 高置列的宽度
$objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
$objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
$objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
$objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
$objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
$objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(10);

$objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
$objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

// 设置页方向和规模
$objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
$objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
$objExcel->setActiveSheetIndex(0);

$timestamp = date("Ymd-His");

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="bpnp'.$timestamp.'.xls"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
$objWriter->save("create/bpnp".$timestamp.".xls");
echo 'data/create/bpnp'.$timestamp.'.xls';
exit;