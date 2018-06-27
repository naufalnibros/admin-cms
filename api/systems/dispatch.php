<?php

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
    error(500, 'dispatch requires at least PHP 5.3 to run.');
}

function _log($message) {
    if (config('debug.enable') == true && php_sapi_name() !== 'cli') {
        $file = config('debug.log');
        $type = $file ? 3 : 0;
        error_log("{$message}\n", $type, $file);
    }
}

function site_url() {

    if (config('site.url') == null)
        error(500, '[site.url] is not set');

    // Forcing the forward slash
    return rtrim(config('site.url'), '/') . '/';
}

function site_path() {
    static $_path;

    if (config('site.url') == null)
        error(500, '[site.url] is not set');

    if (!$_path)
        $_path = rtrim(parse_url(config('site.url'), PHP_URL_PATH), '/');

    return $_path;
}

function urlElement($url) {
    echo '<url>' . PHP_EOL .
     '<loc>' . $url . '</loc>' . PHP_EOL .
    '<changefreq>weekly</changefreq>' . PHP_EOL .
    '</url>' . PHP_EOL;
}

function img_url() {

    if (config('site.img') == null)
        error(500, '[site.img] is not set');

    // Forcing the forward slash
    return rtrim(config('site.img'), '/') . '/';
}

function img_path() {
    if (config('site.img') == null)
        error(500, '[img.path] is not set');

    // Forcing the forward slash
    return rtrim(config('img.path'), '/') . '/';
}

function error($code, $message) {
    @header("HTTP/1.0 {$code} {$message}", true, $code);
    die($message);
}

function config($key, $value = null) {

    static $_config = array();

    if ($key === 'source' && file_exists($value))
        $_config = parse_ini_file($value, true);
    else if ($value == null)
        return (isset($_config[$key]) ? $_config[$key] : null);
    else
        $_config[$key] = $value;
}

function to_b64($str) {
    $str = base64_encode($str);
    $str = preg_replace('/\//', '_', $str);
    $str = preg_replace('/\+/', '.', $str);
    $str = preg_replace('/\=/', '-', $str);
    return trim($str, '-');
}

function from_b64($str) {
    $str = preg_replace('/\_/', '/', $str);
    $str = preg_replace('/\./', '+', $str);
    $str = preg_replace('/\-/', '=', $str);
    $str = base64_decode($str);
    return $str;
}

if (extension_loaded('mcrypt')) {

    function encrypt($decoded, $algo = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC) {

        if (($secret = config('cookies.secret')) == null)
            error(500, '[cookies.secret] is not set');

        $secret = mb_substr($secret, 0, mcrypt_get_key_size($algo, $mode));
        $iv_size = mcrypt_get_iv_size($algo, $mode);
        $iv_code = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);
        $encrypted = to_b64(mcrypt_encrypt($algo, $secret, $decoded, $mode, $iv_code));

        return sprintf('%s|%s', $encrypted, to_b64($iv_code));
    }

    function decrypt($encoded, $algo = MCRYPT_RIJNDAEL_256, $mode = MCRYPT_MODE_CBC) {

        if (($secret = config('cookies.secret')) == null)
            error(500, '[cookies.secret] is not set');

        $secret = mb_substr($secret, 0, mcrypt_get_key_size($algo, $mode));
        list($enc_str, $iv_code) = explode('|', $encoded);
        $enc_str = from_b64($enc_str);
        $iv_code = from_b64($iv_code);
        $enc_str = mcrypt_decrypt($algo, $secret, $enc_str, $mode, $iv_code);

        return rtrim($enc_str, "\0");
    }

}

function set_cookie($name, $value, $expire = 31536000, $path = '/') {
    $value = (function_exists('encrypt') ? encrypt($value) : $value);
    setcookie($name, $value, time() + $expire, $path);
}

function get_cookie($name) {

    $value = from($_COOKIE, $name);

    if ($value)
        $value = (function_exists('decrypt') ? decrypt($value) : $value);

    return $value;
}

