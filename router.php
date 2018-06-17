<?php
if (file_exists($_SERVER["DOCUMENT_ROOT"].$_SERVER['REQUEST_URI'])) {
	return false;
}
include 'maven-proxy.php';