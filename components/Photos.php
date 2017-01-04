<?php

namespace components;

use components\exceptions\PhotoException;

/**
 * Created by PhpStorm.
 * User: oleg
 * Date: 03.01.17
 * Time: 21:10
 */
class Photos extends \ArrayIterator
{
    public $config;
    public function __construct($config)
    {
        $this->config = $config;
        $pics = [];
        foreach($config['in']  as $dir){
            $pics = array_merge($pics, $this->getPhotos($dir));
        }
        parent::__construct($pics);
    }

    public function getPhotos($dir){
        $pics = glob($dir . '/*.{JPG,jpg}', GLOB_BRACE);
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subdir){
            $pics = array_merge($pics, $this->getPhotos($subdir));
        }
        return $pics;
    }

    public function current()
    {
        $filename = parent::current();
        try {
            $photo = new Photo($filename, $this->config);
        } catch (PhotoException $e){
            logger($e->getMessage() , array_merge([$e->filename], $e->params));
            $photo = null;
        }
        return $photo;
    }

}