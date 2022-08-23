<?php
include "app/DAE.php";

if (count(explode("/", @$_GET['url'])) != 1) {
    echo DAE::header().DAE::error().DAE::footer();
} else {
    echo Routes::route(@$_GET['url']);
}