<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 15/07/2020
 * Time: 10:34
 */

session_start();

error_reporting(E_ERROR | E_PARSE);

date_default_timezone_set("Europe/Paris");

require_once __DIR__ . '/../vendor/autoload.php';

$app = new \CPLN\Application(__DIR__ . '/..');
$app->run();