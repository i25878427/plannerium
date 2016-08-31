<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DocumentModel extends CI_Model {

       private $stopwords = array("a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the","city","berlin");
	   
	   	private $threshold = 0.5;
        public function __construct()
        {
                // Call the CI_Model constructor
                parent::__construct();
        }
		public function termFrequency(){
			$query = $this->db->get('places');
			foreach ($query->result() as $row)
			{
				$total = 0;
				$termArray = array();
				$content = strtolower($row->content);
				$special_signs1 = array(".", ",", ":", "?", "!","–","-","(",")","_",";");
				$content = str_replace($special_signs1, " ", $content);
				$special_signs2 = array("'", "`","\"","’","‘","€","”","“","*");
				$content = str_replace($special_signs2, "", $content);
				$content = str_replace("  ", " ", $content);
				$contentArray = explode(" ",$content);
				for($i=0; $i<count($contentArray);$i++){
					$key = trim($contentArray[$i]);
					if($key != "" && strlen($key)>2){
						//echo $key_res;
						//if($key_res != ""){
						//need save all synon.. of similair words

						$key_res = $this->searchInArray($key,array_column($termArray,'word'),$this->threshold);
						if($key_res != ""){
							$key_index = array_search($key_res,array_column($termArray,'word'));
							$value = $termArray[$key_index]['frequency'];
							$value = intval($value) + 1;
							$termArray[$key_index]['frequency'] = $value;
						}
						else{
							if (!in_array($key,$this->stopwords))
								array_push($termArray, array('place_id' =>$row->place_id ,'word' => $key , 'frequency' =>1, 'tf' =>0));
						}
						if (!in_array($key,$this->stopwords))
							$total = intval($total) + 1;
					}
					
				}
				if(!empty($termArray)){
					for($j=0;$j<count($termArray);$j++){
						$termArray[$j]['tf'] = 	intval($termArray[$j]['frequency']) / intval($total);
					}
					
					$this->db->insert_batch('dyn_places_tf', $termArray);
				}
			}
			$this->updateIDF();
		}
		private function searchInArray($key,$termArray,$threshold){
			$stop = false;
			$keyAns = "";
			for($i=0 ; $i<count($termArray) && !$stop ; $i++){
				$stop = $this->compareWord($key,$termArray[$i],$threshold);
				if($stop){
					//echo "$termArray[$i]: " . $termArray[$i];
					$keyAns = $termArray[$i];
				}
			}
			return $keyAns;
		}
		private function updateIDF(){
			$query = $this->db->query("call cursor_idf()");	
			
		}
		public function placeToPlace(){
			$sql = "select DISTINCT place_id from places"; 
			$query = $this->db->query($sql, array());
			$max = 8;
			$queryString = "(SELECT place_id,word from dyn_places_tf where place_id =";
			$queryArray = array();
			$arrayGroup = array();
			foreach($query->result() as $group){
				array_push($queryArray,$queryString ."'". $group->place_id . "'" ." order by tf_idf DESC limit ".$max .")");
				if(!in_array($group->place_id,$arrayGroup)){
					array_push($arrayGroup,$group->place_id);
				}
			}
			$unionQuery = implode(" UNION ",$queryArray);
			$queryTopInGroup = $this->db->query($unionQuery, array());
			$mapPlaces = array();
			foreach($queryTopInGroup->result() as $row){
				if(isset($mapPlaces[$row->place_id])){
					array_push($mapPlaces[$row->place_id],$row->word);
				}
				else{
					$mapPlaces[$row->place_id]= array($row->word);
				}
			}
			$result = array();
			for($i=0; $i<count($arrayGroup);$i++){
				for($j=$i+1;$j<count($arrayGroup);$j++){
					if(isset($mapPlaces[$arrayGroup[$i]]) && isset( $mapPlaces[$arrayGroup[$j]])){
						$match = $this->compare2Places($mapPlaces[$arrayGroup[$i]], $mapPlaces[$arrayGroup[$j]],$this->threshold);
						array_push($result,array("place_id_1" => $arrayGroup[$i] , "place_id_2" => $arrayGroup[$j], "similarity" =>count($match), "words"=> implode(",",$match) ));
					}
				}
			}
			$this->db->insert_batch('dyn_places_similarity', $result);
		}
		
		private function compare2Places($place1,$place2,$threshold){
			$match = array();
			for($i=0;$i<count($place1);$i++){
				for($j=0;$j<count($place2);$j++){
					$this->compareWord($place1[$i],$place2[$j],$threshold);
				}
				if(in_array($place1[$i],$place2)){
					array_push($match,$place1[$i]);
				}
			}
			return $match;
		}
		private function compareWord($word1,$word2,$threshold){
			$word1 = strtolower($word1);
			$word2 = strtolower($word2);		
			$len = strlen($word1);
			
			if($len > strlen($word2)){
				$len = strlen($word2);
			}
			
			$matchCounter=0;
			for($i=0;$i<$len;$i++){
				if($word1[$i] == $word2[$i]){
					$matchCounter++;
				}
			}
			return (($matchCounter/$len) >= $threshold);
		}
}