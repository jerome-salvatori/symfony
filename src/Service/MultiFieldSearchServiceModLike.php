<?php

namespace App\Service;

class MultiFieldSearchServiceModLike {
    
    private $auto_sort;
    private $fields_pr;
    private $req_return; //options : 'data_fields', 'ids', 'tables', array $custom_array (['table_name.col_name',...])
    private $fields_match; // ["form_field_name" => "table_name.column_name", ...]
    private $split_tbls_cols; // ["table_name1...1" => "column_name1", "table_name1...2" => "column_name2", "table_name2...3" => "column_name1", ...]
    private $tables;
    private $srchd_tbls;
    private $table_joins;
    /* ["main" => "main_table_name",
        "joined" => [
                "join_table_name_1" => [
                    "main_table_name.join_table_name_1_id" => "join_table_name_1.id"
                ],
                "join_table_name_2" => [
                    "main_table_name.join_table_name_2_id" => "join_table_name_2.id"
                ],...
            ]
        ]
    */
    
    const FIELD_PATTERN = '/^[A-Za-z0-9-_#@$]+(?<!_)\_{3}(?!_)[A-Za-z0-9-_#@$]+$/';
    const ALL_FORM_FIELDS = "data_fields";
    const ONLY_TABLE_IDS = "ids";
    const SRCH_TABLES = "tables";
    
    public function __construct($auto_sort = true, array $fields_pr = [], $req_return = self::ALL_FORM_FIELDS, $fields_match = false, $table_joins = false, $srchd_tbls = false) {
        $this->auto_sort = $auto_sort;
        $this->fields_pr = $fields_pr;
        $this->req_return = $req_return;
        $this->srchd_tbls = $srchd_tbls;
        $this->fields_match = $fields_match;
        $this->table_joins = $table_joins;
        
    }
    
    public function buildSQLR($data) {
        if ($this->auto_sort) {
            $this->fields_pr = array_keys($data);
        }
        
        $cpk_data = array_keys($data);
        sort($cpk_data);
        $cp_fields_pr = $this->fields_pr;
        sort($cp_fields_pr);
        if ($this->fields_match){
            $cpk_fields_match = array_keys($this->fields_match);
            sort($cpk_fields_match);
        }
        
        if ($cpk_data != $cp_fields_pr) {
            throw new Exception('form fields don\'t match parameters');
        } elseif($this->fields_match && $cpk_data != $cpk_fields_match) {
            throw new Exception('field matching parameters are invalid');
        } elseif (!$this->fields_match) {
            if (!$this->checkFixFMatch()) {
                throw new Exception('form fields\'s format is invalid');
            }
        } elseif ($this->req_return == self::SRCH_TABLES && !$this->srchd_tbls) {
            throw new Exception('No tables selected');
        }
        
        
        $nb_fld = 0;
        $dt_def = [];
        foreach ($data as $k => $v) {
            if (!empty($data[$k])) {
                $nb_fld++;
                $dt_def[$k] = $v;
            }
        }
        
        $seljoin_sub_req = $this->buildSelJoinSubReq();
        $values_prep = [];
        
        $tll_ch_p = count($this->fields_pr);
        for ($i = 0; $i < $nb_fld; $i++) {
            $isel = $i + 1;
            $dyn_var = "sel_join$isel";
            $tier = $nb_fld - $i + 1;
            $nb_fld_tier = $nb_fld - $i;
            $iii = 0;
            $ch_inclus = [];
            for($ii = 0; $ii < $tll_ch_p; $ii++) {
                
                if(in_array($this->fields_pr[$ii], array_keys($dt_def))) {
                    $iii++;
                    $ch_inclus[$this->fields_pr[$ii]] = $dt_def[$this->fields_pr[$ii]];
                }
                
                if ($iii >= $nb_fld_tier) {
                    break;
                }
            }
            
            $$dyn_var = "SELECT $tier AS tier, ";
            $$dyn_var .= $seljoin_sub_req;
            $$dyn_var .= " WHERE";
            
            
            $where_cl = $this->whereClause($ch_inclus, "AND", $tier, $nb_fld);
            $$dyn_var .= $where_cl["str"];
            $values_prep += $where_cl["values"];
        }
        
        $sql = $this->buildMainSelect($seljoin_sub_req);
        
        for ($i = 0; $i < $nb_fld; $i++) {
            $isel = $i + 1;
            $dyn_var = "sel_join$isel";
            
            if ($isel > 1) {
                $sql .= " UNION ";
            }
            $sql .= $$dyn_var;
        }
        
        $sql .= " UNION SELECT 1 AS tier, ";
        
        $sql .= $seljoin_sub_req;
        
        $sql .= " WHERE";
        
        
        $where_cl = $this->whereClause($dt_def, "OR", 1, $nb_fld);
        $sql .= $where_cl["str"];
        $values_prep += $where_cl["values"];
        
        $sql .= ") as t ORDER BY tier DESC, id DESC";
        
        $sql_arr = ["sql" => $sql,
                   "val_prep" => $values_prep];
        
        return $sql_arr;
    }
    
