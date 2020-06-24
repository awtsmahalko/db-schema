<?php
include '../core/config.php';
$db_target = $_POST['db_target'];
$db_source = $_POST['db_source'];
$tablename = $_POST['tbl'];

$SchemaClass = new TableSchemaClass($db_source,$db_target,$tablename);
echo $SchemaClass->per_modal();