<?php
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
ini_set('memory_limit', '512M');
set_time_limit(600);
global $toolsfors3_dir_for_search;

if (current_user_can('administrator')) {
    // The current user is an administrator
    //echo 'You are an administrator.';
} else {
    // The current user is not an administrator
    die('You are not an administrator.');
}


$toolsfors3_dir_for_search = getcwd() . '/';
// if (!defined('ABSPATH')) define('ABSPATH', '');
$toolsfors3_dir_for_search = ABSPATH . '/';
$toolsfors3_dir_for_search = ABSPATH ;
$toolsfors3_data_filesys = toolsfors3_fetch_files($toolsfors3_dir_for_search);
// CREATE NODES //////////////////
$i = 0;
foreach ($toolsfors3_data_filesys as $key => $item)
{
    if ($item['parent'] == '-1') continue;
    if (!isset($item['parent'])) die('fail L 88');
    $toolsfors3_data_filesys[$item['parent']]['nodes'][] = $item['path'];
    continue;
}
//  CREATE JSON ////////////////////////
function toolsfors3_create_json_filesys($indice_node, $toolsfors3_data_filesys)
{
    global $toolsfors3_json;
    //global $j;
    $columns = array_column($toolsfors3_data_filesys, "path");
    array_multisort($columns, SORT_ASC, $toolsfors3_data_filesys);
    $tot = count($toolsfors3_data_filesys);
    $ctd = 0;
    $toolsfors3_json = '[{"text":"Root","icon":"","nodes":[';
    for ($i = 0;$i < $tot;$i++)
    {
        $ctd++;
        if ($toolsfors3_data_filesys[$i]['parent'] !== '-1') continue;
        $item = trim($toolsfors3_data_filesys[$i]['path']);
        if (empty($item)) continue;
        $toolsfors3_json .= '{';
        $toolsfors3_json .= '"text":"';
        $toolsfors3_json .= $item;
        $toolsfors3_json .= '","icon":""';
        // extra nodes
        $indice_node = array_search($item, array_column($toolsfors3_data_filesys, 'path') , true);
        if (isset($toolsfors3_data_filesys[$indice_node]['nodes'])) $toolsfors3_json = toolsfors3_s3transfer_create_nodes($indice_node, $toolsfors3_data_filesys);
        $toolsfors3_json .= '}';
        if ($ctd < $tot - 0)
        {
            $toolsfors3_json .= ',';
        }
    } // end for
    while (substr($toolsfors3_json, -1) == ',')
    {
        $toolsfors3_json = substr($toolsfors3_json, 0, strlen($toolsfors3_json) - 1);
    }
    $toolsfors3_json .= ']'; // end node
    $toolsfors3_json .= '}';
    // $toolsfors3_json .= ']'; // end sub main node
    $toolsfors3_json .= ']'; // end MAIN node
    return $toolsfors3_json;
} // end function Create Json
if (!isset($indice_node)) $indice_node = '-1';
$toolsfors3_json = toolsfors3_create_json_filesys($indice_node, $toolsfors3_data_filesys);
// $toolsfors3_json='[{"text":"Inbox","icon":"","nodes":[{"text":"Office","icon":"fa fa-inbox","nodes":[{"icon":"fa fa-inbox","text":"Customers"},{"icon":"fa fa-inbox","text":"Co-Workers"}]},{"icon":"fa fa-inbox","text":"Others"}]},{"icon":"fa fa-archive","text":"Drafts"},{"icon":"fa fa-calendar","text":"Calendar"},{"icon":"fa fa-address-book","text":"Contacts"},{"icon":"fa fa-trash","text":"Deleted Items"}]';
$toolsfors3_json = str_replace("<br>", "", $toolsfors3_json);
$toolsfors3_json = str_replace(array(
    "\n",
    "\r\n",
    "\r",
    "\t"
) , "", $toolsfors3_json);
//die(esc_attr($toolsfors3_json));
die($toolsfors3_json);