    private function whereClause($ch_arr, $op, $tier, $nb_fld) {
        $i = 0;
        $fl_vals = [];
        
        $offset = $this->getPrepOffset($tier, $nb_fld);
        
        $str = "";
        foreach ($ch_arr as $key => $val) {
            
            $i++;
            
            //"=" dans le repo original, remplacé par "LIKE"
            $str .= " ".$this->fields_match[$key]." LIKE ?";
            $k_flv = $i + $offset;
            $fl_vals[$k_flv] = $ch_arr[$key];
            if ($key !== array_key_last($ch_arr)) {
                $str .= " $op";
            }
        }
        $arr_rt = ["str" => $str,
                  "values" => $fl_vals];
        return $arr_rt;
    }
    
    private function getPrepOffset($tier, $nb_fld) {
        $offset = 0;
        $fl_rm = 0;
        if ($tier == $nb_fld + 1) {
            return $offset;
        } else {
            for($i = $nb_fld + 1; $i > $tier; $i--) {
                $offset += $nb_fld;
                $fl_rm += ($nb_fld + 1) - $i;
            }
            $offset -= $fl_rm;
            return $offset;
        }
    }
    
    private function buildMainSelect($seljoin_sub_req) {
        $main_select = "SELECT DISTINCT ";
        $xpl_ssr = explode(" FROM ", $seljoin_sub_req);
        $subr_slctd_fields = $xpl_ssr[0];
        $subr_slctd_fields = explode(', ', $subr_slctd_fields);
        $i = 0;
        foreach($subr_slctd_fields as $column) {
            $i++;
            if (preg_match('/^[A-Za-z0-9-_#@$\.]+ AS [A-Za-z0-9-_#@$]+$/', $column)) {
                $xpl_col = explode(" AS ", $column);
            } else {
                $xpl_col = explode(".", $column);
            }
            $main_select .= $xpl_col[1];
            if ($i < count($subr_slctd_fields)) {
                $main_select .= ", ";
            }
        }
        $main_select .=  " FROM (";
        return $main_select;
    }
    
