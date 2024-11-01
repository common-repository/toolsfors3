<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2023 www.BillMinozzi.com
 * @ Modified time: 2023-02-05
 * */
if (!defined("ABSPATH")) {
    die('We\'re sorry, but you can not directly access this file.');
}



error_reporting(E_ALL);
ini_set("display_errors", 1);




//ini_set('max_execution_time', 15);
set_time_limit(180); //3600
ini_set("memory_limit", "128M"); // 800M); // 512M');

if (!function_exists("toolsfors3_getHumanReadableSize")) {
    function toolsfors3_getHumanReadableSize($bytes)
    {
        if ($bytes > 0) {
            $base = floor(log($bytes) / log(1024));
            $units = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"]; //units of measurement
            return number_format($bytes / pow(1024, floor($base)), 3) .
                " $units[$base]";
        } else {
            return "0 bytes";
        }
    }
}

if (!function_exists("toolsfors3_record_debug")) {
    function toolsfors3_record_debug($text)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "toolsfors3_copy";
        if (!toolsfors3_tablexist($table_name)) {
            return;
        }

        $txt = PHP_EOL . date("Y-m-d H:i:s") . " " . PHP_EOL;
        $txt .= __("Memory Usage Now:", "toolsfors3");
        $txt .= function_exists("memory_get_usage")
            ? toolsfors3_getHumanReadableSize(round(memory_get_usage(), 0))
            : 0;
        $txt .= PHP_EOL;
        $txt .= __("Memory Peak Usage:", "toolsfors3") . " ";
        $txt .= toolsfors3_getHumanReadableSize(memory_get_peak_usage());
        $txt .= PHP_EOL . $text . PHP_EOL;
        $txt .= "------------------------------";

        $query = "select debug from $table_name ORDER BY id DESC limit 1";
        $debug = $wpdb->get_var($query);
        $content = $debug . $txt;
        $r = $wpdb->query(
            $wpdb->prepare("UPDATE  `$table_name` SET debug = %s", $content)
        );
    }
}



global $folder_server;
global $folder_cloud;
global $server_cloud;
global $bucket_name;
global $toolsfors3_copy_speed;
if (isset($_POST["radValue"])) {
    $toolsfors3_copy_speed = sanitize_text_field($_POST["radValue"]);
} else {
    $toolsfors3_copy_speed = "normal!!";
}
$toolsfors3_time_limit = 90; // 120;
ini_set("max_execution_time", $toolsfors3_time_limit);
set_time_limit($toolsfors3_time_limit); //3600
if (isset($_POST["server_cloud"])) {
    $server_cloud = sanitize_text_field($_POST["server_cloud"]);
} else {
    die("Missing Post server_cloud");
}
if (isset($_POST["folder_server"])) {
    $folder_server = sanitize_text_field($_POST["folder_server"]);
} else {
    die("Missing Post folder_server");
}
if ($folder_server == "Root") {
    $folder_server = substr(ABSPATH, 0, strlen(ABSPATH) - 1);
}
if (isset($_POST["folder_cloud"])) {
    $folder_cloud = sanitize_text_field($_POST["folder_cloud"]);
} else {
    die("Missing Post folder_cloud");
}
if (isset($_POST["bucket_name"])) {
    $bucket_name = sanitize_text_field($_POST["bucket_name"]);
} else {
    die("Missing Post bucket_name");
}
if (
    !isset($_POST["nonce"]) ||
    !wp_verify_nonce(sanitize_text_field($_POST["nonce"]), "toolsfors3_copy")
) {
    //////////////////////////////////////die("Nonce Fail");
}



