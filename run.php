<?php
$loader = require './vendor/autoload.php';
require 'components/init.php';

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use components\Photos;
use components\exceptions\PhotoException;

$config = require 'config.php';

$photos = new Photos($config);

logger('Count: '. $photos->count());

$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);
$output->setFormatter(new OutputFormatter(true));
$progressBar = new ProgressBar($output, $photos->count());

$added = $double = $errors = 0;
foreach ($photos as $photo){

    if(!$photo){
        $errors++;
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
            $errors++;
            logger($e->getMessage(), $e->params);
        }

    } elseif($photo->md5() != md5_file($photo->newFileName())){
        $i++;
        $photo->date->add(new DateInterval("PT".$i."S"));
        goto copy;
    } else {
        $double++;
        // Если это дубликат файла по md5  - то просто удаляем его
        //unlink($photo->filename);
        logger('Обнаружен дубликат', ['old' => $photo->newFileName(), 'new'=>$photo->filename]);
    }

    $progressBar->advance();
}
$progressBar->finish();
$output->writeln('');
$table = new Table($output);
$table->setHeaders(['Обработано', 'Добавлено', 'Дублей', 'Ошибок']);
$table->addRow([$photos->count(), $added, $double, $errors]);
$table->render();