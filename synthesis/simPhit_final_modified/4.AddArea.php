<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Area for SimPhitlok </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';

        $inputFileName = "4_area.xlsx";
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($inputFileName);

        $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);  //select sheet
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();

        $headingsArray = $objWorksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, true, true);
        $headingsArray = $headingsArray[1];

        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, true);
            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                ++$r;
                foreach ($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }
            }
        }
        ?>

        <?php
//----------------- AddArea (similar)-------------------------------------------------------
        //--- Set up -----
        $time_start = microtime(true);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        //*** Connect to MySQL Database ***//
        $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
        $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
        $table = 'People';
        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        //4.1
        $PercentOut = array();   //เก็บค่าจาก excel ของ นอกเขตเทศบาล
        $PercentIn = array();   //เก็บค่าจาก excel ของ ในเขตเทศบาล
        $gender = array();      //เลือก string ไว้ query หาจำนวน
        $PercentLo = array();  //เก็บค่าจาก excel ของ location

        //----- Keep the data in $PercentOut/In/Lo $gender ------  4.2
        $i = 0;
        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            $gender[$i][0] = $result["Gender"];
            $PercentLo[$i][1] = $result["Location"];
            $PercentOut[$i][2] = floatval($result["นอกเขตเทศบาล"]);
            $PercentIn[$i][3] = floatval($result["ในเขตเทศบาล"]);
            $i++;
        }

//    for($i=0;$i<=17;$i++){    //print value
//        echo "$i out ".$PercentOut[$i][2]." in = ".$PercentIn[$i][3]."<br>";
//    }

        $number = mysql_query("SELECT count(*) as number from `" . $table . "`");  //จำนวนคนทั้งหมดจาก db
        $Qnumber = mysql_fetch_assoc($number);
        echo "number = " . $Qnumber['number'] . "<br>";

        $Area_Out = array();
        $Area_In = array();

        //----- Cal & Keep the data in $Area_Out[?][?], $Area_In[?][?]  ------  4.3
        $i = 0;
        for ($j = 0; $j <= 1; $j++) {  //gender
            for ($k = 1; $k <= 9; $k++) {  //location
                $Area_Out[$j][$k] = round(($PercentOut[$i][2] * $Qnumber['number']) / 100);   //คำนวณค่าจำนวนคน นอกเขตเทศบาล เตรียม update ลง db
                $Area_In[$j][$k] = round(($PercentIn[$i][3] * $Qnumber['number']) / 100);  //คำนวณค่าจำนวนคน ในเขตเทศบาล เตรียม update ลง db
                // echo "i = $i ----   ";
                $i++;
                echo "$j$k  out = " . $Area_Out[$j][$k] . " in = " . $Area_In[$j][$k] . "<br>";
            }
        }

        //---- Keep locations arrays (=$headingsArray) -------
        $array_location[1] = "อ.เมืองพิษณุโลก";
        $array_location[2] = "อ.นครไทย";
        $array_location[3] = "อ.ชาติตระการ";
        $array_location[4] = "อ.บางระกำ";
        $array_location[5] = "อ.บางกระทุ่ม";
        $array_location[6] = "อ.พรหมพิราม";
        $array_location[7] = "อ.วัดโบสถ์";
        $array_location[8] = "อ.วังทอง";
        $array_location[9] = "อ.เนินมะปราง";
        $area[0] = "นอกเขตเทศบาล";
        $area[1] = "ในเขตเทศบาล";
        $gender[0] = "male";
        $gender[1] = "female";

//--------------------------------Update (similar) ------------------------------------------------
        for ($i = 0; $i <= 1; $i++) {  //gender
            for ($j = 1; $j <= 9; $j++) {           //location
                $area_out_sql = "UPDATE `" . $table . "` SET `Area` = '" . $area[0] . "' WHERE `Gender` = '" . $gender[$i] . "' AND `Location` = '" . $array_location[$j] . "' AND `Area` = '' Limit " . $Area_Out[$i][$j] . "";
                mysql_query($area_out_sql) or die(mysql_error());
                $area_in_sql = "UPDATE `" . $table . "` SET `Area` = '" . $area[1] . "' WHERE `Gender` = '" . $gender[$i] . "' AND `Location` = '" . $array_location[$j] . "' AND `Area` = '' Limit " . $Area_In[$i][$j] . "";
                mysql_query($area_in_sql) or die(mysql_error());
            }
            echo "Success " . $gender[$i] . " <br>";
        }

        mysql_close($objConnect);
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (int) ($time / 60 / 60);
        $minutes = (int) ($time / 60) - $hours * 60;
        $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Process Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
        ?>
    </body>
</html>
