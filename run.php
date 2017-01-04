<?php
$loader = require './vendor/autoload.php';
require 'components/init.php';

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use components\Photos;
use components\exceptions\PhotoException;



$config = [
    'in' => [
        __DIR__ . '/tmp/in'
    ],
    'out' => __DIR__ . '/tmp/out/',
    'stops' => [
        'min_size_w' => 500,
        'min_size_h' => 900,
        'dates' => [
            '2007:00:00 00:00:00',
            ''
        ]
    ]
];

$photos = new Photos($config);

logger('Count: '. $photos->count());

$output = new ConsoleOutput();
$output->setFormatter(new OutputFormatter(true));
$progressBar = new ProgressBar($output, $photos->count());

$added = 0;
foreach ($photos as $photo){

    if(!$photo){
        continue;
    }

    if(@!dir($photo->newDirName())){
        mkdir($photo->newDirName(), 0777, true);
    }
    $i = 0;
    copy:
    if(!file_exists($photo->newFileName())){
        try {
            $photo->move();
            $added++;
        } catch (PhotoException $e){
            logger($e->getMessage(), $e->params);
        }

    } elseif($photo->md5() != md5_file($photo->newFileName())){
        $i++;
        $photo->date->add(new DateInterval("PT".$i."S"));
        goto copy;
    } else {
        // Если это дубликат файла по md5  - то просто удаляем его
        //unlink($filename);
    }

    $progressBar->advance();
}
$progressBar->finish();
$output->writeln('Обработано ' . $photos->count() . ' фоток');
$output->writeln('Добавлено ' . $added . ' фоток');