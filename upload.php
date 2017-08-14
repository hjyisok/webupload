<?php

class upload
{
    function check(){
        $token=$_POST['token']?$_POST['token']:'';
        echo $token ==='6D55016540C384837CF116550E666950'?1:0;
    }
    //查看上传路径是否存在
    function checkdir(){
        $token=$_POST['token']?$_POST['token']:'';
        $path=$_POST['path']?$_POST['path']:'';
        $type=$_POST['type']?$_POST['type']:1;

        if($token !=='6D55016540C384837CF116550E666950'){
            echo true;
        }
        $basepath = $type==1?'images/hawkeye/':'task/hawkeye/';
        $uploadDir = $basepath.trim($path, '/');
        if(is_dir($uploadDir)){
            echo 1;
        }else{
            echo 0;
        }

    }

    function getPath(){
        $token=$_POST['token']?$_POST['token']:'';
        $page=intval($_POST['page'])?intval($_POST['page']):1;
        $type=intval($_POST['type'])?intval($_POST['type']):1;
        if($token !=='6D55016540C384837CF116550E666950'){
            die('请先登录');
        }
        $basedir = $type==1?'/data/image_server/images/hawkeye':'/data/image_server/task/hawkeye';
        $dirArray=$this->get_all_file($basedir);
        $start=($page-1)*20;
        $data['total']=count($dirArray);
        $data['list']=array_slice($dirArray,$start,20);
        $data['page']=$page;
        $data['maxpage']=ceil($data['total']/20);
        echo json_encode($data);
    }
    //删除目录
    function delPath(){
        $token=$_POST['token']?$_POST['token']:'';
        $path=trim($_POST['path'])?trim($_POST['path']):'';
        $type=intval($_POST['type'])?intval($_POST['type']):1;
        if($token !=='6D55016540C384837CF116550E666950'){
            die('请先登录');
        }
        echo $this->deldir($path);
    }
    function deldir($path) {
        $handle = opendir($path);
        while (($item = readdir($handle)) !== false) {
            if ($item == '.' || $item == '..') continue;
            $_path = $path . '/' . $item;
            if (is_file($_path)) unlink($_path);
            if (is_dir($_path)) rmdirs($_path);
        }
        closedir($handle);
        //删除当前文件夹：
        if(rmdir($path)) {
            return true;
        } else {
            return false;
        }
    }
    //递归获取指定目录下的目录
    function get_all_file($path){
        if($path != '.' && $path != '..' && is_dir($path)){
            $files = [];
            if($handle = opendir($path)){
                while($file = readdir($handle)){
                    if($file != '.' && $file != '..'){
                        $file_name = ($path . DIRECTORY_SEPARATOR . $file);
                        if(is_dir($file_name)){
                            $files[$file] = $this->get_all_file($file_name);
                        }else{
                            $files[] = $path;
                        }
                    }
                }
            }
        }
        closedir($handle);
        return $this->multiArrayToOne($files);
    }
    //多维数组转一维数组，并去重
    public function multiArrayToOne($multi)
    {
        $arr = array();
        foreach ($multi as $key => $val) {
            if (is_array($val)) {
                $arr = array_merge($arr, $this->multiArrayToOne($val));
            } else {
                $arr[] = $val;
            }
        }
        return array_unique($arr);
    }
    function index()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        if (!empty($_REQUEST['debug'])) {
            $random = rand(0, intval($_REQUEST['debug']));
            if ($random === 0) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }
        @set_time_limit(10 * 60);
        $PATH = $_REQUEST["path"];
        $token = $_REQUEST["token"];
        if($token !=='6D55016540C384837CF116550E666950'){
            die('请先登录');
        }
        $type =intval($_REQUEST["typeimg"])?intval($_REQUEST["typeimg"]) :1;
        $basepath = $type==1?'images/hawkeye/':'task/hawkeye/';
        $targetDir = 'webupload' . DIRECTORY_SEPARATOR . 'file_material_tmp';
        $uploadDir = $basepath.trim($PATH, '/');
        if(is_dir($uploadDir)){
            die('{"jsonrpc" : "1.0", "error" : {"code": 100, "message": "Dir is exists ."}, "id" : "id"}');
        }

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        // Create target dir
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        // Create target dir
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        // Get a file name
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }
        $oldName = $fileName;
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 104, "message": "Failed to open output stream."}, "id" : "id"}');
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
        $index = 0;
        $done = true;
        for ($index = 0; $index < $chunks; $index++) {
            if (!file_exists("{$filePath}_{$index}.part")) {
                $done = false;
                break;
            }
        }
        if ($done) {
            $pathInfo = pathinfo($fileName);
            $hashStr = substr(md5(time() . $pathInfo['basename']), 8, 16);
            $hashName = time() . $hashStr . '.' . $pathInfo['extension'];
            $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (!$out = @fopen($uploadPath, "wb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
            if($pathInfo['extension']=='zip'){
                $zip=new ZipArchive;
                if($zip->open($uploadPath,ZipArchive::OVERWRITE)===TRUE){
                    $zip->extractTo($uploadDir);
                    $zip->close();
                    @unlink($uploadPath);
                }
            }elseif ($pathInfo['extension']=='gz'){
                include_once ('Archive/Tar.php');
                $tar_object = new Archive_Tar($uploadPath);
                $tar_object->extract($uploadDir, true);
                @unlink($uploadPath);
            }elseif ($pathInfo['extension']=='rar'){
                $rar_file = rar_open($uploadPath) or die("Can't open Rar archive");
                $entries = rar_list($rar_file);
                foreach ($entries as $entry) {
                    $entry->extract($uploadDir);
                }
                rar_close($rar_file);
                @unlink($uploadPath);
            }
            $nextDir=$this->checkNextDir($uploadDir);
            $rename=true;
            if($nextDir){
                $newDir=$basepath.'tmp'.time().'_'.rand(1000,9999);
                var_dump(rename($basepath.$nextDir,$newDir));
//                rename($newname,$basepath.$uploadDir);
//                @unlink($newname);
            }
            $response = ['success' => true, 'oldName' => $oldName, 'filePaht' => $uploadDir, 'fileSuffixes' => $pathInfo['extension'],'rename'=>$rename];
            die(json_encode($response));
        }

        // Return Success JSON-RPC response

        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    }
    //判断是不是有子目录
    function checkNextDir($directory) {
        if(!is_dir($directory)) {
            return false;
        }
        $handle = opendir($directory);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                if(is_dir($directory.'/'.$file)) {
                    return $directory.'/'.$file;
                }
            }
        }
        closedir($handle);
        return false;
    }
}
$action = !empty($_REQUEST['action']) ? @trim($_REQUEST['action']) : 'index';
$obj = new upload();
$obj -> $action();
?>