toolsfors3_ajax_copy();
function toolsfors3_ajax_copy()
{
    global $bill_debug;
    global $toolsfors3_copy_speed;
    global $wpdb;
    global $cond;
    global $toolsfors3_files_array;
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $toolsfors3_s3;
    global $toolsfors3_config;

    $bill_debug = false;
    $st = toolsfors3_get_copy_status();
    if ($st == null or $st == "end") {
        toolsfors3_create_db_copy_files();

        toolsfors3_copy_inic();

        if (defined("WP_MEMORY_LIMIT")) {
            toolsfors3_record_debug("WordPress Memory Limit: " . WP_MEMORY_LIMIT);
        }
        toolsfors3_record_debug("starting");
        // toolsfors3_record_log("starting");
        $table_name = $wpdb->prefix . "toolsfors3_copy";
        if (!toolsfors3_tablexist($table_name)) {
            die("end");
        }
        $query = "update " . $table_name . " SET mystatus = 'counting'";
        $r = $wpdb->query($query);
        die("Counting Files...");
    }
    $st = toolsfors3_get_copy_status();
    if ($st == "counting") {
        // toolsfors3_record_log("counting files files to copy");
        toolsfors3_record_debug("counting files to copy");

        $r = toolsfors3_fetch_files($folder_server);

        if ($server_cloud == "server") {
            // die('fserver: '.$folder_server);
            $r = toolsfors3_fetch_files($folder_server);
        } else {
            $r = toolsfors3_scan_cloud($folder_cloud);
        }

        if (!is_array($r)) {
            if ($server_cloud == "server") {
                $text =
                    "toolsfors3 could not read the contents of your base WordPress directory. This usually indicates your permissions are so strict that your web server can\'t read your WordPress directory.";
            } else {
                $text =
                    "toolsfors3 could not read the contents of your base cloud directory.";
            }
            toolsfors3_record_debug($text);
            die(
                "Fail to read, please, look the Scan Log tab. Click Cancel Button."
            );
        }

        $qfiles = (string) count($r);

        $table_name = $wpdb->prefix . "toolsfors3_copy";
        $query =
            "update " .
            $table_name .
            " SET mystatus = 'loading', qfiles = '" .
            $qfiles .
            "'";
        $r = $wpdb->query($query);
        $txt = "Number of Files Found to Transfer: " . $qfiles;
        toolsfors3_record_debug($txt);
        // toolsfors3_record_log($txt);
        // toolsfors3_record_log("loading files to transferr to table");
        toolsfors3_record_debug("loading files to transfer to table");
        die("Loading files to copy...");
    }

    ////////////////////// COUNTING ///////////////////////////////

    $st = toolsfors3_get_copy_status();
    if ($st == "loading") {
        global $wpdb;
        global $bill_debug;
        $toolsfors3_quant_files = toolsfors3_get_qfiles(); // total q files found
        $files_db = toolsfors3_get_files_from_db(); // total...
        if ($bill_debug) {
            $toolsfors3_quant_files = 2000;
        }
        if ($toolsfors3_quant_files > count($files_db)) {
            if ($server_cloud == "server") {
                $toolsfors3_files_array = toolsfors3_fetch_files($folder_server);
            } else {
                $toolsfors3_files_array = toolsfors3_scan_cloud($folder_cloud);
            }

            $tomake = $toolsfors3_quant_files;
            if ($toolsfors3_copy_speed == "very_slow") {
                $maxtomake = 75;
            } elseif ($toolsfors3_copy_speed == "slow") {
                $maxtomake = 150;
            } elseif ($toolsfors3_copy_speed == "fast") {
                $maxtomake = 450;
            } elseif ($toolsfors3_copy_speed == "very_fast") {
                $maxtomake = 600;
            } else {
                $maxtomake = 300;
            }

            // Find pointer...
            $table_name = $wpdb->prefix . "toolsfors3_copy";
            $query = "select pointer from $table_name ORDER BY id DESC limit 1";

            $pointer = $wpdb->get_var($query);
            $ctd = 0;
            for ($i = $pointer; $i < $tomake; $i++) {
                if (!isset($toolsfors3_files_array[$i])) {
                    die("not def i : " . var_export($toolsfors3_files_array));
                }

                $name = base64_encode(trim($toolsfors3_files_array[$i]));
                if (in_array($name, $files_db)) {
                    continue;
                }
                $table_name = $wpdb->prefix . "toolsfors3_copy_files";
                $r = $wpdb->get_var(
                    $wpdb->prepare(
                        "
          SELECT name FROM `$table_name` WHERE name = %s LIMIT 1",
                        $name
                    )
                );
                if (!empty($r) or empty($name)) {
                    continue;
                }

                if ($ctd > $maxtomake) {
                    break;
                }

                $ctd++;

                $query = "insert IGNORE into `$table_name` (`name`, `splited`) VALUES ('$name', '0')";
                $msg =
                    "Added to list (table) to transfer: " .
                    base64_decode(trim($name));
                ///// <<<<  toolsfors3_record_debug($msg);

                $r = $wpdb->get_var($query);
            } // end Loop

            if ($toolsfors3_quant_files - count($files_db) < $maxtomake) {
                $table_name = $wpdb->prefix . "toolsfors3_copy";
                $query =
                    "update " . $table_name . " SET mystatus = 'transferring'";
                $r = $wpdb->query($query);
                // toolsfors3_record_log("transferring");
                toolsfors3_record_debug("transferring");
                die("Transferring files...");
            }
            $files_db = toolsfors3_get_files_from_db();
            $done = round((count($files_db) / $toolsfors3_quant_files) * 100);
            if ($done > 99) {
                $done = 100;
            }
            // Update pointer...
            $table_name = $wpdb->prefix . "toolsfors3_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
         SET pointer = %s",
                    $i
                )
            );
        } else {
            $table_name = $wpdb->prefix . "toolsfors3_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
         SET mystatus = %s",
                    "transferring"
                )
            );
            // toolsfors3_record_log("transferring");
            toolsfors3_record_debug("transferring");
            die("Transferring files - 0%");
        }
        die("Loading name of files to table - " . $done . "%");
    }
    ////////////////////// TRANSFERRING ///////////////////////////////
    $st = toolsfors3_get_copy_status();
    // transferring
    if (substr($st, 0, 12) == "transferring") {
        if ($toolsfors3_copy_speed == "very_slow") {
            $maxtransfer = 100;
        } elseif ($toolsfors3_copy_speed == "slow") {
            $maxtransfer = 300;
        } elseif ($toolsfors3_copy_speed == "fast") {
            $maxtransfer = 500;
        } elseif ($toolsfors3_copy_speed == "very_fast") {
            $maxtransfer = 700;
        } else {
            $maxtransfer = 400;
        } // era 500
        // >>>>>>>>>>>>>> have multipart to complete? <<<<<<<<<<<<<<<<<<<
        if($server_cloud == 'server') {
            $files_splited_to_join = toolsfors3_get_files_to_join();
            if (count($files_splited_to_join) > 0) {
                toolsfors3_complete_multipart($files_splited_to_join);
            }
            $files_splited_to_copy = toolsfors3_get_files_to_copy_splited();
            if (count($files_splited_to_copy) > 0) {
                toolsfors3_transfer_splited($files_splited_to_copy);
            }
        }
        // >>>>>>>>>>>>>> end have multipart to complete? <<<<<<<<<<<<<<<<<<<
        $files_to_copy = toolsfors3_get_files_to_copy($maxtransfer);
        $tomake = count($files_to_copy);
        $qfiles_to_copy = count($files_to_copy);

       // die(var_export($files_to_copy ));

        // end transfer?
        if ($qfiles_to_copy == 0) {
            // exit only here
            toolsfors3_record_debug("Joining Files...");
            $table_name = $wpdb->prefix . "toolsfors3_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
            SET mystatus = %s",
                    "joining"
                )
            );
            // toolsfors3_record_log('End of Job');
            die("Joining Files...");
        }


        $toolsfors3_time_limit = time() + 60;

        for ($i = 0; $i < $qfiles_to_copy; $i++) {
            $name_file = $files_to_copy[$i]["name"];
            $id = $files_to_copy[$i]["id"];
            $upload_id = $files_to_copy[$i]["upload_id"];
            $splited = $files_to_copy[$i]["splited"];
            $part_number = $files_to_copy[$i]["part_number"];

            // >>>>>>>>>>>>>>>>>>>>>>  From Cloud

            $r = toolsfors3_make_transfer(
                $name_file,
                $id,
                $upload_id,
                $splited,
                $part_number
            );

            if ($r == "10") {
                // Just splited large file...
                die("Transferring large file: " . $name_file);
            }

            if ($r == "-2") {
                // big file....
                if (toolsfors3_flag_file($files_to_copy[$i]["id"]) === false) {
                    $txt = "Fail Flag file (too  big): " . $name_file;
                    toolsfors3_record_debug($txt);
                    die("Fail to transfer. File too big: " . $name_file);
                }

                $txt = "***** Fail Copy file (too  big): " . $name_file;
                toolsfors3_record_debug($txt);
                die("Fail to transfer. File too big: " . $name_file);
            } elseif ($r == "-1") {
                // update....
                // if (toolsfors3_flag_file($id_to_copy[$i]["id"]) === false) {
                if (toolsfors3_flag_file($id) === false) {
                    $txt = "Fail Copy file: " . $name_file;
                    toolsfors3_record_debug($txt);
                    die("Fail to flag file: " . $name_file . "   id: " . $id);
                }
            } else {
                if ($r == "1") {
                    toolsfors3_flag_file($id, "");

                    $txt = "Transferred file: " . $name_file;
                    /////  <<<<<<<  toolsfors3_record_debug($txt);

                    if (strrpos($name_file, ".toolsfors3part")) {
                        die($txt);
                    }
                }
                if (time() > $toolsfors3_time_limit) {
                    $done = toolsfors3_get_files_done();
                    $todo = toolsfors3_get_total_db_files();

                    if ($todo == 0 or $done == 0) {
                        die("Transferring Files...");
                    }

                    $res = ($done / $todo) * 100;
                    $done = round($res, 0);
                    die("Transferred: " . $done . "%");
                    // die('Transferred: '.$name_file);
                }
            }
        } // end loop

        die("Transferring Files...");
    } // if (substr($st, 0, 12) == 'transferring')

    ////////////////////// JOINING ///////////////////////////////

    $st = toolsfors3_get_copy_status();

    if (substr($st, 0, 7) == "joining") {
        $r = toolsfors3_join();

        if ($r == "1") {
            $table_name = $wpdb->prefix . "toolsfors3_copy";
            $r = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  `$table_name`
            SET mystatus = %s",
                    "end"
                )
            );

            toolsfors3_record_debug("End of Job");
            // toolsfors3_record_log("End of Job");
            die("End of Job!");
        }

        die("Joining  Files!");
    }
} // end main function

///////// END   ////////////////////////////////////////////////////

