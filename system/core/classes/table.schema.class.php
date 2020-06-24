<?php
/**
 *   *=======*
 *         =*   *====*   *====*  *     *
 *       =*    *====*   *       *=====*
 *     =*     *        *       *     *
 *   =*      *=====   *====*  *     *
 *   *=======* S  O  L  U  T  I  O  N S
 */
class TableSchemaClass
{
    public $tables = array();
    public $db = array();
    public $queries = array();

    public $patch_field = array();
    function __construct($source_db , $destination_dbs = '*', $tables = '*')
	{
        $my_concat = "CONCAT(COLUMN_NAME,COLUMN_TYPE,EXTRA,COLUMN_COMMENT,IS_NULLABLE)";
        $my_select = "$my_concat,COLUMN_NAME,COLUMN_TYPE,COLUMN_DEFAULT,EXTRA,COLUMN_COMMENT,IS_NULLABLE";

        $this->tbl_top = $tables;
        $this->db_top = $destination_dbs;
        $this->source_db = $source_db;
        $this->my_concat = $my_concat;
        $this->my_select = $my_select;
    }

    public function per_views()
    {
        return '<table class="table" style="overflow:auto;">
            <thead class="thead-dark">
                <tr style="background-color:green;color:#fff;">
                    <th scope="col">Table Name</th>
                    <th scope="col">Descrip</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>'.$this->per_tr().'</tbody>
        </table>';
    }

    public function per_modal()
    {
        return '<div class="col-md-12">
        <div class="col-md-6">
            <h4>'.$this->source_db.'.'.$this->tbl_top.'</h4>
            <table class="table" style="overflow:auto;">
                <thead class="thead-dark">
                    <tr style="background-color:green;color:#fff;">
                        <th scope="col">Column</th>
                        <th scope="col">Type</th>
                        <th scope="col">Default</th>
                        <th scope="col">Null</th>
                        <th scope="col">Comment</th>
                    </tr>
                </thead>
                <tbody>'.$this->per_modal_column($this->source_db).'</tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h4>'.$this->db_top.'.'.$this->tbl_top.'</h4>
            <table class="table" style="overflow:auto;">
                <thead class="thead-dark">
                    <tr style="background-color:green;color:#fff;">
                        <th scope="col">Column</th>
                        <th scope="col">Type</th>
                        <th scope="col">Default</th>
                        <th scope="col">Null</th>
                        <th scope="col">Comment</th>
                    </tr>
                </thead>
                <tbody>'.$this->per_modal_column($this->db_top).'</tbody>
            </table>
        </div></div>';
    }
    public function per_tr(){
        $loop_table = FM_SELECT_LOOP_QUERY("TABLE_NAME","INFORMATION_SCHEMA.TABLES","TABLE_SCHEMA = '$this->source_db' AND TABLE_TYPE = 'BASE TABLE' ORDER BY table_name ASC");
        $content = "";
        if(count($loop_table)>0){
            foreach ($loop_table as $row) {
                $aa = $this->per_compare($row[0]);
                $content .= "<tr>
                    <td>$row[0]</td>
                    <td>$aa[desc]</td>
                    <td><button class='btn btn-xs btn-default' onclick=\"compare_modal('$row[0]')\"><span class='fa fa-exchange'></span> Compare</button>$aa[btn]</td>
                </tr>";
            }
        }
        return $content;
    }

    public function per_compare($tbl_name)
    {
        $res = array();
        $res['desc'] = '';
        $res['btn'] = '';
        if($this->isTableExist($this->db_top,$tbl_name)>0){
            $loop_column = FM_SELECT_LOOP_QUERY(
                $this->my_select,
                "INFORMATION_SCHEMA.COLUMNS",
                "table_name = '$tbl_name' AND TABLE_SCHEMA = '$this->source_db'"
            );
            if(count($loop_column)>0){
                $is_alter = array();
                foreach ($loop_column as $col) {
                    if($this->isColumnDiffer($this->db_top,$tbl_name,$col[0])<1)
                    {
                        $is_alter[] = $col[1];
                    }
                }
                if(count($is_alter)>0){
                    $res['desc'] = implode(",",$is_alter);
                    $res['btn'] = "<button class='btn btn-xs btn-danger'><span class='fa fa-edit'></span> Alter Table</button>";

                }
            }
        }else{
            $res['desc'] = "ADD $tbl_name";
            $res['btn'] = "<button class='btn btn-xs btn-primary'><span class='fa fa-plus-circle'></span> Add Table</button>";
        }
        return $res;
    }

