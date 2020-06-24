<?php
	function FM_INSERT_QUERY($table_name, $form_data , $last_id = 'N'){
	    $fields = array_keys($form_data);

	    $sql = "INSERT INTO ".$table_name."
	    (`".implode('`,`', $fields)."`)
	    VALUES('".implode("','", $form_data)."')";

	    $return_insert = mysql_query($sql)or die(mysql_error());
	    $lastID = mysql_insert_id();

	    if($last_id == 'Y'){
	        if($return_insert){
	            return $lastID;
	        }else{
	            return 0;
	        }
	    }else{
	        if($return_insert){
	            return 1;
	        }else{
	            return 0;
	        }
	    }
	}

	function FM_INSERT_QUERY_SELECT($table_name, $select_table, $form_data , $where_clause){
	    $fields = array_keys($form_data);
	    $inject = ($where_clause=='')?"":"WHERE $where_clause";
	    $sql = "INSERT INTO ".$table_name." (`".implode('`,`', $fields)."`) SELECT ".implode(",", $form_data)." FROM $select_table $inject";
	    $return_insert = mysql_query($sql);

	    if($return_insert){
	        return 1;
	    }else{
	        return 0;
	    }
	}

	function FM_SELECT_QUERY($type , $table , $params = ''){
		$inject = ($params=='')?"":"WHERE $params";
	    $select_query = mysql_query("SELECT $type FROM $table $inject")or die(mysql_error());
	    $fetch = mysql_fetch_array($select_query);
	    return $fetch;
	}

	function FM_SELECT_LOOP_QUERY($type , $table , $params = ''){
		$inject = ($params=='')?"":"WHERE $params";
	    $fetch = mysql_query("SELECT $type FROM $table $inject")or die(mysql_error());
		while ($row = mysql_fetch_array($fetch)) {
			$data[] = $row;
		}
		return $data;
	}

	function FM_UPDATE_QUERY($table_name, $form_data, $where_clause=''){
	    $whereSQL = '';
	    if(!empty($where_clause)){
	        if(substr(strtoupper(trim($where_clause)), 0, 5) != 'WHERE'){
	            $whereSQL = " WHERE ".$where_clause;
	        }else{
	            $whereSQL = " ".trim($where_clause);
	        }
	    }
	    $sql = "UPDATE ".$table_name." SET ";
	    $sets = array();
	    foreach($form_data as $column => $value)
	    {
	        $sets[] = "`".$column."` = '".$value."'";
	    }
	    $sql .= implode(', ', $sets);
	    $sql .= $whereSQL;

	    $return_query = mysql_query($sql);
	    if($return_query){
	        return 1;
	    }else{
	        return 0;
	    }
	}

	function FM_DELETE_QUERY($table_name, $where_clause=''){
	    $whereSQL = '';
	    if(!empty($where_clause)){
	        if(substr(strtoupper(trim($where_clause)), 0, 5) != 'WHERE'){
	            $whereSQL = " WHERE ".$where_clause;
	        }else{
	            $whereSQL = " ".trim($where_clause);
	        }
	    }
	    $sql = "DELETE FROM ".$table_name.$whereSQL;
	    
	    $return_delete = mysql_query($sql);
	    
	    if($return_delete){
	        return 1;
	    }else{
	        return 0;
	    }
	}

	function FM_QUERY($query){
		$r = mysql_query($query);
	    if($r){
	        return 1;
	    }else{
	        return 0;
	    }
	}

	function clean($str) {
	        $str = @trim($str);
	        if(get_magic_quotes_gpc()) {
	            $str = stripslashes($str);
	        }
	        return mysql_real_escape_string($str);
	}
?>