function toolsfors3_make_transfer(
    $toolsfors3_name_file,
    $id_to_copy,
    $upload_id,
    $splited,
    $part_number
) {
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $toolsfors3_time_limit;
    global $wpdb;
    global $toolsfors3_s3;
    $toolsfors3_time_limit = time() + 60;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once $path;
    if (empty($toolsfors3_name_file)) {
        return "-1";
    }

    //  ------------  PREPAR server to cloud

    if ($server_cloud == "server") {
        $pos = strrpos($toolsfors3_name_file, "/");
        $file_name_base = substr($toolsfors3_name_file, $pos + 1);

        // /home/toolsfors3/public_html/lixo/teste2/scan_log.php
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $toolsfors3key = str_replace($capar, "", $toolsfors3_name_file);
        } else {
            //die($folder_cloud);
            $toolsfors3key =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $toolsfors3_name_file);
            $toolsfors3key = str_replace("//", "/", $toolsfors3key);
        }

        // --------------- MAKE COPY.... server to Cloud

        try {
            if (!file_exists($toolsfors3_name_file)) {
                // >>>>>>>>>>>>>>>>>>>>>>>>>>>>> toolsfors3_flag_file($id_to_copy[$i]["id"]);
                $msg = "File doesn't exist  " . $toolsfors3_name_file;
                toolsfors3_record_debug($msg);
                return "-1";
            }
            // bigger than  3 000 000 3 mega
            if (
                filesize($toolsfors3_name_file) > 3000000 and
                time() + 30 > $toolsfors3_time_limit
            ) {
                $done = toolsfors3_get_files_done();
                $todo = toolsfors3_get_total_db_files();
                $res = ($done / $todo) * 100;
                $done = round($res, 0);
                die("Transferred::: " . $done . "%");
            }
            if (filesize($toolsfors3_name_file) > 1200000000) {
                // 165
                return "-2";
            }

            // Limit size....

        } catch (Exception $exception) {
            $msg =
                "Failed to Read Files From Server with error: " .
                $exception->getMessage();
            toolsfors3_record_debug($msg);
            return "-1";
        }

        /* ============  Split Large files Server =============== */
        // split large
        if (filesize($toolsfors3_name_file) > 100000000) {
            // Split files
            $toolsfors3_f_split = toolsfors3_split_server_file(
                $toolsfors3_name_file,
                $id_to_copy
            );
            $table_name = $wpdb->prefix . "toolsfors3_copy_files";

            // INSERT CREATED SPLITTED ON TABLE
            for ($q = 0; $q < count($toolsfors3_f_split); $q++) {
                $name = $toolsfors3_f_split[$q];
                if (empty($name)) {
                    continue;
                }

                $wname = base64_encode(trim($name));

                $wq = $q + 1;

                $wupload_id = base64_encode($upload_id);

                $query = "insert IGNORE into `$table_name` (`name`, `splited` , `upload_id` , `part_number`) VALUES ('$wname', '1', '$wupload_id', '$wq')";

                $msg = "splited: " . $query;
                // toolsfors3_record_debug($msg);
                $r = $wpdb->get_results($query);


            } // end loop insert on table

            // done...

            if ($q > 0) {
                $query = "update `$table_name` set `flag` = '1' WHERE id = '$id_to_copy' LIMIT 1";
                $msg = "splited: " . $query;
                //toolsfors3_record_debug($msg);
                $r = $wpdb->get_results($query);
                // error_log($query);
                // die($query);
            }

            return "10";
            // } // END filesize($name_file) > 105000000
        } else {
            // No split ...

            if($splited == '1')
              return '1';

            try {
                $r = $toolsfors3_s3->putObject([
                    "Bucket" => $bucket_name,
                    "Key" => $toolsfors3key,
                    "SourceFile" => $toolsfors3_name_file,
                ]);

                if ($r !== false) {
                    $objInfo = $toolsfors3_s3->doesObjectExist(
                        $bucket_name,
                        $toolsfors3key
                    );
                    if (!$objInfo) {
                        $msg = "Failed to tranfer:  " . $toolsfors3key;
                        toolsfors3_record_debug($msg);
                        die("FAIL TO TRANSFER SERVER TO CLOUD");
                    }
                    return "1";
                } else {
                    $msg = "Failed to tranfer:  " . $toolsfors3key;
                    toolsfors3_record_debug($msg);
                    die("FAIL TO TRANSFER SERVER TO CLOUD");
                    return "-1";
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to transfer: $file_name_base , with error: " .
                    $exception->getMessage();
                toolsfors3_record_debug($msg);
                //error_log($msg);
                // die("Failed to transfer ". $file_name_base);  // with error: " . $exception->getMessage();
                return "-1";
            }
        }
        // END MAKE COPY SERVER TO CLOUD
    } else {


        //  ///////////////////  transfer Cloud To Server //////////////////////


        $toolsfors3key = $toolsfors3_name_file;
        $pos = strrpos($toolsfors3_name_file, "/");
        $folder_server2 = substr($toolsfors3_name_file, 0, $pos) . "/";
        if ($pos !== false) {
            $toolsfors3_name_base = substr($toolsfors3_name_file, $pos + 1);
        } else {
            $toolsfors3_name_base = $toolsfors3_name_file;
        }
        $toolsfors3_temp = explode("/", $folder_server2);
        $toolsfors3_filepath = $folder_server . "/" . $folder_server2;
        $toolsfors3_temp2 = $folder_server;
        for ($w = 0; $w < count($toolsfors3_temp); $w++) {
            $toolsfors3_temp2 .= "/" . $toolsfors3_temp[$w];
            if (!is_dir($toolsfors3_temp2)) {
                if (!mkdir($toolsfors3_temp2, 0755, true)) {
                    $msg = "Failed to create folder: " . $toolsfors3_temp2;
                    toolsfors3_record_debug($msg);
                    die("Failed to create directories...");
                }
            }
        }
        $toolsfors3_server_filepath = $folder_server . "/" . $folder_server2;
        if (!is_dir($toolsfors3_server_filepath)) {
            if (!mkdir($toolsfors3_server_filepath, 0755, true)) {
                $msg = "Failed to create folder: " . $toolsfors3_server_filepath;
                toolsfors3_record_debug($msg);
                die("Failed to create directories...");
            }
        }
        $toolsfors3_filepath .= $toolsfors3_name_base;
        $toolsfors3_filepath = str_replace("//", "/", $toolsfors3_filepath);
        try {
            $objInfo9 = $toolsfors3_s3->doesObjectExist($bucket_name, $toolsfors3key);
            if (!$objInfo9) {
                $msg = "Object (File) doesn't exist  " . $toolsfors3key;
                toolsfors3_record_debug($msg);
                return "-1";
            }
            $objInfo = $toolsfors3_s3->headObject([
                "Bucket" => $bucket_name,
                "Key" => $toolsfors3key,
            ]);
            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            if ($objInfo["ContentLength"] > 1200000000) {
                return "-2";
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to Read From Cloud: " .
                $toolsfors3key .
                " with error: " .
                $exception->getMessage();
            toolsfors3_record_debug($msg);
            return "-1";
        }
        try {
            // bigger than 3 mega???
            if (
                $objInfo["ContentLength"] > 3000000 and
                time() + 20 > $toolsfors3_time_limit
            ) {
                $done = toolsfors3_get_files_done();
                $todo = toolsfors3_get_total_db_files();
                $res = ($done / $todo) * 100;
                $done = round($res, 0);
                die("Transferred: " . $done . "%");
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to Read Stream: " .
                $toolsfors3key .
                " with error: " .
                $exception->getMessage();
            toolsfors3_record_debug($msg);
            return "-1";
        }
        if ($objInfo["ContentLength"] > 100000000) {
            $toolsfors3_filesize = $objInfo["ContentLength"];

            $toolsfors3_done = 0;
            $toolsfors3_start_byte = 0;
            $toolsfors3_buffer = 20000 * 1024;
            $toolsfors3_end_byte = $toolsfors3_buffer - 1;
            $part = 0;

            // >>>>>>>>>>  erase all possible temp?????


            while ($toolsfors3_done < $toolsfors3_filesize) {

                // gc_collect_cycles();

                $time_start = microtime(true);
                try {
                    $file_part_path = $toolsfors3key . ".toolsfors3part" . $part;
                    $objInfo2 = $toolsfors3_s3->doesObjectExist(
                        $bucket_name,
                        $file_part_path
                    );
                    if (!$objInfo2) {
                        $todo = $toolsfors3_filesize - $toolsfors3_done;
                        if ($toolsfors3_buffer > $todo) {
                            $toolsfors3_buffer = $todo;
                        }

                        $toolsfors3_Range =
                            "bytes=" .
                            (string) $toolsfors3_start_byte .
                            "-" .
                            (string) $toolsfors3_end_byte;
                        $file = $toolsfors3_s3->getObject([
                            "Bucket" => $bucket_name,
                            "Key" => $toolsfors3key,
                            "Range" => $toolsfors3_Range,
                        ]);

                        $toolsfors3_temp = $file["Body"];
                        $toolsfors3_temp_size = strlen($toolsfors3_temp);

                        if ($toolsfors3_temp_size != $toolsfors3_buffer) {
                            die("Fail to split: " . $part);
                        }
                    } else {
                        // exists...
                        $objInfo4 = $toolsfors3_s3->headObject([
                            "Bucket" => $bucket_name,
                            "Key" => $file_part_path,
                        ]);

                        if (gettype($objInfo4) == "object") {
                            $toolsfors3_temp_size = $objInfo4["ContentLength"];
                        } else {
                            die("Fail to split (759) Part: " . $part);
                        }
                    }
                    $toolsfors3_done = $toolsfors3_done + $toolsfors3_temp_size;
                    $toolsfors3_start_byte = $toolsfors3_done;
                    $todo = $toolsfors3_filesize - $toolsfors3_done;
                    if ($todo > $toolsfors3_buffer) {
                        $todo = $toolsfors3_buffer;
                    }
                    $toolsfors3_end_byte = $toolsfors3_start_byte + $todo - 1;
                    $time_end = microtime(true);
                    $time = $time_end - $time_start;
                    $msg = "duration read obj " . $time;
                    // toolsfors3_record_debug($msg);
                    $wname = $toolsfors3key . ".toolsfors3part" . $part;
                    if (!$objInfo2) {
                        if (empty($toolsfors3_temp)) {
                            // debug...
                            die(
                                "empty (1). offset: " .
                                    $toolsfors3key .
                                    " offset  " .
                                    $toolsfors3_start_byte
                            );
                        }

                        $st = toolsfors3_get_copy_status();
                        if (substr($st, 0, 12) != "transferring") {
                            die("cancelled.");
                        }

                        $time_start = microtime(true);
                        $r = $toolsfors3_s3->putObject([
                            "Bucket" => $bucket_name,
                            "Key" => $file_part_path,
                            "Body" => $toolsfors3_temp,
                        ]);

                        $st = toolsfors3_get_copy_status();
                        if (substr($st, 0, 12) != "transferring") {
                            $result = $toolsfors3_s3->deleteObject([
                                "Bucket" => $bucket_name,
                                "Key" => $file_part_path,
                            ]);

                            die("cancelled.");
                        }
                    }
                    $toolsfors3_temp = null;
                } catch (Exception $exception) {
                    $msg =
                        "Failed to Get Object with Range: " .
                        $toolsfors3key .
                        " with error: " .
                        $exception->getMessage();
                    toolsfors3_record_debug($msg);
                    return "-1";
                }
                $toolsfors3_temp = null;
                $time_end = microtime(true);
                $time = $time_end - $time_start;
                $msg = "duration write obj " . $time;
                // toolsfors3_record_debug($msg);
                $table_name = $wpdb->prefix . "toolsfors3_copy_files";
                $wname = base64_encode(trim($wname));
                $query = "select name from $table_name WHERE name = '$wname' limit 1";
                $r = $wpdb->get_var($query);

                if (empty($r) or $r == "NULL") {
                    $query = "insert IGNORE into `$table_name` (`name`, `splited`) VALUES ('$wname', '1')";
                
                }    else {

                    $query = "UPDATE `$table_name` set `splited` = '1', flag = '' WHERE name = '$wname' limit 1";
 
                }
                
                    $msg = "spl " . $query;
                    $msg .= PHP_EOL;
                    $msg .= "Name File: " . base64_decode($wname);
                    // toolsfors3_record_debug($msg);
                    $r = $wpdb->get_var($query);
                $part++;
                gc_collect_cycles();

                if (time() + 30 > $toolsfors3_time_limit) {
                    die("Splitting large file: " . $toolsfors3key);
                }
            } // Loop
            toolsfors3_flag_file($id_to_copy, "1");
            die("Spliting large file: " . $toolsfors3key);
        } // if $objInfo['ContentLength'] > 105000000 )
        try {
            $result = $toolsfors3_s3->getObject([
                "Bucket" => $bucket_name,
                "Key" => $toolsfors3key, // Object Key
                "SaveAs" => $toolsfors3_filepath,
            ]);
            if ($result) {
                return "1";
            } else {
                $msg = "Failed to transfer: " . $toolsfors3_filepath . " (-99)";
                toolsfors3_record_debug($msg);
                return "-1";
            }
        } catch (Exception $exception) {
            $msg =
                "Failed to transfer $file_name_base with error: " .
                $exception->getMessage();
            toolsfors3_record_debug($msg);
            return "-1";
        } // end catch
    } // end cloud-server
    return false;
} // end function make copy

function toolsfors3_get_copy_status()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "toolsfors3_copy";
    if (!toolsfors3_tablexist($table_name)) {
        return;
    }

    $query = "select mystatus from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function toolsfors3_record_log($text)
{
    global $wpdb;

    $table_name = $wpdb->prefix . "toolsfors3_copy";
    if (!toolsfors3_tablexist($table_name)) {
        return;
    }

    $txt = PHP_EOL . date("Y-m-d H:i:s") . " " . $text . PHP_EOL;
    $txt .= "------------------------------";
    $query = "select log from $table_name ORDER BY id DESC limit 1";
    $log = $wpdb->get_var($query);
    $content = $log . $txt;
    $r = $wpdb->query(
        $wpdb->prepare(
            "UPDATE  `$table_name`
     SET log = %s",
            $content
        )
    );
}

function toolsfors3_get_qfiles()
{
    global $wpdb;
    global $bill_debug;
    if ($bill_debug) {
        return 500;
    }
    $table_name = $wpdb->prefix . "toolsfors3_copy";
    $query = "select qfiles from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function toolsfors3_fetch_files($dir, &$results = [])
{
    try {
        $files = scandir($dir);
    } catch (Exception $exception) {
        $msg = "Failed to scandir with error: " . $exception->getMessage();
        toolsfors3_record_debug($msg);
        die("Fail to Scandir");
    } // end catch

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (is_dir($path) == false) {
            $results[] = $path;
        } elseif ($value != "." && $value != "..") {
            toolsfors3_fetch_files($path, $results);
            if (is_dir($path) == false) {
                $results[] = $path;
            }
        }
    }
    return $results;
}
function toolsfors3_get_files_from_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query =
        "select name, id from " .
        $table_name .
        " where flag <> '1' ORDER BY id"; //  LIMIT 1000";
    $query = "select name, id from " . $table_name . " ORDER BY id"; //  LIMIT 1000";
    $results = $wpdb->get_results($query, ARRAY_A);
    return $results;
}

