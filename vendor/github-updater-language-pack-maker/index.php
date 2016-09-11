<?php
/**
 * Created by PhpStorm.
 * User: afragen
 * Date: 9/9/16
 * Time: 4:24 PM
 */

require_once 'Language_Pack_Maker.php';

echo '<h2>Generating Language Pack Zip Files and JSON file</h2>';
new \Fragen\GitHub_Updater\Language_Pack_Maker();