    public function per_modal_column($database)
    {
        $content = "";
        $loop_ = FM_SELECT_LOOP_QUERY("*","COLUMNS","TABLE_NAME = '$this->tbl_top' AND TABLE_SCHEMA = '$database'");
        if(count($loop_)>0){
            foreach ($loop_ as $col) {
                $content .= "<tr>
                    <td>$col[COLUMN_NAME]</td>
                    <td>$col[COLUMN_TYPE]</td>
                    <td>$col[COLUMN_DEFAULT]</td>
                    <td>$col[IS_NULLABLE]</td>
                    <td>$col[COLUMN_COMMENT]</td>
                </tr>";
            }
        }
        return $content;
    }

    public function initTables($is_destination = 0)
    {
        if($this->tbl_top == '*') {
            $my_db = ($is_destination == 1)?$this->db_top:$this->source_db;
            $tables_arr = array();
            $loop_table = FM_SELECT_LOOP_QUERY("TABLE_NAME","INFORMATION_SCHEMA.TABLES","TABLE_SCHEMA = '$my_db' AND TABLE_TYPE = 'BASE TABLE' ORDER BY table_name ASC");
            if(count($loop_table)>0){
                foreach ($loop_table as $row) {
                    $tables_arr[] = $row[0];
                }
            }
            $this->tables = $tables_arr;
        } else {
            $this->tables = is_array($this->tbl_top) ? $this->tbl_top : explode(',', str_replace(' ', '', $this->tbl_top));
        }
    }

    public function initDB()
    {
        if($this->db_top == '*') {
            $company_db = array();
            FM_QUERY("USE $this->source_db");
            $loop_table = FM_SELECT_LOOP_QUERY("company_code","tbl_company");
            if(count($loop_table)>0){
                foreach ($loop_table as $row) {
                    $company_db[] = $GLOBALS['config']['mysql']['db_prefix'].$row[0];
                }
            }
            $db_arr = $company_db;
        } else {
            $db_arr = is_array($this->db_top) ? $this->db_top : explode(',', str_replace(' ', '', $this->db_top));
        }

        if(count($db_arr)>0){
            foreach ($db_arr as $db_name) {
                ($this->isDBExist($db_name) > 0) ? $this->db[] = $db_name : '';
            }
        }
    }

    public function compareTables()
    {
        $this->initTables();
        $this->initDB();
        if(count($this->db)>0){
            foreach ($this->db as $db_name) {
                $this->compareTablesCheck($db_name);
            }
        }
    }

    public function compareTablesCheck($db_name)
    {
        if(count($this->tables)>0){
            foreach ($this->tables as $tbl_name) {
                if($this->isTableExist($db_name,$tbl_name)>0){
                    $loop_column = FM_SELECT_LOOP_QUERY(
                        $this->my_select,
                        "INFORMATION_SCHEMA.COLUMNS",
                        "table_name = '$tbl_name' AND TABLE_SCHEMA = '$this->source_db'"
                    );
                    if(count($loop_column)>0){
                        $sql_modify = array();
                        $sql_add = array();
                        $is_alter = array();
                        foreach ($loop_column as $col) {
                            if($this->isColumnDiffer($db_name,$tbl_name,$col[0])<1)
                            {
                                $default_col_ = ($col[3] == 'CURRENT_TIMESTAMP')?$col[3]:"'".clean($col[3])."'";
                                $default_col = ($col[3] == NULL && $col[3] == '') ? '' : "DEFAULT $default_col_";
                                $comment_col = ($col[5] == '') ? '' : "COMMENT '". clean($col[5])."'";
                                $is_null_col = ($col[6] == 'YES')? 'NULL' : 'NOT NULL';
                                $final_alter = $ini_alter."`$col[1]` $col[2] $col[4] $is_null_col $default_col $comment_col";
                                if($this->isColumnExist($db_name,$tbl_name,$col[1])>0){
                                    $sql_modify[] = "MODIFY IF EXISTS $final_alter";
                                }else{
                                    if($tbl_name == 'tbl_feed_production_variance' && $col[1] == 'variance_id'){
                                        $this->queries[$db_name][] = $this->utf8ize("ALTER TABLE $tbl_name CHANGE id variance_id int(11) auto_increment NOT NULL");
                                    }
                                    $sql_add[] = $final_alter;
                                }
                                $is_alter[] = 1;
                            }
                        }
                        if(count($is_alter)>0){
                            $imploded_add = implode(",",$sql_add);
                            $imploded_modify = ((count($sql_add)>0)?",":"") . implode(",",$sql_modify);
                
                            $content  = "ALTER TABLE `$tbl_name` ";
                            $content .= (count($sql_add)>0)?((count($sql_add) > 1) ? "ADD COLUMN IF NOT EXISTS ($imploded_add)":"ADD COLUMN IF NOT EXISTS $imploded_add"):'';
                            $content .= (count($sql_modify)>0)?$imploded_modify:'';
                            $this->queries[$db_name][] = $this->utf8ize($content);
                        }
                    }
                }else{
                    FM_QUERY("USE $this->source_db");
                    $create_table = mysql_fetch_row(mysql_query("SHOW CREATE TABLE `$tbl_name`"));
                    $this->queries[$db_name][] = $this->utf8ize($create_table[1]);
                }
            }
        }
    }

