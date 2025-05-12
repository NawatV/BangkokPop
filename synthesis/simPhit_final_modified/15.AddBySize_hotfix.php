<html>
    <head>
        <meta charset="UTF-8">
        <title> Update People By Size for SimPhitlok </title>
    </head>
    <body>
        <?php
//-------------- Initiation (same)-----------------------------------------------------
        /** PHPExcel */
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        try {
            //error_reporting(error_reporting() & ~E_NOTICE);
            $time_start = microtime(true);
            $objConnect = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed from user, 1234
            $objDB = mysql_select_db("SimPhit") or die("เลือกฐานข้อมูลไม่ได้");
            $table = "People";
            mysql_query("SET NAMES UTF8");
            mysql_query("SET character_set_results=UTF8");
            mysql_query("SET character_set_client=UTF8");
            mysql_query("SET character_set_connection=UTF8");


            $str = "SELECT ID , HOUSENUM , HaveParentID ,HH_Status "
                    . "FROM `" . $table . "` "
                    . "ORDER BY HOUSENUM";
            echo $str . "<br>";
            $tempHavePID = array();
            $markChild = array();
            $markParent = array();

            $result = mysql_query($str) or die(mysql_error());
            while ($saveAll = mysql_fetch_object($result)) {
                if ($saveAll->HH_Status == 'Head' || $saveAll->HH_Status == 'Spouse') {
                    if (!isset($markParent[$saveAll->HOUSENUM])) {
                        $markParent[$saveAll->HOUSENUM][0] = $saveAll->ID;
                    } else {
                        array_push($markParent[$saveAll->HOUSENUM], $saveAll->ID);
                    }
                } else if ($saveAll->HH_Status == 'Child') {
                    if (!isset($markChild[$saveAll->HOUSENUM])) {
                        $markChild[$saveAll->HOUSENUM][0] = $saveAll->ID;
                    } else {
                        array_push($markChild[$saveAll->HOUSENUM], $saveAll->ID);
                    }

                    $tempHPID[$saveAll->ID] = array("HNUM" => $saveAll->HOUSENUM, "HPID" => $saveAll->HaveParentID);
                }
            }
            mysql_free_result($result);

            foreach ($tempHPID as $idChild => $objChild) {
                $checkOther = false;
                foreach ($markParent[$objChild["HNUM"]] as $numP => $idParent) {
                    if ($idParent == $objChild["HPID"]) {
                        $checkOther = true;
                    }
                }

                if ($checkOther) {
                    $sql_Update = "INSERT INTO `" . $table . "` (ID , HH_Status) "
                            . "VALUES ( " . $idChild . " , 'Other' ) "
                            . "ON DUPLICATE KEY UPDATE HH_Status = VALUES (HH_Status) ";
                    echo " $sql_Update<br>";
                    mysql_query($sql_Update);
                }
            }

//------------------- Update (similar) ------------------------------------------
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $hours = (int) ($time / 60 / 60);
            $minutes = (int) ($time / 60) - $hours * 60;
            $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
            echo "<br>Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";


            mysql_close($objConnect);
        } catch (Exception $ex) {
            echo 'exception: ', $ex->getMessage(), "<br>";
            mysql_close($objConnect);
        }
        ?>
    </body>
</html>