function toolsfors3_get_files_to_join()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query =
        "select name, id, upload_id, splited, part_number, etag from " .
        $table_name .
        " where flag = '9'";
    $r = $wpdb->get_results($query, ARRAY_A);


    if (count($r) > 0) {
        for ($i = 0; $i < count($r); $i++) {
            $r[$i]["name"] = base64_decode($r[$i]["name"]);
            $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
            $r[$i]["etag"] = base64_decode($r[$i]["etag"]);
        }

        //sort...
        usort($r, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
    }

    return $r;
}

function toolsfors3_get_files_to_copy_splited()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query =
        "select name, id, flag, upload_id, splited, part_number from " .
        $table_name .
        " where splited = '1'";
    $r = $wpdb->get_results($query, ARRAY_A);

    //die($query );


    if (count($r) > 0) {
        for ($i = 0; $i < count($r); $i++) {
            $r[$i]["name"] = base64_decode($r[$i]["name"]);
            $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
        }

        //sort...
        usort($r, function ($a, $b) {
            return strnatcasecmp($a["name"], $b["name"]);
        });
    }

    return $r;
}
function toolsfors3_get_files_to_copy($limit)
{
    global $wpdb;

    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query =
        "select name, id, upload_id, splited, part_number from " .
        $table_name .
        " where flag <> '1' and flag <> '2' and flag <> '9' LIMIT " .
        $limit;
    $r = $wpdb->get_results($query, ARRAY_A);

    for ($i = 0; $i < count($r); $i++) {
        $r[$i]["name"] = base64_decode($r[$i]["name"]);
        $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
    }

    //sort...
    usort($r, function ($a, $b) {
        return strnatcasecmp($a["name"], $b["name"]);
    });

    return $r;
}