    public function executeQuery()
    {
        if(count($this->queries)>0){
            foreach ($this->queries as $db_name => $query_arr) {
                FM_QUERY("USE $db_name");
                foreach($query_arr as $query){
                    FM_QUERY($query);
                }
            }
        }
    }

    public function executeQueryRestore($query_restore , $is_main = 0)
    {
        if(count($query_restore)>0){
            foreach ($query_restore as $db_name => $query_arr) {
                $query_db = ($is_main==1)?$this->source_db:$db_name;
                FM_QUERY("CREATE DATABASE IF NOT EXISTS $query_db");
                FM_QUERY("USE $query_db");
                $response .= $query_db;
                foreach($query_arr as $query){
                    FM_QUERY($query);
                }
            }
        }
    }

    public function patch_subscriber_export($company_code)
    {
        $this->patch_subscriber_field();
        FM_QUERY("USE $this->source_db");
        $comp_ = FM_SELECT_QUERY("*","tbl_company","company_code = '$company_code'");

        $sql = "UPDATE $this->tbl_top SET ";
	    $sets = array();
	    foreach($this->patch_field as $column)
	    {
	        $sets[] = "`".$column."` = '".$comp_[$column]."'";
	    }
	    $sql .= implode(', ', $sets);
        $sql .= " WHERE company_code = '$company_code'";
        $this->queries[$this->db_top][] = $this->utf8ize($sql);
    }

    public function patch_subscriber_field()
    {
        $this->patch_field = array('system_egg','fo_brooding_limits','fo_laying_limits','system_pig','no_sows','no_nonsows','system_feed','system_optinotes','per_tons','system_broiler','limit_broiler','no_users','expiry_date','date_added','enable_accounting','enable_feedmill','allow_price_watch','status','declared_access','eggSubscription','pigSubscription','feedSubscription','broilerSubscription','accountingSubscription','totalSubscription','free_users','extraCharges_users','from_dateSubs','to_dateSubs','local_db_status','closingBookMonth');
    }

    public function isTableExist($db_name,$tbl_name)
    {
        $fetch = FM_SELECT_QUERY("COUNT(TABLE_NAME)","INFORMATION_SCHEMA.TABLES","TABLE_SCHEMA = '$db_name' AND TABLE_NAME = '$tbl_name'");
        return $fetch[0] * 1;
    }

    public function isColumnExist($db_name,$tbl_name,$column_name)
    {
        $fetch = FM_SELECT_QUERY("COUNT(COLUMN_NAME)","INFORMATION_SCHEMA.COLUMNS","table_name = '$tbl_name' AND TABLE_SCHEMA = '$db_name' AND COLUMN_NAME = '$column_name'");
        return $fetch[0] * 1;
    }

