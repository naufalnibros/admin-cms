<?php

function site_url() {
    return rtrim(getenv('SITE_URL'), '/') . '/';
}

function site_path() {
    static $_path;

    if (!$_path) {
        $_path = rtrim(parse_url(getenv('SITE_URL'), PHP_URL_PATH), '/');
    }

    return $_path;
}

function img_url() {
    return rtrim(getenv('SITE_URL'), '/') . '/';
}

function img_path() {
    return rtrim(getenv('IMG_PATH'), '/') . '/';
}

function dispatch() {
    $path = $_SERVER['REQUEST_URI'];

    if (getenv('SITE_URL') !== null) {
        $path = preg_replace('@^' . preg_quote(site_path()) . '@', '', $path);
    }

    $parts = preg_split('/\?/', $path, -1, PREG_SPLIT_NO_EMPTY);

    $uri = trim($parts[0], '/');

    if ($uri == 'index.php' || $uri == '') {
        $uri = 'site';
    }

    return $uri;
}

function getUrlFile() {
    $uri = dispatch();
    $getUri = explode("/", $uri);

    if ($getUri[0] == 'api') {
        $file = 'routes/' . (isset($getUri[1]) ? $getUri[1] : 'sites') . '.php';

        if (file_exists($file)) {
            return $file;
        }
    } else {

        $file = 'routes/' . $getUri[0] . '.php';

        if (file_exists($file)) {
            return $file;
        }
    }

    return 'routes/sites.php';
}

function successResponse($response, $message) {
    return $response->withJson([
                'status_code' => 200,
                'data' => $message,
                    ], 200);
}

function unprocessResponse($response, $message) {
    return $response->withJson([
                'status_code' => 422,
                'errors' => $message,
                    ], 422);
}

function unauthorizedResponse($response, $message) {
    return $response->withJson([
                'status_code' => 403,
                'errors' => $message,
                    ], 403);
}

function partial($view, $locals = null) {

    if (is_array($locals) && count($locals)) {
        extract($locals, EXTR_SKIP);
    }

    $path = basename($view);
    $view = preg_replace('/' . $path . '$/', "_{$path}", $view);
    $view = "views/{$view}.php";

    if (file_exists($view)) {
        ob_start();
        require $view;
        return ob_get_clean();
    } else {
        error(500, "partial [{$view}] not found");
    }

    return '';
}


function content($value = null) {
    return stash('$content$', $value);
}
function get_img($file , $url, $a)
{
    $name = $a . '.' . 'jpg';
    $fileName = $url . $name;
    file_put_contents($fileName,file_get_contents($file));
    // var_dump($name);exit();
    return $name;
}

function render($view, $locals = null, $layout = null) {

    if (is_array($locals) && count($locals)) {
        extract($locals, EXTR_SKIP);
    }

    ob_start();
    include "public/views/{$view}.php";
    content(trim(ob_get_clean()));

    //    if ($layout !== false) {
    //
    //        if ($layout == null) {
    //            $layout = ($layout == null) ? 'mainSingle' : $layout;
    //        }
    //
    //        $layout = "views/layout/{$layout}.php";
    //
    //        header('Content-type: text/html; charset=utf-8');
    //
    //        ob_start();
    //        require $layout;
    //        echo trim(ob_get_clean());
    //    } else {
            echo content();
    //    }
}

function stash($name, $value = null) {

    static $_stash = array();

    if ($value === null)
        return isset($_stash[$name]) ? $_stash[$name] : null;

    $_stash[$name] = $value;

    return $value;
}

function base64ToFile($base64, $path, $custom_name = null) {
    if (isset($base64['base64'])) {
        $extension = substr($base64['filename'], strrpos($base64['filename'], ",") + 1);

        if (!empty($custom_name)) {
            $nama = $custom_name . '.jpg';
        } else {
            $nama = substr($base64['filename'], 6).$base64['filename'];
        }

        $file = base64_decode($base64['base64']);
        file_put_contents($path . '/' . $nama, $file);

        return [
            'status' => 1,
            'fileName' => $nama,
            'filePath' => $path . '/' . $nama,
        ];
    } else {
        return [
            'status' => 0,
            'fileName' => '',
            'filePath' => '',
        ];
    }
}

function urlParsing($string) {
    $arrDash = array("--", "---", "----", "-----");
    $string = strtolower(trim($string));
    $string = strtr($string, normalizeChars());
    $string = preg_replace('/[^a-zA-Z0-9 -.]/', '', $string);
    $string = str_replace(" ", "-", $string);
    $string = str_replace("&", "", $string);
    $string = str_replace(array("'", "\"", "&quot;"), "", $string);
    $string = str_replace($arrDash, "-", $string);
    return str_replace($arrDash, "-", $string);
}

function contentParsing($string) {
    $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
return preg_replace($regex, '$1', $string);
}