function toolsfors3_get_files_done()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    // $query = "select name, id from " . $table_name . " where flag <> '1' ORDER BY id LIMIT " . $limit;
    $query = "select count(*) from $table_name where flag = '1'";
    return $wpdb->get_var($query);
}
function toolsfors3_get_total_db_files()
{
    global $wpdb;
    global $bill_debug;
    if ($bill_debug) {
        return 500;
    }
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query = "select count(*) from $table_name";
    return $wpdb->get_var($query);
}
function toolsfors3_unflag()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $query = "update " . $table_name . " SET flag = ''";
    $r = $wpdb->query($query);
    return $r;
}
function toolsfors3_flag_file($id, $splited = "")
{
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";

    // update wp_toolsfors3_copy_files SET flag = '1' WHERE id = 1 LIMIT 1


    if ($splited == "") {
        $query =
            "update " . $table_name .
            " SET flag = '1'
      WHERE id = $id LIMIT 1";
      //die($query);
        $r = $wpdb->query($query);
    } else {
        $query =
            "update " . $table_name .  
             " SET flag = '1', splited = '$splited'
      WHERE id = '$id'
      LIMIT 1";
      //die($query);
        $r = $wpdb->query($query);
    }

    return $r;
}
function toolsfors3_copy_inic()
{
    global $wpdb;
    global $server_cloud;
    global $folder_server;
    global $folder_cloud;
    global $bucket_name;

    $table_name = $wpdb->prefix . "toolsfors3_copy";
    if (toolsfors3_tablexist($table_name)) {

        $query = "TRUNCATE TABLE " . $table_name;

        $r = $wpdb->query($query);

        $r = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO `$table_name` 
        (`from`, `folder_server`, `folder_cloud`, `bucket`,`mystatus`)
        VALUES (%s, %s , %s ,%s,'starting')",
                $server_cloud,
                $folder_server,
                $folder_cloud,
                $bucket_name
            )
        );
    }

    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    if (toolsfors3_tablexist($table_name)) {
        $query = "TRUNCATE TABLE " . $table_name;
        $r = $wpdb->query($query);
        toolsfors3_unflag();
    }
}
function toolsfors3_get_scan_status()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_scan";
    $query = "select mystatus from $table_name ORDER BY id DESC limit 1";
    return $wpdb->get_var($query);
}

function toolsfors3_scan_cloud($folder_cloud)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $toolsfors3_s3;

    if (empty($folder_cloud)) {
        return false;
    }

    $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once $path;

    try {
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = "";
        }

        $objects = $toolsfors3_s3->getIterator("ListObjects", [
            "Bucket" => $bucket_name,
            "Prefix" => $folder_cloud,
        ]);
    } catch (AWSException $e) {
        $msg =
            "Failed to Get List of objects with error: " .
            $exception->getMessage();
        toolsfors3_record_debug($msg);
        die();
    }
    $files = [];

    foreach ($objects as $ob) {
        if (substr($ob["Key"], -1) != "/") {
            $files[] = $ob["Key"];
        }
    }

    return $files;
} // end function scan cloud

function toolsfors3_split_server_file($toolsfors3_name_file, $id)
{
    //  ------------  PREPAR server to split

    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $toolsfors3_time_limit;
    global $toolsfors3_s3;

    if (empty($toolsfors3_name_file)) {
        return false;
    }

    $pos = strrpos($toolsfors3_name_file, "/");
    $file_name_base = substr($toolsfors3_name_file, $pos + 1);

    $file_part_path = substr($toolsfors3_name_file, 0, $pos);

    if (substr($folder_cloud, 0, 4) == "Root") {
        $folder_cloud = substr($folder_cloud, 5);
    }

    $pos = strrpos($folder_server, "/");
    $capar = substr($folder_server, 0, $pos + 1);

    if (empty($folder_cloud)) {
        $toolsfors3key = str_replace($capar, "", $toolsfors3_name_file);
    } else {
        $toolsfors3key =
            $folder_cloud . "/" . str_replace($capar, "", $toolsfors3_name_file);
    }

    // --------------- SPLIT .... server

    try {
        $toolsfors3_buffer = 20000 * 1024;

        //open file to read
        $file_handle = fopen($toolsfors3_name_file, "r");
        if ($file_handle === false) {
            $msg = "Fail Open File: " . $toolsfors3_name_file;
            toolsfors3_record_debug($msg);
            die($msg);
        }

        //get file size
        $file_size = filesize($toolsfors3_name_file);
        //no of parts to split
        $parts = $file_size / $toolsfors3_buffer;

        //store all the file names
        $file_parts = [];

        //path to write the final files
        //$store_path = getcwd(); // "splits/";
        $store_path = $folder_server . "/";

        //name of input file
        $file_name = basename($toolsfors3_name_file);

        for ($i = 0; $i < $parts; $i++) {
            $st = toolsfors3_get_copy_status();
            if (substr($st, 0, 12) != "transferring") {
                die("cancelled.");
            }

            //read buffer sized amount from file
            $file_part = fread($file_handle, $toolsfors3_buffer);
            //the filename of the part

            $file_part_name =
                $file_part_path . "/" . $file_name . ".toolsfors3part$i";

            //open the new file [create it] to write
            $file_new = fopen($file_part_name, "w+");
            if($file_new === false) {
                $msg = "Fail to Open Temporary File: " . $file_part_name;
                toolsfors3_record_debug($msg);
                die($msg);
            }


            //write the part of file
            $r = fwrite($file_new, $file_part);
            if($r === false) {
                $msg = "Fail to Write Temporary File: " . $file_part_name;
                toolsfors3_record_debug($msg);
                die($msg);
            }

            if(!file_exists($file_part_name))
            {

                $msg = "Fail to Create Temporary File: " . $file_part_name;
                toolsfors3_record_debug($msg);
                die($msg);


            }
            

            array_push($file_parts, $file_part_name);
            //die('array: '.var_export($file_parts,true));

            //close the part file handle
            fclose($file_new);
        }
        //close the main file handle
        fclose($file_handle);

        return $file_parts;

    } catch (Exception $exception) {
        $msg =
            "Failed to split $toolsfors3_name_file with error: " .
            $exception->getMessage();
        toolsfors3_record_debug($msg);
        // return "-1";
        return [];
    }
} // end function toolsfors3_split_server_file