    public function isColumnDiffer($db_name,$tbl_name,$my_concat)
    {
        $fetch = FM_SELECT_QUERY("COUNT(COLUMN_NAME)","INFORMATION_SCHEMA.COLUMNS","table_name = '$tbl_name' AND TABLE_SCHEMA = '$db_name' AND $this->my_concat = '$my_concat'");
        return $fetch[0] * 1;
    }

    public function isDBExist($db_name)
    {
        $fetch = FM_SELECT_QUERY("COUNT(SCHEMA_NAME)","INFORMATION_SCHEMA.SCHEMATA","SCHEMA_NAME = '$db_name'");
        return $fetch[0] * 1;
    }

    public function company_name($company_code)
    {
        FM_QUERY("USE $this->source_db");
        $fetch = FM_SELECT_QUERY("company_name","tbl_company","company_code = '$company_code'");
        return $fetch[0];
    }

    public function file_name($company_code,$type = '')
    {
        $type_list = array(
            'D' => 'DBPATCH_',
            'P' => 'SUBSCRIBER_PATCH_',
            'B' => 'BACKUP_'
        );
        $comp_name  = $this->company_name($company_code);
        $date_now_  = date("mdYhis", strtotime(getCurrentDate()));
        return $type_list[$type] . str_replace(' ', '_', $comp_name) . "_" . $date_now_ . ".txt";
    }

    public function restore_type($line_of_text)
    {
        return substr($line_of_text, 0, 1);
    }

    public function encrypt_patch($content,$type = '')
    {
        return $type.generateRandomString().base64_encode($content);
    }

    public function decrypt_patch($line_of_text)
    {
        return base64_decode(substr($line_of_text, 5));
    }

    public function patch_backup_db($mode, $dir)
    {
        FM_QUERY("USE $this->db_top");
        $this->initTables(1);
        $disableForeignKeyChecks = true;
        $charset = 'utf8';
        $batchSize = 1000;
    
        $sql = "CREATE DATABASE IF NOT EXISTS `$this->db_top`;\n\n";
        $sql .= 'USE `'.$this->db_top."`;\n\n";
    
        /**
         * Disable foreign key checks 
         */
        if ($disableForeignKeyChecks === true) {
            $sql .= "SET foreign_key_checks = 0;\n\n";
        }
    
        /**
         * Iterate tables
         */
        foreach($this->tables as $table)
        {
            /**
             * CREATE TABLE
             */
            $this->queries[$this->db_top][] = $this->utf8ize('DROP TABLE IF EXISTS `'.$table.'`');
            $row = mysql_fetch_row(mysql_query('SHOW CREATE TABLE `'.$table.'`'));
            $this->queries[$this->db_top][] = $this->utf8ize($row[1]);

            /**
             * INSERT INTO
             */
            $row = mysql_fetch_row(mysql_query('SELECT COUNT(*) FROM `'.$table.'`'));
            $numRows = $row[0];
            // Split table in batches in order to not exhaust system memory 
            // Number of while-loop calls to perform
            $numBatches = intval($numRows / $batchSize) + 1; 
            for ($b = 1; $b <= $numBatches; $b++)
            {
                $query = 'SELECT * FROM `' . $table . '` LIMIT ' . ($b * $batchSize - $batchSize) . ',' . $batchSize;
                $result = mysql_query($query);
                $realBatchSize = mysql_num_rows ($result); // Last batch size can be different from $this->batchSize
                $numFields = mysql_num_fields($result);
                if ($realBatchSize !== 0)
                {
                    $sql = 'INSERT INTO `'.$table.'` VALUES ';
                    for ($i = 0; $i < $numFields; $i++)
                    {
                        $rowCount = 1;
                        while($row = mysql_fetch_row($result))
                        {
                            $sql.='(';
                            for($j=0; $j<$numFields; $j++) 
                            {
                                if (isset($row[$j])) {
                                    $row[$j] = addslashes($row[$j]);
                                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                                    $row[$j] = str_replace("\r","\\r",$row[$j]);
                                    $row[$j] = str_replace("\f","\\f",$row[$j]);
                                    $row[$j] = str_replace("\t","\\t",$row[$j]);
                                    $row[$j] = str_replace("\v","\\v",$row[$j]);
                                    $row[$j] = str_replace("\a","\\a",$row[$j]);
                                    $row[$j] = str_replace("\b","\\b",$row[$j]);
                                    if ($row[$j] == 'true' or $row[$j] == 'false' or preg_match('/^-?[0-9]+$/', $row[$j]) or $row[$j] == 'NULL' or $row[$j] == 'null') {
                                        $sql .= $row[$j];
                                    } else {
                                        $sql .= "'".clean($row[$j])."'" ;
                                    }
                                } else {
                                    $sql.= 'NULL';
                                }

                                if ($j < ($numFields-1)) {
                                    $sql .= ',';
                                }
                            }

                            if ($rowCount == $realBatchSize) {
                                $rowCount = 0;
                                $sql.= ")"; //close the insert statement
                                $this->queries[$this->db_top][] = $this->utf8ize($sql);
                            } else {
                                $sql.= "),\n"; //close the row
                            }

                            $rowCount++;
                        }
                    }
                }
            }
        }
        $this->patch_views();
    }