function normalizeChars() {
    return array(
        'ï¿½' => 'S', 'ï¿½' => 's', 'ï¿½' => 'Dj', 'ï¿½' => 'Z', 'ï¿½' => 'z', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A',
        'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'C', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'I', 'ï¿½' => 'I', 'ï¿½' => 'I',
        'ï¿½' => 'I', 'ï¿½' => 'N', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'U', 'ï¿½' => 'U',
        'ï¿½' => 'U', 'ï¿½' => 'U', 'ï¿½' => 'Y', 'ï¿½' => 'B', 'ï¿½' => 'Ss', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a',
        'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'c', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'i', 'ï¿½' => 'i', 'ï¿½' => 'i',
        'ï¿½' => 'i', 'ï¿½' => 'o', 'ï¿½' => 'n', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'u',
        'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½' => 'y', 'ï¿½' => 'y', 'ï¿½' => 'b', 'ï¿½' => 'y', 'ï¿½' => 'f',
    );
}

//function urlParsing($string) {
//    $arrDash = array("--", "---", "----", "-----");
//    $string = strtolower(trim($string));
//    $string = strtr($string, normalizeChars());
//    $string = preg_replace('/[^a-zA-Z0-9 -.]/', '', $string);
//    $string = str_replace(" ", "-", $string);
//    $string = str_replace("&", "", $string);
//    $string = str_replace(array("'", "\"", "&quot;"), "", $string);
//    $string = str_replace($arrDash, "-", $string);
//    return str_replace($arrDash, "-", $string);
//}
function is_base64($base64) {
    $data = $base64;
    $data = str_replace(" ", "+", $data);
    $data = str_replace(":=", "==", $data);

    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    list($header, $ext) = explode("/", $type);
    if (isset($data) and base64_decode($data)) {
        return true;
    } else {
        return false;
    }
}

function base64toImg($base64, $path, $name = null) {
    $data = $base64;
    $data = str_replace(" ", "+", $data);
    $data = str_replace(":=", "==", $data);

    if (!empty($data) and is_base64($data)) {
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        list($header, $ext) = explode("/", $type);

        $data = base64_decode($data);
        if (empty($name)) {
            $name = date("ymdis");
        }

        $allowExt = array('PNG', 'JPG', 'jpg', 'png', 'JPEG', 'jpeg');
        if (in_array($ext, $allowExt)) {
            $fileName = $name . '.' . $ext;
            file_put_contents($path . $fileName, $data);
            return [
                'status' => true,
                'data' => $fileName,
            ];
        } else {
            return [
                'status' => false,
                'data' => ['ekstensi gambar harus JPG atau PNG'],
            ];
        }
    } else {
        return [
            'status' => false,
            'data' => $data,
        ];
    }
}

function rp($price = 0, $prefix = true, $decimal = 0) {
    if ($price === '-' || empty($price)) {
        return '';
    } else {
        if ($prefix === "-") {
            return $price;
        } else {
            $rp = ($prefix) ? 'Rp. ' : '';

            if ($price < 0) {
                $price = (float) $price * -1;
                $result = '(' . $rp . number_format($price, $decimal, ",", ".") . ')';
            } else {
                $price = (float) $price;
                $result = $rp . number_format($price, $decimal, ",", ".");
            }
            return $result;
        }
    }
}

/* IMAGE */

function createImg($path, $filename, $id, $proporsional = false) {
    $newFileName = urlParsing($filename);
    $small = $path . $id . '-150x150-' . $newFileName;
    $medium = $path . $id . '-350x350-' . $newFileName;
    $big = $path . $id . '-700x700-' . $newFileName;

    if (file_exists($small)) {
        unlink($small);
    }

    if (file_exists($medium)) {
        unlink($medium);
    }

    if (file_exists($big)) {
        unlink($big);
    }

    $file = $path . $filename;

    smart_resize_image($file, $small, getenv('resize_image_width_small'), getenv('resize_image_width_small'), $proporsional, 90);
    smart_resize_image($file, $medium, getenv('resize_image_width_medium'), getenv('resize_image_width_medium'), $proporsional, 90);
    smart_resize_image($file, $big, getenv('resize_image_width_big'), getenv('resize_image_width_big'), $proporsional, 90);

    unlink($path . $filename);

    return array('big' => $id . '-700x700-' . $newFileName,'medium' => $id . '-350x350-' . $newFileName, 'small' => $id . '-150x150-' . $newFileName);
}

function createImags($path, $filename, $id, $proporsional = false, $i) {
    $newFileName = urlParsing($filename);
    $big = $path . $id .'pp_'. $newFileName;
    $small = $path . $id . 'tmp_' . $newFileName;
    
  
    if (file_exists($small)) {
        unlink($small);
    }

    if (file_exists($big)) {
        unlink($big);
    }

    $file = $path . $filename;

   if ($i == 0) {
       smart_resize_image($file, $small, getenv('resize_image_width_small'), getenv('resize_image_height_small'), false , 90);
   }

    
    smart_resize_image($file, $big, getenv('resize_image_width_big'), getenv('resize_image_height_big'), $proporsional, 90);

    unlink($path . $filename);

    return array('big' => $id .'pp_'. $newFileName,'small' => $id . 'tmp_' . $newFileName);
}