    private function buildSelJoinSubReq() {
        $sub_req_str = "";
        
        
        if (!is_array($this->req_return)) {
            $this->defSplitTbCols($this->fields_match);
        } else {
            $this->defSplitTbCols($this->req_return);
        }
        
        if ($this->req_return != self::ONLY_TABLE_IDS) {
            $unq_stc = array_unique($this->split_tbls_cols); 
            $duplicates = array_diff_assoc($this->split_tbls_cols, $unq_stc);

            foreach ($duplicates as $k => $v) {
                if ($needle_key = array_search($v, $unq_stc)) {
                    $duplicates[$needle_key] = $v;
                }
            }
        }
        
        
        if ($this->req_return != self::ONLY_TABLE_IDS && !is_array($this->req_return)) {
            
            
            $sub_req_str .= $this->firstPartSReqBuild($duplicates);
        
        } elseif ($this->req_return == self::ONLY_TABLE_IDS) {
            
            $this->defTables();
            $i = 0;
            foreach ($this->tables as $tbl) {
                $i++;
                $sub_req_str .= $tbl.'.id AS '.$tbl.'_id';
                if ($i < count($this->tables)) {
                    $sub_req_str .= ", ";
                }
            }
        } elseif (is_array($this->req_return)) { 
            $i = 0;
            foreach($this->req_return as $val) {
                $i++;
                $sub_req_str .= $val;
                $xpl_val = explode('.', $val);
                $sub_req_str .= $this->rewriteDuplicates($duplicates, $xpl_val[0], $xpl_val[1]);
                if ($i < count($this->req_return)) {
                    $sub_req_str .= ", ";
                }
            }
        }
        
        $sub_req_str .= " FROM ";
        
        if ($this->table_joins) {
            $sub_req_str .= $this->table_joins["main"];
            foreach ($this->table_joins["joined"] as $k => $v) {
                $sub_req_str .= " JOIN " . $k . " ON ";
                $ar_k_v = array_keys($v);
                $sub_req_str .= $ar_k_v[0] . " = " . $v[$ar_k_v[0]];
            }
        } else {
            $priority_field = $this->fields_pr[0];
            $match_pr_fl = $this->fields_match[$priority_field];
            
            $xpl_cpf = explode('.', $match_pr_fl);
            $main = $xpl_cpf[0];
            
            if (empty($this->tables)) {
                $this->defTables();
            }
            
            $joined = array_diff($this->tables, array($main));
            
            $sub_req_str .= $main;
            foreach ($joined as $j) {
                $sub_req_str .= ' JOIN '.$j.' ON '.$main.'.'.$j.'_id = '.$j.'.id';
            }
        }
        
        return $sub_req_str;
    }
    
    private function defTables() {
        
        $tables = array_keys($this->split_tbls_cols);
        $tables_tmp = [];
        foreach ($tables as $t) {
            $xpl_t = explode('...', $t);
            $tables_tmp[] = $xpl_t[0];
        }
        $this->tables = array_unique($tables_tmp);
    }
    
    private function firstPartSReqBuild($duplicates) {
        $i = 0;
        $sub_req_str = "";
        foreach($this->fields_match as $k => $v) {
            $i++;
            $xpl_fl = explode('.', $v);
            if ($this->req_return == self::ALL_FORM_FIELDS || ($this->req_return == self::SRCH_TABLES && in_array($xpl_fl[0], $this->srchd_tbls))) {
               $sub_req_str .= $v; 
            }
            
            if ($this->req_return == self::SRCH_TABLES && !in_array($xpl_fl[0], $this->srchd_tbls)) {
                if ($i == count($this->fields_match)) {
                    $sub_req_str = substr($sub_req_str, 0, -2);
                }
                continue;
            }
            
            $sub_req_str .= $this->rewriteDuplicates($duplicates, $xpl_fl[0], $xpl_fl[1]);
            if ($i < count($this->fields_match)) {
                $sub_req_str .= ', ';
            }
        }
        
        return $sub_req_str;
    }
    
    private function rewriteDuplicates($duplicates, $table, $col) {
        $sub_req_str = "";
        if (in_array($col, $duplicates)) {
            $sub_req_str .= " AS ";

            $sub_req_str .= $table."_";
            if ($col == "id") {
                $sub_req_str .= $table."_";
            }
            $sub_req_str .= $col;
        }
        return $sub_req_str;
    }
    
    private function checkFixFMatch() {
        if (!$this->fields_match) {
            $fields_match = [];
            $i = 1;
            foreach ($this->fields_pr as $field) {
                if (!preg_match(self::FIELD_PATTERN, $field)) {
                    return false;
                }
                $expl_field = explode('___', $field);
                $fields_match[$field] = $expl_field[0].'.'.$expl_field[1];
                $i++;
            }
            $this->fields_match = $fields_match;
            
        }
        return true;
    }
    
    private function defSplitTbCols($fields) {
        $split_tbls_cols = [];
        $i = 1;
        foreach($fields as $fl) {
            $xpl_fl = explode('.', $fl);
            $spl_tbl_col_k = $xpl_fl[0] . '...' .$i;
            $split_tbls_cols[$spl_tbl_col_k] = $xpl_fl[1];
            $i++;
        }
        $this->split_tbls_cols = $split_tbls_cols;
    }
}