    public function patch_views()
    {
        $this->queries[$this->db_top][] = $this->utf8ize('DROP VIEW IF EXISTS `ar_beginning_balance`');
        $this->queries[$this->db_top][] = $this->utf8ize("CREATE VIEW `ar_beginning_balance`
                AS SELECT
                   `tbl_beginning_balance`.`bbnum` AS `delivery_number`,'BB' AS `module`,
                   `tbl_beginning_balance`.`posted_date` AS `date_added`,
                   `tbl_beginning_balance`.`company_id` AS `company_id`,
                   `tbl_beginning_balance`.`branch_id` AS `branch_id`,
                   `tbl_beginning_balance`.`account_id` AS `account_id`,
                   `tbl_beginning_balance`.`status` AS `status`
                FROM `tbl_beginning_balance`");

        $this->queries[$this->db_top][] = 'DROP VIEW IF EXISTS `bb_for_aging`';
        $this->queries[$this->db_top][] = $this->utf8ize("CREATE VIEW `bb_for_aging`
                AS SELECT
                `tbl_beginning_balance`.`begin_bal_id` AS `begin_bal_id`,
                `tbl_beginning_balance`.`company_id` AS `company_id`,
                `tbl_beginning_balance`.`branch_id` AS `branch_id`,
                `tbl_beginning_balance`.`bbnum` AS `delivery_number`,
                `tbl_beginning_balance`.`account_id` AS `customer_id`,
                `tbl_beginning_balance`.`status` AS `status`,
                `tbl_beginning_balance`.`posted_date` AS `dr_date`,
                `tbl_beginning_balance`.`dr` AS `amount`,
                `tbl_beginning_balance`.`description` AS `description`
                FROM `tbl_beginning_balance` where (`tbl_beginning_balance`.`gchart_main_id` <> 0)");

        $this->queries[$this->db_top][] = 'DROP VIEW IF EXISTS `beginning_balance`';
        $this->queries[$this->db_top][] = $this->utf8ize("CREATE VIEW `beginning_balance`
                AS SELECT
                `tbl_beginning_balance`.`bbnum` AS `receiving_number`,'BB' AS `module`,
                `tbl_beginning_balance`.`date` AS `date_added`,
                `tbl_beginning_balance`.`company_id` AS `company_id`,
                `tbl_beginning_balance`.`branch_id` AS `branch_id`,
                `tbl_beginning_balance`.`gchart_main_id` AS `gchart_main_id`,
                `tbl_beginning_balance`.`account_id` AS `account_id`,
                `tbl_beginning_balance`.`dr` AS `dr`,
                `tbl_beginning_balance`.`cr` AS `cr`,
                `tbl_beginning_balance`.`date` AS `date`,
                `tbl_beginning_balance`.`status` AS `status`
                FROM `tbl_beginning_balance`");
    }

    public function utf8ize( $mixed ) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
        }
        return $mixed;
    }

    public function root_compare()
    {
        $this->initTables();
        $this->initDB();
        if(count($this->db)>0){
            foreach ($this->db as $db_name) {
                $this->root_compare_sync($db_name);
            }
        }
    }

    public function root_compare_sync($db_name)
    {
        $response = '';
        if(count($this->tables)>0){
            foreach ($this->tables as $tbl_name) {
                if($this->isTableExist($db_name,$tbl_name)>0){
                    $loop_column = FM_SELECT_LOOP_QUERY(
                        $this->my_select,
                        "INFORMATION_SCHEMA.COLUMNS",
                        "table_name = '$tbl_name' AND TABLE_SCHEMA = '$this->source_db'"
                    );
                    if(count($loop_column)>0){
                        $sql_modify = array();
                        $sql_add = array();
                        $is_alter = array();
                        foreach ($loop_column as $col) {
                            if($this->isColumnDiffer($db_name,$tbl_name,$col[0])<1)
                            {
                                $default_col_ = ($col[3] == 'CURRENT_TIMESTAMP')?$col[3]:"'".clean($col[3])."'";
                                $default_col = ($col[3] == NULL && $col[3] == '') ? '' : "DEFAULT $default_col_";
                                $comment_col = ($col[5] == '') ? '' : "COMMENT '". clean($col[5])."'";
                                $is_null_col = ($col[6] == 'YES')? 'NULL' : 'NOT NULL';
                                $final_alter = $ini_alter."`$col[1]` $col[2] $col[4] $is_null_col $default_col $comment_col";
                                if($this->isColumnExist($db_name,$tbl_name,$col[1])>0){
                                    $sql_modify[] = "MODIFY $final_alter";
                                }else{
                                    if($tbl_name == 'tbl_feed_production_variance' && $col[1] == 'variance_id'){
                                        FM_QUERY("USE $db_name");
                                        $is_ = FM_QUERY("ALTER TABLE $tbl_name CHANGE id variance_id int(11) auto_increment NOT NULL");
                                        if($is_){
                                            $response .= "<span style='color:green'><span class='fa fa-check-circle'></span> Good! <strong>$db_name</strong> Successfully => ALTER TABLE $tbl_name CHANGE id variance_id int(11) auto_increment NOT NULL</span> <br>";
                                        }else{
                                            $response .= "<span style='color:red'><span class='fa fa-times-circle'></span> Oops! <strong>$db_name</strong>Failed to => ALTER TABLE $tbl_name CHANGE id variance_id int(11) auto_increment NOT NULL</span> <br>";
                                        }
                                    }
                                    $sql_add[] = $final_alter;
                                }
                                $is_alter[] = 1;
                            }
                        }
                        if(count($is_alter)>0){
                            $imploded_add = implode(",",$sql_add);
                            $imploded_modify = ((count($sql_add)>0)?",":"") . implode(",",$sql_modify);
                
                            $content  = "ALTER TABLE `$tbl_name` ";
                            $content .= (count($sql_add)>0)?((count($sql_add) > 1) ? "ADD COLUMN ($imploded_add)":"ADD COLUMN $imploded_add"):'';
                            $content .= (count($sql_modify)>0)?$imploded_modify:'';
                            FM_QUERY("USE $db_name");
                            $is_ = FM_QUERY($content);
                            if($is_){
                                $response .= "<span style='color:green'><span class='fa fa-check-circle'></span> Good! <strong>$db_name</strong> Successfully => $content</span> <br>";
                            }else{
                                $response .= "<span style='color:red'><span class='fa fa-times-circle'></span> Oops! <strong>$db_name</strong>Failed to => $content</span> <br>";
                            }
                        }else{
                            $response .= "<span style='color:blue'><span class='fa fa-exclamation-circle'></span> Oops! <strong>$db_name</strong> $tbl_name already exist</span> <br>";
                        }
                    }
                }else{
                    FM_QUERY("USE $this->source_db");
                    $create_table = mysql_fetch_row(mysql_query("SHOW CREATE TABLE `$tbl_name`"));
                    FM_QUERY("USE $db_name");
                    $is_ = FM_QUERY($create_table[1]);
                    if($is_){
                        $response .= "<span style='color:green'><span class='fa fa-check-circle'></span> Good! <strong>$db_name</strong> Successfully added $tbl_name</span> <br>";
                    }else{
                        $response .= "<span style='color:red'><span class='fa fa-times-circle'></span> Oops! <strong>$db_name</strong>Failed to add $tbl_name </span> <br>";
                    }
                }
            }
        }
        $this->response_sync .= $response;
    }
}
?>