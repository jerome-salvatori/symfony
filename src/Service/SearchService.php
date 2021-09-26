<?php

namespace App\Service;

use App\Service\MultiFieldSearchServiceModLike;

class SearchService {
    public function buildSQLR($data) {
        $array_data = explode(' ', $data);
        $art_fields = ["titre", "auteur", "contenu"];
        
        $multi_data = [];
        $ar_d_size = count($array_data);
        $prio_1 = [];
        $prio_2 = [];
        $prio_3 = [];
        $fields_match = [];
        for ($i = 0; $i < $ar_d_size; $i++) {
            $multi_val = "%".trim($array_data[$i])."%";
            foreach ($art_fields as $k => $val) {
                $nb_prio = $k + 1;
                $prio_array = "prio".strval($nb_prio);
                $multi_key = $val . strval($i);
                $$prio_array[] = $multi_key;
                if ($val == "titre") {
                    $fields_match[$multi_key] = "article.titre";
                } else if ($val == "auteur") {
                    $fields_match[$multi_key] = "utilisateur.username";
                } else if ($val == "contenu") {
                    $fields_match[$multi_key] = "article.contenu";
                }
                $multi_data[$multi_key] = $multi_val;
            }
        }
        
        $prios = array_merge($prio1, $prio2, $prio3);
        $cols_result = ["article.id", "article.auteur_id", "article.date_publication", "article.date_modif", "article.titre", "article.contenu"];
        $table_joins = ["main" => "article", "joined" => [
                                "utilisateur" => [
                                    "article.auteur_id" => "utilisateur.id"
                                ]
                            ]
                       ];
        
        $multi_serv = new MultiFieldSearchServiceModLike(false, $prios, $cols_result, $fields_match, $table_joins);
        
        return $multi_serv->buildSQLR($multi_data);
        /*$sql = "SELECT DISTINCT t.* FROM (SELECT * FROM articles WHERE ";
        $i = 0;
        foreach ($array_data as $srch_wrd) {
            $clean_srw = trim($srch_wrd);
            
            if ($i > 0) {
                $sql .= "OR ";
            }
            
            $sql .= ""
        }*/
        
        
    }
}