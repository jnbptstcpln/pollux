<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 19/02/2020
 * Time: 11:04
 */

namespace Plexus;


use Plexus\DataType\Collection;

class Configuration extends Collection {

    /**
     * @var string
     */
    protected $filePath;

    /**
     * Configuration constructor.
     * @param string $filePath
     */
    public function __construct($filePath) {
        parent::__construct();
        $this->filePath = $filePath;
        $this->read();
    }

    /**
     *
     */
    protected function read() {
        if (!is_file($this->filePath)) {
            touch($this->filePath);
            chmod($this->filePath, 0777);
        }
        $this->setArray(parse_ini_file($this->filePath, true));
    }

}