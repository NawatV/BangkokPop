<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Create Table for SimPhitlok</title>
    </head>
    <body>
        <?php

//-------------- Initiation -----------------------------------------------------        
        $time_start = microtime(true);
        $table = 'People';

        mysql_query("SET NAMES UTF8");
        mysql_query("SET character_set_results=UTF8");
        mysql_query("SET character_set_client=UTF8");
        mysql_query("SET character_set_connection=UTF8");

        $link = mysql_connect("localhost", "root", "") or die("เชื่อมต่อฐานข้อมูลไม่ได้"); //changed
        // $objDB = mysql_select_db("abc") or die("เลือกฐานข้อมูลไม่ได้");
        $create_db = "CREATE DATABASE SimPhit CHARACTER SET utf8 COLLATE utf8_general_ci;";  //สร้าง database

//----------------- Create ----------------------------------------------------------
        //----Create a DB-----
        if (mysql_query($create_db, $link)) {
            echo "Database SimPhit created successfully <br>";
        } else {
            echo 'Error creating database: ' . mysql_error() . "<br>";
        }

        $objDB = mysql_select_db('SimPhit') or die("เลือกฐานข้อมูลไม่ได้");

        //----Create a table-----
        $create_tb = "CREATE TABLE People(ID INT NOT NULL AUTO_INCREMENT,"      //สร้าง Table
                . "Gender VARCHAR(20) NOT NULL,"
                . "Location VARCHAR(20) NOT NULL,"
                . "Area VARCHAR(20) NOT NULL,"
                . "Age VARCHAR(20) NOT NULL,"
                . "BDay INT NOT NULL,"
                . "Byear VARCHAR(20) NOT NULL,"
                . "Edu_Level VARCHAR(20) NOT NULL,"
                . "SchoolCode INT NOT NULL,"
                . "Marital_Status VARCHAR(20) NOT NULL,"
                . "HH_Status VARCHAR(20) NOT NULL,"
                . "HH_ID INT NOT NULL DEFAULT 0,"
                . "SPOUSE_ID INT NOT NULL DEFAULT 0,"
                . "HH_Size INT NOT NULL DEFAULT 0,"
                . "HaveParentID INT NOT NULL DEFAULT 0,"
                . "Number_children INT NOT NULL DEFAULT 0,"
                . "UNKNOWN_CHILD INT NOT NULL DEFAULT 0,"
                . "Age_At_First_Birth INT NOT NULL DEFAULT 0,"
                . "HeadAlone INT NOT NULL DEFAULT 0,"
                . "HOUSENUM INT NOT NULL DEFAULT 0,"
                . "FirstChild INT NOT NULL DEFAULT 0,"
                . "temp INT NOT NULL DEFAULT 0,"
                . "TempYear INT NOT NULL DEFAULT 0,"                 
                . "Picked INT NOT NULL DEFAULT 0,"
                . "PRIMARY KEY (ID)) "
                . "ENGINE=MyISAM "
                . "DEFAULT CHARSET=UTF8";
        //mysql_query($create_tb , $link );
//            echo " result = $result <br>";
        if (mysql_query($create_tb, $link)) {
            //die('Could not create table: ' . mysql_error());
            echo "Table created successfully <br>";
        } else {
            die('Could not create table: ' . mysql_error());
            //echo "Table created successfully <br>";
        }

//------------ Config -----------------------------------
        $config = "ALTER TABLE "."$table"." ROW_FORMAT=FIXED";
        echo $config . "<br>";
        mysql_query($config);
        
        //Optimize a table 
        $config = "OPTIMIZE TABLE ".$table;
        echo $config . "<br>";
        mysql_query($config);
        
        mysql_close($link);

        //About the time
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $hours = (INT) ($time / 60 / 60);
        $minutes = (INT) ($time / 60) - $hours * 60;
        $seconds = (INT) $time - $hours * 60 * 60 - $minutes * 60;
        echo "Process Time: $hours hours/ $minutes minutes/ $seconds seconds. <br>";
        ?>      
    </body>
</html>