function delete_cookie() {
    $cookies = func_get_args();
    foreach ($cookies as $ck)
        setcookie($ck, '', -10, '/');
}

// if we have APC loaded, enable cache functions
if (extension_loaded('apc')) {

    function cache($key, $func, $ttl = 0) {
        if (($data = apc_fetch($key)) === false) {
            $data = call_user_func($func);
            if ($data !== null) {
                apc_store($key, $data, $ttl);
            }
        }
        return $data;
    }

    function cache_invalidate() {
        foreach (func_get_args() as $key) {
            apc_delete($key);
        }
    }

}

function warn($name = null, $message = null) {

    static $warnings = array();

    if ($name == '*')
        return $warnings;

    if (!$name)
        return count(array_keys($warnings));

    if (!$message)
        return isset($warnings[$name]) ? $warnings[$name] : null;

    $warnings[$name] = $message;
}

function _u($str) {
    return urlencode($str);
}

function _h($str, $enc = 'UTF-8', $flags = ENT_QUOTES) {
    return htmlentities($str, $flags, $enc);
}

function from($source, $name) {
    if (is_array($name)) {
        $data = array();
        foreach ($name as $k)
            $data[$k] = isset($source[$k]) ? $source[$k] : null;
        return $data;
    }
    return isset($source[$name]) ? $source[$name] : null;
}

function stash($name, $value = null) {

    static $_stash = array();

    if ($value === null)
        return isset($_stash[$name]) ? $_stash[$name] : null;

    $_stash[$name] = $value;

    return $value;
}

function method($verb = null) {

    if ($verb == null || (strtoupper($verb) == strtoupper($_SERVER['REQUEST_METHOD'])))
        return strtoupper($_SERVER['REQUEST_METHOD']);

    error(400, 'bad request');
}