function toolsfors3_join()
{
    global $wpdb;
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $toolsfors3_time_limit;
    global $toolsfors3_s3;
    $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once $path;
    // $msg = "Begin function";


    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
   
    if($server_cloud == 'cloud') {
        $query =
        "select name, id, upload_id, etag, part_number from " .
        $table_name .
        " where flag = '1' and splited = '1'";
    }
    else {

        $query =
            "select name, id, upload_id, etag, part_number from " .
            $table_name .
            " where (flag = '2' and splited = '0') or (flag = '1' and splited = '1')";

    }


    //    " where flag = '1' and splited = '1'";
    $r = $wpdb->get_results($query, ARRAY_A);
    if ($r === false) {
        die("Fail to read table (to join)");
    }
    if (count($r) < 1) {
        // end of job ...
        return "1";
    }


    for ($i = 0; $i < count($r); $i++) {
        $r[$i]["id"] = $r[$i]["id"];
        $r[$i]["part_number"] = $r[$i]["part_number"];
        $r[$i]["upload_id"] = base64_decode($r[$i]["upload_id"]);
        $r[$i]["etag"] = base64_decode($r[$i]["etag"]);
        $r[$i]["name"] = base64_decode($r[$i]["name"]);
    }

    // No parts...
    for ($w = 0; $w < count($r); $w++) {
        if (strpos($r[$w]["name"], ".toolsfors3part") !== false) {
            break;
        }
    }


    if (count($r) == $w) {
        return "1";
    }

    //sort...
    usort($r, function ($a, $b) {
        return strnatcasecmp($a["name"], $b["name"]);
    });

    for ($i = 0; $i < count($r); $i++) {
        $id = $r[$i]["id"];
        $name = $r[$i]["name"];
        $etag = $r[$i]["etag"];
        $upload_id = $r[$i]["upload_id"];

        $pos2 = strrpos($name, "/");
        $filepath = trim(substr($name, 0, $pos2 + 1));
        if ($server_cloud == "cloud") {
            if ($pos2 === false) {
                $filepath = "";
                $namefile = $name;
            } else {
                $filepath = trim(substr($name, 0, $pos2 + 1));
                $namefile = trim(substr($name, $pos2));
            }
        } else {
            $namefile = trim(substr($name, $pos2 + 1));
        }
        $pos = strrpos($namefile, ".");
        if ($pos === false) {
            $part = "";
        } else {
            $part = trim(substr($namefile, $pos + 1));
        }
        if ($part == ".toolsfors3part") {
            $original_name = trim(substr($namefile, 0, $pos));
        } else {
            $original_name = $namefile;
        }
        $newarray[$i]["originalname"] = $original_name;
        $newarray[$i]["filepath"] = $filepath;
        $newarray[$i]["namefile"] = $namefile;
        $newarray[$i]["id"] = $id;
        $newarray[$i]["etag"] = $etag;
        $newarray[$i]["upload_id"] = $upload_id;

    } // end loop

    // filter
    $toolsfors3_original_name = $newarray[0]["originalname"];
    $newarray_todo = $newarray;
    for ($j = 0; $j < count($newarray); $j++) {
        if ($newarray[$j]["originalname"] == $toolsfors3_original_name) {
            $newarray_todo[$j] = $newarray[$j];
        }
    }

    $newarray = $newarray_todo;

    //sort...
    usort($newarray, function ($a, $b) {
        return strnatcasecmp($a["namefile"], $b["namefile"]);
    });


    for ($i = 0; $i < count($newarray); $i++) {
        if (!isset($newarray[$i])) {
            continue;
        }


        // >>>>>>>>>>>>>>>>>>>>>>>>  do it...

        if ($server_cloud == "cloud") {


            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>    Cloud to ==>  Server




            $original_name = $newarray[$i]["originalname"];

            // die(var_export($original_name));


            $file_path = $newarray[$i]["filepath"];
            if (empty($original_name)) {
                continue;
            }




            $pos = strpos($original_name, ".toolsfors3part");
            if ($pos === false) {
                continue;
            }
            $filetemp = $folder_server . "/" . $original_name;
            // >>>>>>>>>>>>>>>>>> Get Original
            // double check
            $pos = strrpos($original_name, ".toolsfors3part");
            if ($pos === false) {
                $toolsfors3key_original = $original_name;
            } else {
                $toolsfors3key_original = substr($original_name, 0, $pos);
            }
            // end double check original
            $filepath = trim($file_path);
            if (empty($filepath)) {
                $filetempori = $folder_server . "/" . $toolsfors3key_original;
            } else {
                $filetempori =
                    $folder_server . "/" . $filepath . $toolsfors3key_original;
            }
            $filetempori = str_replace("//", "/", $filetempori);
            // Begin low level
            try {
                if (file_exists($filetempori)) {
                    // die(var_export(__LINE__));
                    $fh_original = fopen($filetempori, "a");
                    fseek($fh_original, SEEK_END);
                } else {
                    // die(var_export(__LINE__));
                    $fh_original = fopen($filetempori, "w");
                }
                if ($fh_original === false) {
                    $msg = "Failed to Create / Open file: " . $filetemp;
                    toolsfors3_record_debug($msg);
                    die($msg);
                } else {
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to Open: $filetemp , with error: " .
                    $exception->getMessage();
                toolsfors3_record_debug($msg);
                die("Fail to Create Temp File " . $filetemp);
                //return "-1";
            }

            // Loop

            for ($j = 0; $j < count($newarray); $j++) {
                if (empty($newarray[$j]["originalname"])) {
                    continue;
                }
                $file_part_name = $newarray[$j]["namefile"];
                $id = $newarray[$j]["id"];
                $filepath = trim($newarray[$j]["filepath"]);
                if (!empty($filepath)) {
                    $filetemp =
                        $folder_server . "/" . $filepath . $original_name;
                } else {
                    $filetemp = $folder_server . "/" . $original_name;
                }
                while (strpos($filetemp, "//") !== false) {
                    $filetemp = str_replace("//", "/", $filetemp);
                }
                try {



                    // Part0, truncate original.
                    if (strpos($filetemp, "toolsfors3part0") !== false) {
                        ftruncate($fh_original, 0);
                    }
                    if (!file_exists($filetemp)) {
                        $msg = "File Part name doesn't exist: " . $filetemp;
                        toolsfors3_record_debug($msg);
                        die($msg);
                    } else {
                        $fh_part = fopen($filetemp, "r");
                        if ($fh_part === false) {
                            $msg = "Failed to Open Part Name: " . $filetemp;
                            toolsfors3_record_debug($msg);
                            die($msg);
                        }
                                      }



                    if ($toolsfors3key_original != $file_part_name) {

                        $file_part_size = filesize($filetemp);
                        $file_part = fread($fh_part, $file_part_size);
                        fclose($fh_part);



                        if (substr($folder_cloud, 0, 4) == "Root") {
                            $folder_cloud = $file_path.substr($folder_cloud, 5);
                        }


                        if (empty($folder_cloud)) {
                            $toolsfors3key = $file_part_name;
                        } else {
                            $toolsfors3key = $folder_cloud . "/" . $file_part_name;
                        }
                        while (strpos($toolsfors3key, "//") !== false) {
                            $toolsfors3key = str_replace("//", "/", $toolsfors3key);
                        }



                        $r = fwrite($fh_original, $file_part);

                        if ($r != $file_part_size) {
                            $msg =
                                "Failed to join (-1524) file Name: " .
                                $filetemp;
                            toolsfors3_record_debug($msg);
                            die("Error size...");
                        } else {



                            $pos = strpos($toolsfors3key, ".toolsfors3part");
                            if ($pos !== false) {
                                if (!unlink($filetemp)) {
                                    $msg =
                                        "Failed to erase temp file Name: " .
                                        $filetemp;
                                    toolsfors3_record_debug($msg);
                                    die($msg);
                                }


                                if (substr(trim($toolsfors3key), 0, 1) == "/") {
                                    $toolsfors3key = substr($toolsfors3key, 1);
                                }

                                $result = $toolsfors3_s3->deleteObject([
                                    "Bucket" => $bucket_name,
                                    "Key" => $toolsfors3key,
                                ]);


                                if ($result == false) {
                                    $msg =
                                        "Failed to erase temp file Name: " .
                                        $toolsfors3key;
                                    toolsfors3_record_debug($msg);
                                    die($msg);
                                }


                            }
                        }
                    }
                    // " where flag = '2' and splited = '0'";
                    $query =
                        "update " .
                        $table_name .
                        " SET flag = '8', splited = '8'
                            WHERE id = $id 
                            LIMIT 1";
                    $r = $wpdb->query($query);
                    $msg = "Joined Part: " . $filetemp;
                    //toolsfors3_record_debug($msg);
                    die($msg);
                } catch (Exception $exception) {
                    $msg =
                        "Failed Low Level operation Joining files (1), with error: " .
                        $exception->getMessage();
                    toolsfors3_record_debug($msg);
                    die($msg); // with error: " . $exception->getMessage();
                    // return "-1";
                }
            } // end for next do it
            fclose($fh_original);
        }
        // endif $server_cloud == 'cloud')
        else {



            // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>   Server to => Cloud  <<<<<<<<<<<<<<<<<<<<<<<





            $toolsfors3_time_limit2 = time() + 90;

            $msg = "Begin Server to cloud";
            //error_log($msg);
            //toolsfors3_record_debug($msg);
            if (empty($original_name)) {
                continue;
            }

            $file_path = $newarray[$i]["filepath"];
            $original_name = $newarray[$i]["originalname"];
            $toolsfors3_name_file = $file_path . "/" . $original_name;
            while (strpos($toolsfors3_name_file, "//") !== false) {
                $toolsfors3_name_file = str_replace("//", "/", $toolsfors3_name_file);
            }
            $pos = strrpos($toolsfors3_name_file, "/");
            $file_name_base = substr($toolsfors3_name_file, $pos + 1);
            if (substr($folder_cloud, 0, 4) == "Root") {
                $folder_cloud = substr($folder_cloud, 5);
            }
            $pos = strrpos($folder_server, "/");
            $capar = substr($folder_server, 0, $pos + 1);
            if (empty($folder_cloud)) {
                $toolsfors3key_original = str_replace(
                    $capar,
                    "",
                    $toolsfors3_name_file
                );
            } else {
                $toolsfors3key_original =
                    $folder_cloud .
                    "/" .
                    str_replace($capar, "", $toolsfors3_name_file);
            }
            while (strpos($toolsfors3key_original, "//") !== false) {
                $toolsfors3key_original = str_replace(
                    "//",
                    "/",
                    $toolsfors3key_original
                );
            }
            // Begin low level
            try {
                // >>>>>>>>>>>>>>>>>> Get Original
                // double check
                $pos = strrpos($toolsfors3key_original, ".toolsfors3part");
                if ($pos !== false) {
                    $toolsfors3key_original = trim(
                        substr($toolsfors3key_original, 0, $pos)
                    );
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed to Open: $toolsfors3key_original , with error: " .
                    $exception->getMessage();
                toolsfors3_record_debug($msg);
                die($msg);
                // return "-1";
            }

            $namefile = $newarray[$i]["namefile"];
            $id = $newarray[$i]["id"];

            // die($newarray[$i]["namefile"]);

            $pos99 = strrpos($namefile, ".toolsfors3part");

            $filetemp2 = $newarray[$i]["namefile"];
            $filepath3 = $newarray[$i]["filepath"];

            $filetemp4 = $filepath3 . $filetemp2;

            // protect original...
            $pos99 = strrpos($filetemp2, ".toolsfors3part");
            if ($pos99 === false) {
                continue;
            }

            try {
                if (file_exists($filetemp4)) {


                    if (!unlink($filetemp4)) {
                        $msg =
                            "Failed to erase temp file Name (2): " . $filetemp4;
                        toolsfors3_record_debug($msg);
                        //die($msg);
                    } else {
                        $msg = "Erased temp file Name (3): " . $filetemp4;
                        // toolsfors3_record_debug($msg);
                    }

                    $query =
                        "update " .
                        $table_name .
                        " SET flag = '8', splited = '8'  " .
                        " WHERE id = '" .
                        $id .
                        "' 
                    LIMIT 1";
                    $r = $wpdb->query($query);
                }
            } catch (Exception $exception) {
                $msg =
                    "Failed Low Level operation Deleting Temporary Object (22), with error: " .
                    $exception->getMessage();
                toolsfors3_record_debug($msg);
                fclose($toolsfors3_stream);
                die("Failed to erase temp part (-4) " . $toolsfors3key); // with error: " . $exception->getMessage();
                return "-1";
            }

            // end...


            if (time() + 10 > $toolsfors3_time_limit2) {
                die("Erasing Temp Files... " . $toolsfors3key);
            }

            continue;

            die("Joining files... " . $toolsfors3key);
        } // end Server or cloud
    } // end main

    die("Reloading...");
} // end function JOIN

function toolsfors3_complete_multipart($files_to_join)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
   // ? is completed?
   $table_name = $wpdb->prefix . "toolsfors3_copy_files";
   $query =
       "select name, id, upload_id, splited, part_number, etag from " .
       $table_name .
       " where splited = '1'";
   $r = $wpdb->get_results($query, ARRAY_A);
   $qtodo = count($r);
   $query =
   "select name, id, upload_id, splited, part_number, etag from " .
   $table_name .
   " where flag = '9'";
    $r = $wpdb->get_results($query, ARRAY_A);
    $qdone = count($r);
    if($qtodo > $qdone)
      return;
    $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once $path;
        $i = 0;
        $toolsfors3_name_file = $files_to_join[$i]["name"];
        $id = $files_to_join[$i]["id"];
        $upload_id = $files_to_join[$i]["upload_id"];
        $splited = $files_to_join[$i]["splited"];
        $part_number = $files_to_join[$i]["part_number"];
        if (empty($part_number)) {
            $part_number = 0;
        }
        $pos = strrpos($toolsfors3_name_file, "/");
        $file_name_base = substr($toolsfors3_name_file, $pos + 1);
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $toolsfors3key = str_replace($capar, "", $toolsfors3_name_file);
        } else {
            $toolsfors3key =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $toolsfors3_name_file);
            $toolsfors3key = str_replace("//", "/", $toolsfors3key);
        }
        $pos = strrpos($toolsfors3key, ".toolsfors3part");
        if ($pos !== false) {
            $toolsfors3key_original = substr($toolsfors3key, 0, $pos);
        }
        else
             $toolsfors3key_original = $toolsfors3key;
    for ($i = 0; $i < count($files_to_join); $i++) {
        $pIndex = $i + 1; // $partNumber - 1;
             $parts[$i] = [
                 "PartNumber" => $pIndex,
                 "ETag" => $files_to_join[$i]["etag"],
             ];
    }
    try {
        $result = $toolsfors3_s3->completeMultipartUpload([
            "Bucket" => $bucket_name,
            "Key" => $toolsfors3key_original,
            "UploadId" => $upload_id,
            "MultipartUpload" => [
                "Parts" => $parts,
            ],
        ]);
    } catch (Exception $exception) {
        $msg =
            "Failed to complete multipart: " .
            $toolsfors3key_original .
            ", with error: " .
            $exception->getMessage();
        toolsfors3_record_debug($msg);
        error_log($msg);
        die($msg);
    }
    if ($result) {
        for ($i = 0; $i < count($files_to_join); $i++) {
            $id = $files_to_join[$i]["id"];
            $query =
                "update " .
                $table_name .
                " SET flag = '2', splited = '0'  " .
                " WHERE id = '" .
                $id .
                "' 
                                    LIMIT 1";
            $r = $wpdb->query($query);
        }
    } else {
        die("fail to complete multipart 255");
    }
}

