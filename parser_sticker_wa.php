<?php

$folder = (isset($_GET['folder'])) ? $_GET['folder'] : "";
$folder = str_replace("%20", " ", $folder);

if (empty($folder)) {
    $files = scandir(".");
    rsort($files);

    foreach ($files as $key => $value) {
        if (!in_array($value, [".", "..", ".git"])) {

            if (is_dir("$value")) {
                echo "<a href='?folder=$value'>$value</a><br>";
            }
        }
    }
} else {
    echo "<a href='?'>Back</a>";
    echo "<h1>$folder</h1>";

    if (file_exists("$folder/contents.json")) {
        $json = file_get_contents("$folder/contents.json");
        $json_data = json_decode($json, true);
        foreach ($json_data['sticker_packs'] as $data) {
            echo "<br> " . $data['identifier'];
            $path = $folder . "/" . $data['identifier'];
            $title = $data['name'];
            createTitleTxt($path, $title);
            cekAnimated($path);
        }
    } else {
        $files = scandir($folder);
        rsort($files);
        foreach ($files as $key => $value) {
            $path = $folder . "/" . $value;
            if (!in_array($value, [".", ".."])) {
                if (is_dir($path)) {
                    echo "<br> $value";
                    if (file_exists("$path/contents.json")) {
                        $json = file_get_contents("$path/contents.json");
                        $json_data = json_decode($json, true);
                        $data = $json_data[0];
                        $title = $data['name'];
                        createTitleTxt($path, $title);
                    }
                    if (file_exists("$path/title.txt")) {
                        $title = file_get_contents("$path/title.txt");
                        createTitleTxt($path, $title);
                    }
                    cekAnimated($path);
                }
            }
        }
    }
}

function createTitleTxt($path, $title)
{
    echo " => " . $title;
    $title = filter_filename($title);
    $myfile = fopen("$path/$title.txt", "w");
    fwrite($myfile, $title);
    fclose($myfile);
}

function filter_filename($name)
{
    $name = str_replace(array_merge(
        array_map('chr', range(0, 31)),
        array('<', '>', ':', '"', '/', '\\', '|', '?', '*')
    ), '', $name);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $name = mb_strcut(pathinfo($name, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($name)) . ($ext ? '.' . $ext : '');
    return $name;
}

function cekAnimated($path)
{
    if (is_dir($path)) {
        $anim = false;
        $sticker = scandir($path);
        foreach ($sticker as $sticker_filename) {
            if (!in_array($sticker_filename, [".", ".."])) {
                $pathFile = "$path/$sticker_filename";
                if (isWebpAnimated($pathFile)) {
                    $anim = true;
                }
                sortirFileSize($pathFile);
            }
        }
        if ($anim) {
            $animated = fopen("$path/animated.txt", "w");
            fwrite($animated, "animated");
            echo " - animated";
        }
    }
}

function isWebpAnimated($src)
{
    $webpContents = file_get_contents($src);
    $where = strpos($webpContents, "ANMF");
    if ($where !== FALSE) {
        // animated
        $isAnimated = true;
    } else {
        // non animated
        $isAnimated = false;
    }
    return $isAnimated;
}

function sortirFileSize($path)
{
    $filesize = filesize($path);
    $size = number_format($filesize / 1024, 2);
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (strtolower($ext) == "webp") {
        list($width, $height) =  getimagesize($path);
        if ($width !== $height) {
            resize_image_webp($path, $path, '512', '512', 100, true);
        }

        if ($size > 500) {
            unlink($path);
            echo "<h1>[$size]</h1>";
        }
    }
}

function resize_image_webp($source_file, $destination_file, $width, $height, $quality, $crop = FALSE)
{
    list($current_width, $current_height) = getimagesize($source_file);
    $rate = $current_width / $current_height;
    if ($crop) {
        if ($current_width > $current_height) {
            $current_width = ceil($current_width - ($current_width * abs($rate - $width / $height)));
        } else {
            $current_height = ceil($current_height - ($current_height * abs($rate - $width / $height)));
        }
        $newwidth = $width;
        $newheight = $height;
    } else {
        if ($width / $height > $rate) {
            $newwidth = $height * $rate;
            $newheight = $height;
        } else {
            $newheight = $width / $rate;
            $newwidth = $width;
        }
    }
    $src_file = imagecreatefromwebp($source_file);
    $dst_file = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst_file, $src_file, 0, 0, 0, 0, $newwidth, $newheight, $current_width, $current_height);
    imagewebp($dst_file, $destination_file, $quality);
}
