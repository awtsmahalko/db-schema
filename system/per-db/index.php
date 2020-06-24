<?php
include '../core/config.php';
$loop_db = FM_SELECT_LOOP_QUERY("SCHEMA_NAME","SCHEMATA","DEFAULT_CHARACTER_SET_NAME = 'latin1' ORDER BY SCHEMA_NAME ASC");

$databases_ .= "<option value=''>&mdash; Please Choose Database &mdash;</option>";
if(count($loop_db)>0){
    foreach ($loop_db as $db_) {
        $databases_ .= "<option value='$db_[0]'>$db_[0]</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>DB COMPARE</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/css/font-awesome.min.css">
  <link href="../../assets/css/select2.min.css" rel="stylesheet" />

  <script src="../../assets/js/jquery.min.js"></script>
  <script src="../../assets/js/bootstrap.min.js"></script>
  <script src="../../assets/js/select2.min.js"></script>
  <style>
      .select2-container .select2-selection--single{
        height:35px !important;
      }
</style>
</head>
<body>
  <div class="well text-center" style='background-color:#049408;color:#fff;'>
    <h3 style="margin: auto;">COMPARE TWO DATABASES</h3>
  </div>
  <div class="container" style="padding: unset;width:100%;">
    <div class="col-md-12">
        <div class="col-md-5">
            <div class="input-group" style="margin-bottom:5px;">
                <span class="input-group-addon">Database Source (from)</span>
                <select class="form-control select2-here" id="db_source"><?=$databases_?></select>
            </div>
        </div>
        <div class="col-md-5">
            <div class="input-group" style="margin-bottom:5px;">
                <span class="input-group-addon">Database Target (into)</span>
                <select class="form-control select2-here" id="db_target"><?=$databases_?></select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="btn-group" style="margin-bottom:5px;">
                <button class="btn btn-primary" onclick="compare_db()"> Compare</button>
            </div>
        </div>
    </div>
    <hr style="border:1px solid gray;">
    <div class="col-md-12" id="per-db-response">
    </div>
  </div>
<div class="modal fade" id="compare_modal" role="dialog">
    <div class="modal-dialog" style="width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Compare Table</h4>
            </div>
            <div class="modal-body" id='compare_content'>
                <br>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('.select2-here').select2();
    });
    function compare_db(){
        var db_target = $("#db_target").val();
        var db_source = $("#db_source").val();
        if(db_target == '' || db_source == ''){
            alert("Please Choose Database");
        }else if(db_target == db_source){
            alert("Please Choose another Database");
        }else{
            $("#per-db-response").html("<center><h3><span class='fa fa-spin fa-spinner'></span> Loading</h3></center>");
            $.post("../ajax/per_db_compare_database.php",{
                db_target:db_target,
                db_source:db_source
            },function(data,status){
                $("#per-db-response").html(data);
            });
        }
    }
    function compare_modal(tbl){
        var db_target = $("#db_target").val();
        var db_source = $("#db_source").val();
        $("#compare_modal").modal("show");
        $("#compare_content").html("<center><h3><span class='fa fa-spin fa-spinner'></span> Loading</h3></center>");
        $.post("../ajax/compare_database_table.php",{
            db_target:db_target,
            db_source:db_source,
            tbl:tbl
        },function(data,status){
            $("#compare_content").html(data+"<br>");
        });
    }
</script>
</body>
</html>