function toolsfors3_transfer_splited($files_to_copy)
{
    global $folder_server;
    global $folder_cloud;
    global $server_cloud;
    global $bucket_name;
    global $wpdb;
    $table_name = $wpdb->prefix . "toolsfors3_copy_files";
    $path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
    require_once $path;
    $qfiles_to_copy = count($files_to_copy);
    for ($i = 0; $i < $qfiles_to_copy; $i++) {
        $toolsfors3_name_file = $files_to_copy[$i]["name"];
        $id = $files_to_copy[$i]["id"];
        $upload_id = $files_to_copy[$i]["upload_id"];
        $splited = $files_to_copy[$i]["splited"];
        $part_number = $files_to_copy[$i]["part_number"];
        $flag = $files_to_copy[$i]["flag"];
        if($flag == '9')
          continue;
        if (empty($part_number)) {
            $part_number = 0;
        }
        $pos = strrpos($toolsfors3_name_file, "/");
        $file_name_base = substr($toolsfors3_name_file, $pos + 1);
        //  die($toolsfors3_name_file);
        // /home/toolsfors3/public_html/lixo/teste2/scan_log.php
        if (substr($folder_cloud, 0, 4) == "Root") {
            $folder_cloud = substr($folder_cloud, 5);
        }
        $pos = strrpos($folder_server, "/");
        $capar = substr($folder_server, 0, $pos + 1);
        if (empty($folder_cloud)) {
            $toolsfors3key = str_replace($capar, "", $toolsfors3_name_file);
        } else {
            $toolsfors3key =
                $folder_cloud .
                "/" .
                str_replace($capar, "", $toolsfors3_name_file);
            $toolsfors3key = str_replace("//", "/", $toolsfors3key);
        }
        $pos = strrpos($toolsfors3key, ".toolsfors3part");
        if ($pos !== false) {
            $toolsfors3key_original = substr($toolsfors3key, 0, $pos);
        }
        else
             $toolsfors3key_original = $toolsfors3key;
        if ($part_number == '1' ) {
            $multipart_uploads = $toolsfors3_s3->listMultipartUploads([
                "Bucket" => $bucket_name,
                //'Prefix' => (string) $job_object->job['s3dir'],
            ]);
            $uploads = $multipart_uploads["Uploads"];
            if (!empty($uploads)) {
                foreach ($uploads as $upload) {
                    $toolsfors3_s3->abortMultipartUpload([
                        "Bucket" => $bucket_name,
                        "Key" => $upload["Key"],
                        "UploadId" => $upload["UploadId"],
                    ]);
                }
            }
        }
        try {
            $pos = strrpos($toolsfors3key, ".toolsfors3part");
            if ($pos !== false) {
                $toolsfors3key_original = substr($toolsfors3key, 0, $pos);
            }
            if (!isset($upload_id)) {
                $multipart_uploads = $toolsfors3_s3->listMultipartUploads([
                    "Bucket" => $bucket_name,
                    //'Prefix' => (string) $job_object->job['s3dir'],
                ]);
                $uploads = $multipart_uploads["Uploads"];
                if (!empty($uploads)) {
                    $upload_id = $uploads[0]["UploadId"];
                } else {
                    // try again ...
                    die("Fail to start Multipart Uploads... (277)");
                }
            }
            $pos = strrpos($toolsfors3key, ".toolsfors3part");
            if ($pos !== false) {
                $toolsfors3key_original = substr($toolsfors3key, 0, $pos);
            }
            $multipart_uploads = $toolsfors3_s3->listMultipartUploads([
                'Bucket' => $bucket_name
                //'Prefix' => (string) $job_object->job['s3dir'],
            ]);
            $uploads = $multipart_uploads['Uploads'];
           // die(var_export($uploads));
            if (!empty($uploads)) {
                $upload_id = $uploads[0]['UploadId'];
            }
            else
            {
                $result =  $toolsfors3_s3->createMultipartUpload(array(
                    // 'ACL' => 'public-read',
                    'Bucket' => $bucket_name,
                    'Key' => $toolsfors3key_original,
                ));
                $upload_id = $result['UploadId'];
            }
            $result = $toolsfors3_s3->uploadPart([
                "Bucket" => $bucket_name,
                "Key" => $toolsfors3key_original,
                "UploadId" => $upload_id,
                "PartNumber" => $part_number,
                "SourceFile" => $toolsfors3_name_file,
            ]);
        } catch (Exception $e) {
            die("Upload Part error: " . $e->getMessage());
            //die($e->getLine());
            // $e->getFile(),
        }
        if ($result) {
            // update database $parts...
            $wetag = base64_encode($result["ETag"]);
            $wupload_id = base64_encode($upload_id);
            $query = "update `$table_name` set  `upload_id` = '$wupload_id', `part_number` = '$part_number', `etag` = '$wetag', `flag` = '9'   WHERE id = '$id' LIMIT 1";
            $msg = "uploaded splited query: " . $query;
            $r = $wpdb->get_results($query);
            die('Transferred Part: '.$part_number.'/'.$qfiles_to_copy);
            // continue;
        } else {
            if ($part_number == 0) {
                $toolsfors3_s3->AbortMultipartUpload([
                    "Bucket" => $bucket_name,
                    "Key" => $toolsfors3key,
                    "UploadId" => $upload_id,
                ]);
            }
            $msg = "Failed to tranfer multipart:  " . $toolsfors3key;
            toolsfors3_record_debug($msg);
            die($msg);
            return "-1";
        }
    } // end main loop
    if (!isset($toolsfors3key_original)) {
        $toolsfors3key_original = $toolsfors3_name_file;
        $pos = strrpos($toolsfors3_name_file, ".toolsfors3part");
        if ($pos !== false) {
            $toolsfors3key_original = substr($toolsfors3_name_file, 0, $pos);
        }
    }
       die("Finished to upload large file: " . $toolsfors3key_original);
} // end function make transfer splited