function client_ip() {

    if (isset($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];

    return $_SERVER['REMOTE_ADDR'];
}

function redirect(/* $code_or_path, $path_or_cond, $cond */) {

    $argv = func_get_args();
    $argc = count($argv);

    $path = null;
    $code = 302;
    $cond = true;

    switch ($argc) {
        case 3:
            list($code, $path, $cond) = $argv;
            break;
        case 2:
            if (is_string($argv[0]) ? $argv[0] : $argv[1]) {
                $code = 302;
                $path = $argv[0];
                $cond = $argv[1];
            } else {
                $code = $argv[0];
                $path = $argv[1];
            }
            break;
        case 1:
            if (!is_string($argv[0]))
                error(500, 'bad call to redirect()');
            $path = $argv[0];
            break;
        default:
            error(500, 'bad call to redirect()');
    }

    $cond = (is_callable($cond) ? !!call_user_func($cond) : !!$cond);

    if (!$cond)
        return;

    header('Location: ' . $path, true, $code);
    exit;
}

function partial($view, $locals = null) {

    if (is_array($locals) && count($locals)) {
        extract($locals, EXTR_SKIP);
    }

//  if (($view_root = config('views.root')) == null)
//    error(500, "[views.root] is not set");

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

function render($view, $locals = null, $layout = null) {

    if (is_array($locals) && count($locals)) {
        extract($locals, EXTR_SKIP);
    }

//  if (($view_root = config('views.root')) == null)
//    error(500, "[views.root] is not set");

    ob_start();
    include "views/{$view}.php";
    content(trim(ob_get_clean()));

    if ($layout !== false) {

        if ($layout == null) {
            $layout = ($layout == null) ? 'mainSingle' : $layout;
        }

        $layout = "views/layout/{$layout}.php";

        header('Content-type: text/html; charset=utf-8');

        ob_start();
        require $layout;
        echo trim(ob_get_clean());
    } else {
        echo content();
    }
}

function json($obj, $code = 200) {
    header('Content-type: application/json', true, $code);
    echo json_encode($obj);
    exit;
}

function condition() {

    static $cb_map = array();

    $argv = func_get_args();
    $argc = count($argv);

    if (!$argc)
        error(500, 'bad call to condition()');

    $name = array_shift($argv);
    $argc = $argc - 1;

    if (!$argc && is_callable($cb_map[$name]))
        return call_user_func($cb_map[$name]);

    if (is_callable($argv[0]))
        return ($cb_map[$name] = $argv[0]);

    if (is_callable($cb_map[$name]))
        return call_user_func_array($cb_map[$name], $argv);

    error(500, 'condition [' . $name . '] is undefined');
}

function middleware($cb_or_path = null) {

    static $cb_map = array();

    if ($cb_or_path == null || is_string($cb_or_path)) {
        foreach ($cb_map as $cb) {
            call_user_func($cb, $cb_or_path);
        }
    } else {
        array_push($cb_map, $cb_or_path);
    }
}

function filter($sym, $cb_or_val = null) {

    static $cb_map = array();

    if (is_callable($cb_or_val)) {
        $cb_map[$sym] = $cb_or_val;
        return;
    }

    if (is_array($sym) && count($sym) > 0) {
        foreach ($sym as $s) {
            $s = substr($s, 1);
            if (isset($cb_map[$s]) && isset($cb_or_val[$s]))
                call_user_func($cb_map[$s], $cb_or_val[$s]);
        }
        return;
    }

    error(500, 'bad call to filter()');
}

function route_to_regex($route) {
    $route = preg_replace_callback('@:[\w]+@i', function ($matches) {
        $token = str_replace(':', '', $matches[0]);
        return '(?P<' . $token . '>[a-z0-9_\0-\.]+)';
    }, $route);
    return '@^' . rtrim($route, '/') . '$@i';
}

function route($method, $pattern, $callback = null) {

    // callback map by request type
    static $route_map = array(
        'GET' => array(),
        'POST' => array(),
        'DELETE' => array()
    );

    $method = strtoupper($method);

    if (!in_array($method, array('GET', 'POST', 'DELETE')))
        error(500, 'Only GET and POST are supported');

    // a callback was passed, so we create a route defiition
    if ($callback !== null) {

        // create a route entry for this pattern
        $route_map[$method][$pattern] = array(
            'xp' => route_to_regex($pattern),
            'cb' => $callback
        );
    } else {


        // callback is null, so this is a route invokation. look up the callback.
        foreach ($route_map[$method] as $pat => $obj) {

            // if the requested uri ($pat) has a matching route, let's invoke the cb
            if (!preg_match($obj['xp'], $pattern, $vals))
                continue;

            // call middleware
            middleware($pattern);

            // construct the params for the callback
            array_shift($vals);
            preg_match_all('@:([\w]+)@', $pat, $keys, PREG_PATTERN_ORDER);
            $keys = array_shift($keys);
            $argv = array();

            foreach ($keys as $index => $id) {
                $id = substr($id, 1);
                if (isset($vals[$id])) {
                    array_push($argv, trim(urldecode($vals[$id])));
                }
            }

            // call filters if we have symbols
            if (count($keys)) {
                filter(array_values($keys), $vals);
            }

            // if cb found, invoke it
            if (is_callable($obj['cb'])) {
                call_user_func_array($obj['cb'], $argv);
            }

            // leave after first match
            break;
        }
    }
}

function get($path, $cb) {
    route('GET', $path, $cb);
}

function post($path, $cb) {
    route('POST', $path, $cb);
}

function del($path, $cb) {
    route('DELETE', $path, $cb);
}

function flash($key, $msg = null, $now = false) {

    static $x = array(),
    $f = null;

    $f = (config('cookies.flash') ? config('cookies.flash') : '_F');

    if ($c = get_cookie($f))
        $c = json_decode($c, true);
    else
        $c = array();

    if ($msg == null) {

        if (isset($c[$key])) {
            $x[$key] = $c[$key];
            unset($c[$key]);
            set_cookie($f, json_encode($c));
        }

        return (isset($x[$key]) ? $x[$key] : null);
    }

    if (!$now) {
        $c[$key] = $msg;
        set_cookie($f, json_encode($c));
    }

    $x[$key] = $msg;
}

function dispatch() {

    $path = $_SERVER['REQUEST_URI'];

    if (config('site.url') !== null)
        $path = preg_replace('@^' . preg_quote(site_path()) . '@', '', $path);

    $parts = preg_split('/\?/', $path, -1, PREG_SPLIT_NO_EMPTY);

    $uri = trim($parts[0], '/');

    if ($uri == 'index.php' || $uri == '') {
        $uri = 'index';
    }

    return $uri;
}

function url($url = '') {
    if (isset($url) and empty($url))
        return site_url();
    else
        return site_url() . $url . '.html';
}

function normalizeChars() {
    return array(
        'ï¿½' => 'S', 'ï¿½' => 's', 'ï¿½' => 'Dj', 'ï¿½' => 'Z', 'ï¿½' => 'z', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A',
        'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'C', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'E', 'ï¿½' => 'I', 'ï¿½' => 'I', 'ï¿½' => 'I',
        'ï¿½' => 'I', 'ï¿½' => 'N', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'U', 'ï¿½' => 'U',
        'ï¿½' => 'U', 'ï¿½' => 'U', 'ï¿½' => 'Y', 'ï¿½' => 'B', 'ï¿½' => 'Ss', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a',
        'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'c', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'e', 'ï¿½' => 'i', 'ï¿½' => 'i', 'ï¿½' => 'i',
        'ï¿½' => 'i', 'ï¿½' => 'o', 'ï¿½' => 'n', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'u',
        'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½' => 'y', 'ï¿½' => 'y', 'ï¿½' => 'b', 'ï¿½' => 'y', 'ï¿½' => 'f'
    );
}

function urlParsing($string) {
    $arrDash = array("--", "---", "----", "-----");
    $string = strtolower(trim($string));
    $string = strtr($string, normalizeChars());
    $string = preg_replace('/[^a-zA-Z0-9 -.]/', '', $string);
    $string = str_replace(" ", "-", $string);
    $string = str_replace("&", "", $string);
    $string = str_replace("+", "plus", $string);
    $string = str_replace(array("'", "\"", "&quot;"), "", $string);
    $string = str_replace($arrDash, "-", $string);
    return str_replace($arrDash, "-", $string);
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

function createImg($path, $filename,$id, $proporsional = false) {
    $newFileName = urlParsing($filename);
    $small = img_path() . $path . $id . '-150x150-' . $newFileName;
    $big = img_path() . $path . $id . '-700x700-' . $newFileName;
    //delete file, if any
    if (file_exists($small))
        unlink($small);
    if (file_exists($big))
        unlink($big);

    $file = img_path() . $path . $filename;

    smart_resize_image($file, $small, 150, 150, $proporsional, 80);
    smart_resize_image($file, $big, 700, 700, $proporsional, 80);

    unlink(img_path() . $path . $filename);
}

function jmlproduct($id) {
    $sql = new LandaDb();
    $sql->select("*")->from("product")->where("=", "product_category_id", $id)->andWhere("=", 'is_deleted', 0)->andWhere(">", "stock", 0);
    $models = $sql->findAll();
    $totalItems = $sql->count();
    return $totalItems;
}

function smart_resize_image($file, $newName, $width = 0, $height = 0, $proportional = false, $quality = 100) {
    $output = 'file';

    if ($height <= 0 && $width <= 0)
        return false;
    if ($file === null)
        return false;

    # Setting defaults and meta
    $info = getimagesize($file);
    $image = '';
    $final_width = 0;
    $final_height = 0;
    list($width_old, $height_old) = $info;
    $cropHeight = $cropWidth = 0;

    # Calculating proportionality
    if ($proportional) {
        if ($width == 0)
            $factor = $height / $height_old;
        elseif ($height == 0)
            $factor = $width / $width_old;
        else
            $factor = min($width / $width_old, $height / $height_old);
        $final_width = round($width_old * $factor);
        $final_height = round($height_old * $factor);
    }
    else {
        $final_width = ( $width <= 0 ) ? $width_old : $width;
        $final_height = ( $height <= 0 ) ? $height_old : $height;
        $widthX = $width_old / $width;
        $heightX = $height_old / $height;

        $x = min($widthX, $heightX);
        $cropWidth = ($width_old - $width * $x) / 2;
        $cropHeight = ($height_old - $height * $x) / 2;
    }

    # Loading image to memory according to type
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_GIF: $image = imagecreatefromgif($file);
            break;
        case IMAGETYPE_PNG: $image = imagecreatefrompng($file);
            break;
        default: return false;
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
            $output = NULL;
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
        case IMAGETYPE_GIF: imagegif($image_resized, $output);
            break;
        case IMAGETYPE_JPEG: imagejpeg($image_resized, $output, $quality);
            break;
        case IMAGETYPE_PNG:
            $quality = 9 - (int) ((0.9 * $quality) / 10.0);
            imagepng($image_resized, $output, $quality);
            break;
        default: return false;
    }
    return true;
}

function deleteImg($folder, $id, $fileName) {
    $small = img_path() . $folder . $id . '-150x150-' . $fileName;
    $medium = img_path() . $folder . $id . '-350x350-' . $fileName;
    $big = img_path() . $folder . $id . '-700x700-' . $fileName;
    //delete file, if any
    if (file_exists($small))
        unlink($small);
    if (file_exists($medium))
        unlink($medium);
    if (file_exists($big))
        unlink($big);
}

function go($url) {
    echo '<script>window.location = "' . $url . '";</script>';
}

// The not found error
function not_found() {
    error(404, render('404', null, false));
}

function access_denied() {
    error(401, render('401', null, false));
}

function addOrUpdateUrlParam($name, $value) {
    $params = $_GET;
    unset($params[$name]);
    $params[$name] = $value;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);
}

function pagination($item_count, $limit, $cur_page, $link) {
    if ($limit > 0 && $item_count > 0) {
        $page_count = ceil($item_count / $limit);
        $paginate = '<div class="col-md-12 col-sm-12" style="text-align:center"><ul class="pagination">';
        if ($page_count != 1) {
            if ($cur_page == 1) {
                $paginate .= '<li><a>First</a></li>';
            } else {
                $url = $link . addOrUpdateUrlParam('page', 1);
                $paginate .= '<li><a href="' . urldecode($url) . '">First</a></li>';
            }

            if ($cur_page > 1) {
                $url = $link . addOrUpdateUrlParam('page', $cur_page - 1);
                $paginate .= '<li><a href="' . urldecode($url) . '">Prev</a></li>';
            }

            if ($cur_page > 3) {
                $start = $cur_page - 2;
//            $end = ($cur_page == $page_count) ? $page_count : $cur_page + 2;
                $end = ($cur_page == $page_count) ? $page_count : (($page_count - $cur_page) + $cur_page);
            } else {
                $start = 1;
                $end = (($cur_page + 4) <= $page_count) ? ($cur_page + 4) : $page_count;
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $cur_page)
                    $active = 'class="active"';
                else
                    $active = '';

                $url = $link . addOrUpdateUrlParam('page', $i);

                $paginate .= '<li ' . $active . '><a href="' . urldecode($url) . '">' . $i . '</a></li>';
            }


            $url = $link . addOrUpdateUrlParam('page', $cur_page + 1);
            if ($cur_page != $page_count) {
                $paginate .= '<li><a href="' . urldecode($url) . '">Next</a></li>';
            }

            if ($cur_page == $page_count) {
                $paginate .= '<li><a>Last</a></li>';
            } else {
                $url = $link . addOrUpdateUrlParam('page', $page_count);
                $paginate .= '<li><a href="' . urldecode($url) . '">Last</a></li>';
            }


            $paginate .= '</ul></div>';
        } else {
            $paginate = '';
        }
        return $paginate;
    }
}

?>
