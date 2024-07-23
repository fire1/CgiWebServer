<?php


/**
 * Requires enabling the "spckets" extentions:
 * `extension=sockets`
 */


require './src/Request.php';
require './src/Server.php';

//
// Place to store public files
const PUBLIC_DIR = __DIR__ . '/public';


(new Server('127.0.0.1', 8080, PUBLIC_DIR))->run();
