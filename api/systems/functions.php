<?php

function validate($data, $validasi, $custom = [])
{
    if (!empty($custom)) {
        $validasiData = array_merge($validasi, $custom);
    } else {
        $validasiData = $validasi;
    }

    $validate = GUMP::is_valid($data, $validasiData);

    if ($validate === true) {
        return true;
    } else {
        return $validate;
    }
}
function get_images($html)
{
    preg_match_all('@src="([^"]+)"@' , $html, $match);

    $src = array_pop($match);

    return $src;
}

function get_iframe($html)
{

    $result = preg_match_all('/<img.*?src\s*=.*?>/', $html, $matches, PREG_SET_ORDER);

    for ($i=0; $i < count($matches); $i++) {

        $img = $matches[$i][0];

        $a = get_youtube($img);
        $iframe = '<iframe width="'.getenv('youtube_width').'" height="'.getenv('youtube_height').'" src="'.$a[0].'">
        </iframe>';
        $yutub = str_replace($img, $iframe , $img);

        $konten = str_replace($img, $yutub, $html);

        $html = $konten;
    }
   return $html;
}

function get_youtube($img)
{
    preg_match_all( '@ta-insert-video="([^"]+)"@' , $img, $match);

    $src = array_pop($match);

    return $src;
}


function base64_to_jpeg($base64_string) {
    // open the output file for writing
    $output_file = null;
    $ifp = fopen( $output_file, 'wb' );

    // split the string on commas
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = explode( ',', $base64_string );

    // we could add validation here with ensuring count( $data ) > 1
    fwrite( $ifp, base64_decode( $data[ 1 ] ) );

    // clean up the file resource
    fclose( $ifp );

    return $output_file;
}

function imgUrl($custom = NULL){
  $url = getenv('SITE_IMG');
  if (isset($custom)) {
    return $url.$custom;
  } else {
    return $url;
  }
}
