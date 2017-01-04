<?php

namespace components;

use components\exceptions\NoDateException;
use components\exceptions\NoSizeException;
use components\exceptions\PhotoException;

/**
 * Created by PhpStorm.
 * User: oleg
 * Date: 04.01.17
 * Time: 0:14
 */
class Photo
{
    public $filename;
    public $date;
    public $w;
    public $h;
    public $config;


    public function __construct($filename, $config){
        $this->config = $config;
        $this->filename = $filename;
        $exif = @exif_read_data($filename);
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
            logger('in stops dates' . $filename, [$date]);
            $date = date("Y:m:d H:i:s", stat($filename)['mtime']);
        }

        if(!$date || in_array($date, $config['stops']['dates'])){
            throw new NoDateException('No Date!', $filename, ['date' => $date]);
        } else {
            $this->date =  \DateTime::createFromFormat("Y:m:d H:i:s", $date);
        }

        if(!$w || !$h){
            list($w, $h) = @getimagesize($filename);
        }
        if(!$w || !$h){
            throw new NoSizeException('No Size!', $filename, ['w'=>$w, 'h'=>$h]);
        } else {
            $this->w = $w;
            $this->h = $h;
        }



    }

    public function newFileName(){
        return $this->newDirName() . "/" . $this->date->format("Y-m-d_H-i-s") . ".jpg";
    }

    public function newDirName(){
        if($this->w <= $this->config['stops']['min_size_w'] || $this->h <= $this->config['stops']['min_size_w']){
            // Если маленькое разрешение
            $newDir = $this->config['out'] . "min_size";
        } else {
            $newDir = $this->config['out'] . $this->date->format("Y/m");
        }
        return $newDir;
    }

    public function move($newFilename = null){
        if(!$newFilename){
            $newFilename = $this->newFileName();
        }
        if(!copy($this->filename, $newFilename)){
            throw new PhotoException('Не удалось скопировать фото ', null, ['old' => $this->filename, 'new'=>$newFilename]);
        } else {
            logger($this->filename . ' => ' . $newFilename);
            //  unlink($this->filename);
            $this->filename = $newFilename;
        }

    }

    public function md5(){
        return md5_file($this->filename);
    }

}