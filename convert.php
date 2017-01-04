<?php
require "ConsoleProgressBar.php";

$config = [
    'dirs' => [
        'E:\PhotoVideo'
    ],
    'outDir' => 'E:/Photos/',
    'stops' => [
        'min_size_w' => 500,
        'min_size_h' => 900,
        'dates' => [
            '2007:00:00 00:00:00',
            ''
        ]
    ]
];


function allpics($dir){
    file_put_contents("error.log", "");
    $pics = glob($dir . '\*.{JPG,jpg}', GLOB_BRACE);
    foreach (glob($dir . '\*', GLOB_ONLYDIR) as $subdir){
        $pics = array_merge($pics, allpics($subdir));
    }
    return $pics;
}
$added = 0;
$pics = [];
foreach($config['dirs']  as $dir){
    $pics = array_merge($pics, allpics($dir));
}

$progress = new ConsoleProgressBar(count($pics));
foreach($pics as $key=>$filename){
    $progress->advance();

    $p = $key*100;
    $exif = @exif_read_data($filename);
    $date = false;
    $date = $w = $h = false;
    if($exif){
        $w = @$exif["ExifImageWidth"];
        $h = @$exif["ExifImageLength"];
        if(isset($exif['DateTimeOriginal'])){
            $date = $exif['DateTimeOriginal'];
        }
        if((!$date || in_array($date, $config['stops']['dates'])) && isset($exif['DateTime'])){
            $date = $exif['DateTime'];
        }
        if((!$date || in_array($date, $config['stops']['dates'])) && isset($exif['FileDateTime'])){
            $date = date("Y:m:d H:i:s", $exif['FileDateTime']);
        }
    }
    if(!$date || in_array($date, $config['stops']['dates'])){
        $date = date("Y:m:d H:i:s", stat($filename)['mtime']);
    }

    if(!$date || in_array($date, $config['stops']['dates'])){
        file_put_contents("error.log", "[$key] " . $filename . " no date " . $date . "\n", FILE_APPEND);
        continue;
    }

    if(!$w || !$h){
        list($w, $h) = @getimagesize($filename);
    }

    if(!$w || !$h){
        file_put_contents("error.log", "[$key] " . $filename . " no weight or height " . $w . "x" . $h . "\n", FILE_APPEND);
        continue;
    }

    $dt = DateTime::createFromFormat("Y:m:d H:i:s", $date);

    if($w <= $config['stops']['min_size_w'] || $h <= $config['stops']['min_size_w']){
        // Если маленькое разрешение
        $newDir = $config['outDir'] . "min_size";
    } else {
        $newDir = $config['outDir'] . $dt->format("Y/m/d");
    }

    $newfilename = $newDir . "/" . $dt->format("Y-m-d_H-i-s") . ".jpg";
    @mkdir($newDir, 0777, true);
    $i = 0;
    copy:
    if(!file_exists($newfilename)){
        if(!copy($filename, $newfilename)){
            file_put_contents("error.log", "[$key] " .$filename . " no copy! " . print_r($exif, true) . "  \n", FILE_APPEND);
        } else {
            $added++;
            //file_put_contents("error.log", "[$key] $filename => $newfilename ($i)\n" , FILE_APPEND);
			// Ставим дату создания по новой определенной дате. Для того, чтобы программы правильно катагализировали
			//touch($newfilename ,$dt->getTimestamp());
            // Удаляем старый файл
            //unlink($filename);
        }
    } elseif(md5_file($filename) != md5_file($newfilename)){
        $i++;
        $dt->add(new DateInterval("PT".$i."S"));
        $newfilename = $newDir . "/" . $dt->format("Y-m-d_h-i-s") . ".jpg";

        goto copy;
    } else {
        // Если это дубликат файла по md5  - то просто удаляем его
		//unlink($filename);
	}
    

    //if(++$i >= 100) break;
}
$progress->finish();

echo "\n\n Всего " . count($pics) . ", скопировано " . $added;