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
                sortirFileSize($path);
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

    if ($size > 500) {
        unlink($path);
        echo "<h1>[$size]</h1>";
    }
}