function toolsfors3_s3transfer_create_nodes($indice_node, $toolsfors3_data_filesys)
{
    global $toolsfors3_json;
    if (isset($toolsfors3_data_filesys[$indice_node]['nodes']))
    {
        $toolsfors3_data_filesys3 = $toolsfors3_data_filesys[$indice_node]['nodes'];
        $tot3 = count($toolsfors3_data_filesys3);
        if ($tot3 > 0) $toolsfors3_json .= ',"nodes": [';
        for ($k = 0;$k < $tot3;$k++)
        {
            $item3 = trim($toolsfors3_data_filesys3[$k]);
            $toolsfors3_json .= ' {
            "icon": "",
            "text": "' . $item3 . '"';
            $indice_node_node = array_search($item3, array_column($toolsfors3_data_filesys, 'path') , true);
            if (isset($toolsfors3_data_filesys[$indice_node_node]['nodes']))
            {
                // Node has node
                $toolsfors3_json = toolsfors3_s3transfer_create_nodes($indice_node_node, $toolsfors3_data_filesys);
            }
            $toolsfors3_json .= '}';
            if ($k < $tot3 - 1)
            {
                $toolsfors3_json .= ',';
            }
        } //  end for
        if ($tot3 > 0) $toolsfors3_json .= ']';
    } // end if tem nodes
    return $toolsfors3_json;
} // end function
function toolsfors3_fetch_files($dir)
{
    global $toolsfors3_filesys_result;
    global $toolsfors3_dir_for_search;
    $i = 0;
    $x = scandir($dir);
    if (!isset($toolsfors3_filesys_result)) $toolsfors3_filesys_result = array();
    foreach ($x as $filename)
    {
        if ($filename == '.') continue;
        if ($filename == '..') continue;
        $filePath = $dir . $filename;
        if (!is_dir($filePath)) continue;
        if (empty($filePath)) continue;
        if (is_dir($filePath))
        {
            if ($i == 0)
            {
                // Novo parente.
                $parent = $dir;
                $parent_for_search = trim(substr($dir, 0, strlen($dir) - 1));
                if ($parent_for_search == substr($toolsfors3_dir_for_search, 0, strlen($toolsfors3_dir_for_search) - 1))
                {
                    $indice_parent = '-1';
                }
                else
                {
                    if (gettype(count($toolsfors3_filesys_result)) == 'integer' and count($toolsfors3_filesys_result) > 0)
                    {
                        $indice_parent = array_search($parent_for_search, array_column($toolsfors3_filesys_result, 'path') , true);
                        if ($indice_parent === false)
                        {
                            // Bill
                            if (count($toolsfors3_filesys_result) == 0) $indice_parent;
                            else die('NOT FOUND !!!!');
                        }
                        $indice_parent = array_search($parent_for_search, array_column($toolsfors3_filesys_result, 'path') , true);
                    }
                    else
                    {
                        $indice_parent = 0;
                    }
                }
            } // end I = 0
            $ctd = count($toolsfors3_filesys_result);
            $toolsfors3_filesys_result[] = array(
                'path' => trim($filePath) ,
                'parent' => $indice_parent
            );
            $i++;
            $filePath = $dir . $filename . '/';
            foreach (toolsfors3_fetch_files($filePath) as $childFilename)
            {
                if (gettype($childFilename) === 'object') continue;
                if (!isset($childFilename[0])) continue;
                if ($childFilename[0] == '.') continue;
                if ($childFilename[0] == '..') continue;
                $filePath2 = $dir . $childFilename[0];
                if (!is_dir($filePath2)) continue;
                if (empty($filePath2)) continue;
                $ctd = count($toolsfors3_filesys_result);
                try
                {
                    $toolsfors3_filesys_result[] = array(
                        'path' => trim($filePath2) ,
                        'parent' => '999'
                    );
                    $i++;
                }
                catch(Exception $e)
                {
                    echo 'Message: ' . esc_attr($e->getMessage());
                }
            }
        } // end isdir
    } // end for
    // die(var_export($toolsfors3_filesys_result));
    return $toolsfors3_filesys_result;
} // end function
