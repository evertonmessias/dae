<?php
include "app/DAE.php";

echo DAE::header();

if (count(explode("/", @$_GET['url'])) != 1) {
    echo DAE::error();;
} else {
    echo Routes::route(@$_GET['url']);
}

echo DAE::footer();
