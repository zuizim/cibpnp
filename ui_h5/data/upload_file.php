<?php

header("Content-Type:application/json;charset=utf-8");
error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('PRC');

$output = array('msg'=>'','tip'=>'');

function insert_into_table($data){
    include 'config-using.php';

    global $output;
    //var_dump($data);
    //输入send_record
    $sort_no = $data[3]['B'];
    $send_date = date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($data[3]['C']));
    $send_qty = $data[3]['E'];
    $to_company = $data[3]['F'];
    $bill_no = $data[3]['G'];
    $feedback = "";
    $sql = "insert into send_record (sort_no,send_date,send_qty,to_company,bill_no) values('$sort_no','$send_date','$send_qty','$to_company','$bill_no')";
    $result = mysqli_query($conn,$sql);
    if($result){
        //echo "successfully uploaded data to send_record in rows # ".mysqli_insert_id($conn);
        $output = array('msg'=>1,'tip'=>'发货记录添加成功');
    }else{
        //echo "failed to insert data into send_record";
        $output = array('msg'=>-1,'tip'=>'发货记录添加失败');
        echo json_encode($output);
        exit(0);
    }

    //输入product_info
    $rows = count($data);

    $sort_no = $data[3]['B'];
    $spec = $data[3]['D'];
    $values = "";
    for($i=3;$i<=$rows;$i++){
        $sn_no = $data[$i]['A'];
        if($i!=$rows) {
            $values = $values . " ('$sn_no' ,'$sort_no','$spec'),";
        }else{
            $values = $values . " ('$sn_no' ,'$sort_no','$spec');";
        }
    }
    $sql = "insert into product_info (sn_no,sort_no,spec) values".$values;
    $result = mysqli_query($conn,$sql);

    if($result){
        $output = array('msg'=>2,'tip'=>'SN信息添加成功');
    }else{
        //echo "failed to insert data into product_info";
        $output = array('msg'=>-2,'tip'=>'SN信息添加失败');
        echo json_encode($output);
        exit(0);
    }
    return $output;
}

if ( !empty( $_FILES ) ) {

    $tempPath = $_FILES[ 'file' ][ 'tmp_name' ];
    $_FILES['file']['newname']=date('Ymd',time()).'o'.substr(microtime(true),6,-5).'-'.$_FILES['file']['name'];

    $uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $_FILES[ 'file' ][ 'newname' ];
    move_uploaded_file( $tempPath, $uploadPath );

        /** Include path **/
        set_include_path(get_include_path() . PATH_SEPARATOR . '../');

        /** PHPExcel_IOFactory */
        include 'PHPExcel/IOFactory.php';


        $inputFileType = 'Excel5';
        //	$inputFileType = 'Excel2007';
        //	$inputFileType = 'Excel2003XML';
        //	$inputFileType = 'OOCalc';
        //	$inputFileType = 'Gnumeric';
        $inputFileName = $uploadPath;

        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);

        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);


        if(sizeof($sheetData)-1){ //将数据写入数据库
            if(insert_into_table($sheetData)>0) {
                $output = array('msg'=>3,'tip' => '文件上传成功');
            }else{
                $output = array('msg'=>-3,'tip' => '文件上传失败');
            }
            // print_r( $sheetData);
        }else{
            $output = array('msg'=>-4,'tip' => '在文件中没有找到正确的excel表格，请检查');
        }
        $output = json_encode( $output );
        echo $output;

} else {
    $output = array('msg'=>-5,'tip' => '没有发现文件，请检查');
    $output = json_encode($output);
    echo $output;
}

?>