<?php
if (!defined('ABSPATH')) {
    die('We\'re sorry, but you can not directly access this file.');
}
ini_set('memory_limit', '512M');
set_time_limit(600);
global $toolsfors3_region;
global $toolsfors3_secret_key;
global $toolsfors3_access_key;
global $bucket_name;



if (current_user_can('administrator')) {
    // The current user is an administrator
    //echo 'You are an administrator.';
} else {
    // The current user is not an administrator
    die('You are not an administrator.');
}


//if (!isset($_POST['toolsfors3_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['toolsfors3_nonce'])), 'toolsfors3_action')) {
 //   // wp_die('The nonce verification failed. Please try again.');
// }

//<a href="tools.php?page=toolsfors3_admin_page&tab=transf&

//debug2(wp_create_nonce('toolsfors3_action'));


 if (!isset($_GET['toolsfors3_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash ($_GET['toolsfors3_nonce'])), 'toolsfors3_action')) {
  //  wp_die('The nonce verification failed. Please try again.');
 

}

if (isset($_POST['bucket_name'])) 
    $bucket_name = sanitize_text_field($_POST['bucket_name']);
else
    die('Missing Post bucket_name');
//if (!defined('ABSPATH')) 
//  define('ABSPATH', '');

use Aws\Exception\AwsException;

//$path = toolsfors3PATH . "/vendor/autoload.php";
//require_once($path);

$path = TOOLSFORS3PATH . "/functions/toolsfors3_connect.php";
require_once esc_url($path);

/*
$baseurl = $endpoints;
$credjson = base64_decode($toolsfors3_access_key);

$credarray = json_decode($credjson);
*/

// $config = ['s3-access' => ['key' => $toolsfors3_access_key, 'secret' => $toolsfors3_secret_key, 'bucket' => $bucket_name, 'region' => $toolsfors3_region, 'version' => 'latest', 'endpoint' => $endpoints]];
try {
    // $toolsfors3_s3 = new Aws\S3\S3Client(['credentials' => ['key' => $config['s3-access']['key'], 'secret' => $config['s3-access']['secret']], 'use_path_style_endpoint' => true, 'force_path_style' => true, 'endpoint' => $config['s3-access']['endpoint'], 'version' => 'latest', 'region' => $config['s3-access']['region']]);
    // TEST
  //  $buckets = $toolsfors3_s3->listBuckets();
  //  die(var_export($buckets));
    $objects = $toolsfors3_s3->getIterator('ListObjects', array(
        'Bucket' => $toolsfors3_config['s3-access']['bucket'],
       // 'Bucket' => 'documentos'
    ));
    // ));
  //  die(var_export($config['s3-access']['bucket']));
} catch (AWSException $e) {
    echo esc_attr($e->getStatusCode());
    echo esc_attr($e->getAwsErrorCode());
    echo esc_attr(explode(';', $e->getMessage())[1]);
    die();
}
//die(var_export($objects));


$elements = iterator_to_array($objects);
// Function to decode S3 object keys
function toolsfors3_decodeS3Key($key) {
    return rawurldecode($key);
}

function toolsfors3_buildTree(array $elements) {
    $tree = [];
    foreach ($elements as $element) {
        // Obter o caminho completo do arquivo
        $filePath = toolsfors3_decodeS3Key($element['Key']);
        // Verificar se o caminho contém diretórios (ignorar arquivos na raiz)
        if (strpos($filePath, '/') === false) {
            continue;
        }
        // elimina o nome do arq...
        $filePath = dirname($filePath);

        // Explodir o caminho em partes (diretórios e arquivos)
        $parts = explode('/', $filePath);
        // Construir a árvore baseada nos diretórios do caminho
        $currentLevel = &$tree;
        foreach ($parts as $part) {
            if ($part === '' or $part === '.' or $part === '..'  ) {
                continue;
            }
            $directoryName = $part;
            $found = false;
            // Procurar pelo diretório atual dentro do nível atual da árvore
            foreach ($currentLevel as &$node) {
                if ($node['text'] === $directoryName) {
                    // Encontrou o diretório, mudar para o próximo nível
                    $currentLevel = &$node['nodes'];
                    $found = true;
                    break;
                }
            }
            // Se não encontrou o diretório, adicionar um novo nó
            if (!$found) {
                $newNode = [
                    'text' => $directoryName,
                    'nodes' => [],
                    'icon' => 'bi bi-folder'
                ];
                $currentLevel[] = $newNode;
                $currentLevel = &$newNode['nodes']; // Mudar para o próximo nível
            }
        }
        // Remover a referência
        unset($currentLevel);
    }
    return $tree;
}

$treeviewData = toolsfors3_buildTree($elements);
unset($elements);

// Envolver os dados no formato especificado
$wrappedTreeData = [
    [
        "text" => "Root",
        "icon" => "",
        "nodes" => $treeviewData
    ]
];

// Converte a estrutura da árvore para JSON
$tree_json = json_encode($wrappedTreeData);

// Retorna o JSON para ser processado pelo JavaScript
die($tree_json);


///////////////////      end  ////////////////////////////////



$files = [];
// $ctd = 0;
foreach( $objects as $ob ) {
    if(substr($ob[ 'Key' ], -1) != '/') {
        $pos = strrpos($ob[ 'Key' ],'/');
        $ob[ 'Key' ] = substr($ob[ 'Key' ],0, $pos).'/';
    }
    $atemp = explode( '/', $ob[ 'Key' ] );
        //$atemp = $split;
    $acont = count($atemp );
    if( $acont == 2) {
          if (!empty($atemp[0])){
             if (!in_array($atemp[0].'/', $files)) 
               $files[] = $atemp[0].'/';
          }
          continue;
    }
        $temp2 = '';
        for($i = 0; $i < ($acont-1); $i++) {
            $temp2 = $temp2 . $atemp[$i];
            if (!in_array($temp2, $files)){ 
                if(substr($temp2, -1) != '/'){
                    $pos = strrpos($temp2,'/');
                    $ob[ 'Key' ] = substr($temp2,0, $pos-1);
                } 
                if($i < ($acont-1))
                  $temp2 .=  '/';
                if (!in_array($temp2, $files))
                   $files[] = $temp2;
            }
        }
}


sort($files, SORT_STRING);
$tot = count($files);
$toolsfors3_data_filesys = array();



$k = 0;
for($i = 0; $i < $tot; $i++){
    $temp = explode('/',$files[$i]);
    if( empty($temp[count($temp) -1 ]))
      unset($temp[count($temp) -1 ]);
    $parent = '-1';
    for($j = 0; $j < count($temp); $j++){

            // Remove the last element which is the file name
            // array_pop($temp);


            $toolsfors3_data_filesys[$k]['path'] = $temp[$j];
            if(count($temp) > 1) {
                $temp_size = strlen($files[$i]) - 1;
                $pos = strrpos(substr($files[$i],0, $temp_size) ,'/');
                $parent_for_search = substr($files[$i],0,$pos+1);
                $indice_parent = array_search($parent_for_search, array_column($toolsfors3_data_filesys, 'path') , true);
                }
            else
              $indice_parent = '-1';

            $toolsfors3_data_filesys[$k]['parent'] = $indice_parent;
            $k++;
            break;
        continue;

        /*
        if(count($temp) == 3) {
            $toolsfors3_data_filesys[$k]['path'] = $temp[0];
            $toolsfors3_data_filesys[$k]['parent'] = '-1';
            $k++;
            continue;
        }
          $parent = $temp[0];
          if($j == 1){
            $toolsfors3_data_filesys[$k]['parent'] = $parent;
            $toolsfors3_data_filesys[$k]['path'] = $temp[$j];
          }
          $k++;
        */
    }
     $toolsfors3_data_filesys[$i]['path'] = $files[$i];
}


// CREATE NODES //////////////////
$i = 0;
foreach ($toolsfors3_data_filesys as $key => $item)
{
    if ($item['parent'] == '-1') continue;

    // if (!isset($item['parent'])) die('fail L 88');

    $toolsfors3_data_filesys[$item['parent']]['nodes'][] = $item['path'];

    continue;

    /*
    if (isset($item['parent']))
    {


        // Define the base path using __FILE__ to ensure it is relative to the plugin's directory
        $plugin_dir_path = realpath(dirname(__FILE__) . '/..'); // Go up one level to get the plugin's root directory

        // Construct the full path to the target directory
        $target_path = $plugin_dir_path . '/assets/bootstrap4-glyphicons/css/fonts/fontawesome';

        // Now use this constructed path in the condition
        if ($item['path'] == $target_path) {
        //if ($item['path'] == '/home/toolsfors3/public_html/wp-content/plugins/toolsfors3/assets/bootstrap4-glyphicons/css/fonts/fontawesome')
            echo esc_attr($item['path']);
            echo '-----';
            die();
        }
        else
        {
            die(esc_attr($indice_node_node));
            $toolsfors3_data_filesys[$indice_node_node]['nodes'][] = $item['path'];
            esc_attr($toolsfors3_data_filesys[$indice_node_node]['nodes']);
            die();
        }
    }
    $i++;
    $path_ant = $item['path'];
    */
}
//  CREATE JSON ////////////////////////
function toolsfors3_create_json_filesys($indice_node, $toolsfors3_data_filesys)
{
    global $toolsfors3_json;
    //global $j;
    global $bucket_name;
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
        if ( substr($item, strlen($item)-1  ) == '/'  )
          $item2 = substr($item, 0, strlen($item)-1 );
        else
          $item2 = $item;
        $toolsfors3_json .= '{';
        $toolsfors3_json .= '"text":"';
        $toolsfors3_json .= $item2;
        $toolsfors3_json .= '","icon":""';
        // extra nodes
        $indice_node = array_search($item, array_column($toolsfors3_data_filesys, 'path') , true);
        if (isset($toolsfors3_data_filesys[$indice_node]['nodes'])) $toolsfors3_json = toolsfors3_create_nodes($indice_node, $toolsfors3_data_filesys);
        $toolsfors3_json .= '}';
        if ($ctd < $tot - 0)
        {
            $toolsfors3_json .= ',';
        }
    } // end for
    while (substr($toolsfors3_json, -1) == ',')
    {
        //if(substr($toolsfors3_json, -1) == ',') {
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

error_log(var_export($toolsfors3_json,true));

die($toolsfors3_json);
/*   END */
/*         FUNCTION  CREATE NODES            */
function toolsfors3_create_nodes($indice_node, $toolsfors3_data_filesys)
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
                $toolsfors3_json = toolsfors3_create_nodes($indice_node_node, $toolsfors3_data_filesys);
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
