<html>
    <head>
        <meta charset="UTF-8">
        <title> Update Education Level for SimPhitlok </title>
    </head>
    <body>

        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        require_once 'Classes/PHPExcel.php';

        /** PHPExcel_IOFactory - Reader */
        include 'Classes/PHPExcel/IOFactory.php';


        $inputFileName = "7_eduLevel.xlsx";
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
//----------------- AddEduLevel (similar)-------------------------------------------------------
        //-----Set up --------------------------
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
        $location = array();    //เก็บค่าจาก excel 7.1
        $gender = array();
        $edu = array();

        //------ Keep the data in $location[?][?], $gender[?][?], $edu[?][?] ------ 7.2
        foreach ($namedDataArray as $result) {     //เอาค่าจาก excel มาใส่ตัวแปร
            $location[$i][0] = $result["Location"];
            $gender[$i][1] = $result["Gender"];
            $edu[$i][2] = $result["อ.1"];
            $edu[$i][3] = $result["อ.2"];
            $edu[$i][4] = $result["ป.1"];
            $edu[$i][5] = $result["ป.2"];
            $edu[$i][6] = $result["ป.3"];
            $edu[$i][7] = $result["ป.4"];
            $edu[$i][8] = $result["ป.5"];
            $edu[$i][9] = $result["ป.6"];
            $edu[$i][10] = $result["ม.1"];
            $edu[$i][11] = $result["ม.2"];
            $edu[$i][12] = $result["ม.3"];
            $edu[$i][13] = $result["ม.4"];
            $edu[$i][14] = $result["ม.5"];
            $edu[$i][15] = $result["ม.6"];
            $i++;
        }

        //---------- Message -----------------------
        $message = array();     //message ไว้ update set
        $message[2] = "อ.1";
        $message[3] = "อ.2";
        $message[4] = "ป.1";
        $message[5] = "ป.2";
        $message[6] = "ป.3";
        $message[7] = "ป.4";
        $message[8] = "ป.5";
        $message[9] = "ป.6";
        $message[10] = "ม.1";
        $message[11] = "ม.2";
        $message[12] = "ม.3";
        $message[13] = "ม.4";
        $message[14] = "ม.5";
        $message[15] = "ม.6";
     

        $startyear = array();
        $endyear = array();
        $syear = 2005;
        $eyear = 2006;
        $startday = 136;
        $endday = 135;
        //------ Keep Year in $startyear[?], $endyear[?] ---------- 
        for ($i = 2; $i <= 15; $i++) {   //Year of getting for each grade
            $startyear[$i] = $syear;
            $endyear[$i] = $eyear;
            $syear--;
            $eyear--;
            //echo $startyear[$i]. " ".$endyear[$i]. "<br>";
        }

        $temp = 0;
        $tempDiff = 0;  //no.0
        //-----------Fill $edulevel ----------------------------------------
        for ($i = 0; $i <= 17; $i++) {       //18 อำเภอ
            for ($j = 2; $j <= 15; $j++) {   //อ.1 - ม.6
                $startday = 136;
                $endday = 135;

                if ($startyear[$j] % 4 == 0) {
                //--------- 366-day $startyear --------------------  
                    $startday = $startday + 1;
                    $edulevel = "UPDATE `" . $table . "` SET `Edu_Level` = '" . $message[$j] . "' "
                            . "WHERE ((`BDAY` >= '" . $startday . "' AND `BYEAR` = '" . $startyear[$j] . "' "
                            . "AND  Location = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "') "
                            . "OR (`BDAY` <= '" . $endday . "' AND BYEAR = '" . $endyear[$j] . "' "
                            . "AND  `Location` = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "')) "
                            . "LIMIT " . $edu[$i][$j] . "";
                    mysql_query($edulevel) or die(mysql_error());
                    //echo $edulevel . "<br>";
                    //echo $location[$i][0]." ".$gender[$i][$j]." ".$message[$j]. " start :".$startday. " " .$startyear[$j]. "end : ".$endday. " " .$endyear[$j]. " update successful <br>";

                } else if ($endyear[$j] % 4 == 0) {
                //--------- 366-day $endyear --------------------  
                    $endday = $endday + 1;
                    $edulevel = "UPDATE `" . $table . "` SET `Edu_Level` = '" . $message[$j] . "' "
                            . "WHERE ((`BDAY` >= '" . $startday . "' AND `BYEAR` = '" . $startyear[$j] . "' "
                            . "AND  `Location` = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "') "
                            . "OR (`BDAY` <= '" . $endday . "' AND `BYEAR` = '" . $endyear[$j] . "' "
                            . "AND  `Location` = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "'))  "
                            . "LIMIT " . $edu[$i][$j] . "";
                    mysql_query($edulevel) or die(mysql_error());
                    //echo $edulevel . "<br>";
                    //echo $location[$i][0]." ".$gender[$i][$j]." ".$message[$j]. " start :".$startday. " " .$startyear[$j]. "end : ".$endday. " " .$endyear[$j]. " update successful <br>";

                } else {
                //--------- OTHER CASES --------------------  
                    $edulevel = "UPDATE `" . $table . "` SET `Edu_Level` = '" . $message[$j] . "' "
                            . "WHERE ((`BDAY` >= '" . $startday . "' AND `BYEAR` = '" . $startyear[$j] . "' "
                            . "AND  `Location` = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "') "
                            . "OR (`BDAY` <= '" . $endday . "' AND `BYEAR` = '" . $endyear[$j] . "' "
                            . "AND  `Location` = '" . $location[$i][0] . "' AND `Gender` = '" . $gender[$i][1] . "'))  "
                            . "LIMIT " . $edu[$i][$j] . "";
                    mysql_query($edulevel) or die(mysql_error());
                    // echo $edulevel . "<br>";
                    //echo $location[$i][0]." ".$gender[$i][$j]." ".$message[$j]. " start :".$startday. " " .$startyear[$j]. "end : ".$endday. " " .$endyear[$j]. " update successful <br>";
                }

                //no.1- query uneducated people
                $sql = "SELECT count(ID) AS number 
                        FROM `" . $table . "` 
                        WHERE `Edu_Level` != '' "; 
                $number = mysql_query($sql);                //$number = all people in DB
                //Fetch a row from $number
                $numberTemp = mysql_fetch_assoc($number);
//????????????????????????? CONFUSED ????????????????????????????????????????????   
                $checkall = intval($numberTemp['number']); //Get int. value of a var 

                //no.2
                $temp +=$edu[$i][$j];       //Keep an amount of student in that grade  temporarily
                $diff = $temp - $checkall;

                //no.3
                if ($diff != $tempDiff && $diff != 0) {
                    $lose = $diff - $tempDiff;
                    $tempDiff = $diff;
                    if (!isset($saveLose[$gender[$i][1]][$location[$i][0]][$message[$j]])) {
                        $saveLose[$gender[$i][1]][$location[$i][0]][$message[$j]] = array($lose, $j);
                    }
                    echo $edulevel . "<br>";
                    echo " $temp  VS $checkall   J $j Lose $lose Array " . $saveLose[$gender[$i][1]][$location[$i][0]][$message[$j]][0] . "<br>";
                }
//????????????????????????????????????????????????????????????????????????????????
                //$temp +=$edu[$i][$j];
            }
        }
        echo "successRound 1 $temp <br>";
        //--------------------------------------------------------------------------------------------------------------//

//------------- Update (similar) ---------------------------------------   
        $sql_temp = "ALTER TABLE " . $table . " ADD COLUMN TempYear INT NOT NULL DEFAULT 0";
        mysql_query($sql_temp);
        
        $sql_temp1 = "UPDATE " . $table . " SET `TempYear` = IF(`Byear` != 'น้อยกว่า1909',`Byear`,0) WHERE 1";
        mysql_query($sql_temp1);

        foreach ($saveLose as $gen => $value) {
            foreach ($saveLose[$gen] as $lo => $value) {
                foreach ($saveLose[$gen][$lo] as $me => $value) {
                    $index = $saveLose[$gen][$lo][$me][1];
                    $sLose = $saveLose[$gen][$lo][$me][0];
                    $sql = "UPDATE `" . $table . "` SET `Edu_Level` = '$me' "
                    . "WHERE `Gender` = '$gen' AND `Location` = '$lo' "
                    . "AND `TempYear` BETWEEN ".($startyear[$index] - 1)." AND ".$endyear[$index]." "
                    . "AND `Edu_Level` = '' ORDER BY RAND() LIMIT $sLose ";
                    echo $sql ."<br>";
                    mysql_query($sql) or die(mysql_error());
                }
            }
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