<?php
include '../core/config.php';
$db_target = $_POST['db_target'];
$db_source = $_POST['db_source'];
$SchemaClass = new TableSchemaClass($db_source,$db_target);
echo $SchemaClass->per_views();
?>