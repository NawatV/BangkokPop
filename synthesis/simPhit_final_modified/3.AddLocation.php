<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Location for SimPhitlok </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "3_location.xlsx";
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
//----------------- AddLocation (similar)-------------------------------------------------------
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


        $i = 0;
        $PercentLo = array();   //เก็บค่าจาก excel
        $gender = array();  //เลือก string ไว้ query หาจำนวน

        //----- Keep the data in $PercentLo[?][?], $gender[?] ------  3.1
        //เอาค่าจาก excel มาใส่ตัวแปร
        foreach ($namedDataArray as $result) {     
            $gender[$i][0] = ($result["Gender"]);
            $PercentLo[$i][1] = floatval($result["อ.เมืองพิษณุโลก"]); //in each col.
            $PercentLo[$i][2] = floatval($result["อ.นครไทย"]);
            $PercentLo[$i][3] = floatval($result["อ.ชาติตระการ"]);
            $PercentLo[$i][4] = floatval($result["อ.บางระกำ"]);
            $PercentLo[$i][5] = floatval($result["อ.บางกระทุ่ม"]);
            $PercentLo[$i][6] = floatval($result["อ.พรหมพิราม"]);
            $PercentLo[$i][7] = floatval($result["อ.วัดโบสถ์"]);
            $PercentLo[$i][8] = floatval($result["อ.วังทอง"]);
            $PercentLo[$i][9] = floatval($result["อ.เนินมะปราง"]);
            $i++;
        }

        $number = mysql_query("SELECT count(*) as number from `" . $table . "`");  //จำนวนคนทั้งหมดจาก db
        $Qnumber = mysql_fetch_assoc($number);
        //echo "number = ".$Qnumber['number']."<br>";
//    $startmale = mysql_query("SELECT MIN(ID) as startmale from `".$table."` WHERE Gender = '".$gender[0][0]."'");  //ID แรกของ  male  จาก db (startMale)
//    $startMale = mysql_fetch_assoc($startmale);
//    echo "start male = ".$startMale['startmale']."<br>";
//
//    $startfemale = mysql_query("SELECT MIN(ID) as startfemale from `".$table."` WHERE Gender = '".$gender[1][0]."'");  //ID แรกของ  female  จาก db (startFemale)
//    $startFemale = mysql_fetch_assoc($startfemale);
//    echo "start female = ".$startFemale['startfemale']."<br>";

        $Location = array();

        //---- Keep locations in $array_location[?], $gender[?] (=$headingsArray) -------
        $array_location[1] = "อ.เมืองพิษณุโลก";
        $array_location[2] = "อ.นครไทย";
        $array_location[3] = "อ.ชาติตระการ";
        $array_location[4] = "อ.บางระกำ";
        $array_location[5] = "อ.บางกระทุ่ม";
        $array_location[6] = "อ.พรหมพิราม";
        $array_location[7] = "อ.วัดโบสถ์";
        $array_location[8] = "อ.วังทอง";
        $array_location[9] = "อ.เนินมะปราง";
        $gender[0] = "male";
        $gender[1] = "female";

        //----- Cal & Keep the data in $Location[?][?]  ------  3.2
        for ($i = 0; $i <= 1; $i++) {
            //echo gettype($gender[$i][0]). " ";
            //echo  $gender[$i][0]. "<br>";

            for ($j = 1; $j <= 9; $j++) {
                //echo gettype($PercentLo[$i][$j]). " ";	
                $Location[0][$j] = round(($PercentLo[0][$j] * $Qnumber['number']) / 100);   //คำนวณค่าจำนวนคนของแต่ละอำเภอที่เป็น "ผู้ชาย" เตรียม update ลง db
                $Location[1][$j] = round(($PercentLo[1][$j] * $Qnumber['number']) / 100);  //คำนวณค่าจำนวนคนของแต่ละอำเภอที่เป็น "ผู้หญิง" เตรียม update ลง db
                //echo gettype($Location[$i][$j]);
                //echo "$i$j = ".$Location[$i][$j]. "<br>";
            }
        }

//--------------------------- Update ------------------------------------------------
        //--- Update ---
        for ($i = 0; $i <= 1; $i++) {
            for ($j = 1; $j <= 9; $j++) {
                $location_sql = "UPDATE `" . $table . "` SET `Location` = '" . $array_location[$j] . "'  WHERE `Gender` = '" . $gender[$i] . "' AND `Location` = '' Limit " . $Location[$i][$j] . "";
                mysql_query($location_sql) or die(mysql_error());
            }
            echo "Success " . $gender[$i] . " <br>";
        }
        $location_sql1 = "UPDATE `" . $table . "` SET `Location` = '" . $array_location[9] . "'  WHERE  `Location` = '' ";
        mysql_query($location_sql1) or die(mysql_error());

        mysql_close($objConnect);

        //--- About the time ---
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (int) ($time / 60 / 60);
        $minutes = (int) ($time / 60) - $hours * 60;
        $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Process Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
        ?>
    </body>
</html>