function createImageThumb($path, $filename, $id, $proporsional = false) {
    $newFileName = urlParsing($filename);

    $small = $path . $id . '-thumb-' . $newFileName;

    if (file_exists($small)) {
        unlink($small);
    }

    $file = $path . $filename;

    smart_resize_image($file, $small, getenv('resize_image_width_small'), getenv('resize_image_height_small'), false , 90);

    unlink($path . $filename);

    return array('small' => $id . '-thumb-' . $newFileName);
}

function createImageCopy($path, $filename, $id, $proporsional = false) {
    $newFileName = urlParsing($filename);
    $big = $path . $id .'pp_'. $newFileName;
    $small = $path . $id . 'tmp_' . $newFileName;

   if (file_exists($small)) {
        unlink($small);
    }

    if (file_exists($big)) {
        unlink($big);
    }

    $file = $path . $filename;
    // var_dump($small);exit();
    smart_resize_image($file, $small, getenv('resize_image_width_small'), getenv('resize_image_height_small'), false , 90);  

    smart_resize_image($file, $big, getenv('resize_image_width_big'), getenv('resize_image_height_big'), $proporsional, 90);

    unlink($path . $filename);
    return array('big' => $id .'pp_'. $newFileName,'small' => $id . 'tmp_' . $newFileName);
}

function smart_resize_image($file, $newName, $width = 0, $height = 0, $proportional = false, $quality = 100) {
    $output = 'file';

    if ($height <= 0 && $width <= 0) {
        return false;
    }

    if ($file === null) {
        return false;
    }

    # Setting defaults and meta
    $info = getimagesize($file);
    $image = '';
    $final_width = 0;
    $final_height = 0;
    list($width_old, $height_old) = $info;
    $cropHeight = $cropWidth = 0;

    # Calculating proportionality
    if ($proportional) {
        if ($width == 0) {
            $factor = $height / $height_old;
        } elseif ($height == 0) {
            $factor = $width / $width_old;
        } else {
            $factor = min($width / $width_old, $height / $height_old);
        }

        $final_width = round($width_old * $factor);
        $final_height = round($height_old * $factor);
    } else {
        $final_width = ($width <= 0) ? $width_old : $width;
        $final_height = ($height <= 0) ? $height_old : $height;
        $widthX = $width_old / $width;
        $heightX = $height_old / $height;

        $x = min($widthX, $heightX);
        $cropWidth = ($width_old - $width * $x) / 2;
        $cropHeight = ($height_old - $height * $x) / 2;
    }

    # Loading image to memory according to type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:$image = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_GIF:$image = imagecreatefromgif($file);
            break;
        case IMAGETYPE_PNG:$image = imagecreatefrompng($file);
            break;
        default:return false;
    }

    # This is the resizing/resampling/transparency-preserving magic
    $image_resized = imagecreatetruecolor($final_width, $final_height);
    if (($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG)) {
        $transparency = imagecolortransparent($image);
        $palletsize = imagecolorstotal($image);
        if ($transparency >= 0 && $transparency < $palletsize) {
            $transparent_color = imagecolorsforindex($image, $transparency);
            $transparency = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
            imagefill($image_resized, 0, 0, $transparency);
            imagecolortransparent($image_resized, $transparency);
        } elseif ($info[2] == IMAGETYPE_PNG) {
            imagealphablending($image_resized, false);
            $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
            imagefill($image_resized, 0, 0, $color);
            imagesavealpha($image_resized, true);
        }
    }
    imagecopyresampled($image_resized, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $width_old - 2 * $cropWidth, $height_old - 2 * $cropHeight);

    # Taking care of original, if needed
    //        if ($delete_original) {
    //            if ($use_linux_commands)
    //                exec('rm ' . $file);
    //            else
    //        unlink($file);
    //        }
    # Preparing a method of providing result
    switch (strtolower($output)) {
        case 'browser':
            $mime = image_type_to_mime_type($info[2]);
            header("Content-type: $mime");
            $output = null;
            break;
        case 'file':
            $output = $newName;
            break;
        case 'return':
            return $image_resized;
            break;
        default:
            break;
    }

    # Writing image according to type to the output destination and image quality
    switch ($info[2]) {
        case IMAGETYPE_GIF:imagegif($image_resized, $output);
            break;
        case IMAGETYPE_JPEG:imagejpeg($image_resized, $output, $quality);
            break;
        case IMAGETYPE_PNG:
            $quality = 9 - (int) ((0.9 * $quality) / 10.0);
            imagepng($image_resized, $output, $quality);
            break;
        default:return false;
    }
    return true;
}
