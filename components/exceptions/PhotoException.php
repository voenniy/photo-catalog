<?php
/**
 * Created by PhpStorm.
 * User: oleg
 * Date: 04.01.17
 * Time: 1:37
 */

namespace components\exceptions;


use Exception;

class PhotoException extends \Exception
{
    public $filename;
    public $params;
    public function __construct($message = "", $filename = null, $params = [], $code = 0, Exception $previous = null)
    {
        $this->filename = $filename;
        $this->params = $params;
        parent::__construct($message, $code, $previous);
    }
}