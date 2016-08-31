<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ParserModel extends CI_Model {

        public $title;
        public $content;
        public $date;

        public function __construct()
        {
                // Call the CI_Model constructor
                parent::__construct();
				$this->load->library('simple_html_dom');
        }

        public function get_berlin()
        {
			
			   $query = $this->db->get('places');

				foreach ($query->result() as $row)
				{
					
						$address_tel_fax_mail = $row->address;
						//convert address to lat &lon
						
						
						$keys = array("Tel","Fax","Mail");
						//gettingAllPositions
						$telPos = stripos($address_tel_fax_mail, 'Tel');
						$faxPos = stripos($address_tel_fax_mail, 'Fax');
						$mailPos = stripos($address_tel_fax_mail, 'Mail');
						
						
						//remove tel
						$telPos = stripos($address_tel, 'Tel');
						if($telPos >-1){
							$address = substr($address_tel,0,$telPos);
							$tel = substr($address_tel,$telPos+5);
							echo "</br>";
							echo $address;
							echo "</br>";
							
							$faxPos = stripos($tel, 'Fax');
							if($faxPos > -1){
								$tel = substr($tel,0,$faxPos);
								$fax = substr($tel,$faxPos);
								
							}
							if(isset($fax)){
								$mailPos = stripos($fax, 'Mail');
							}
							if($mailPos > -1){
								$fax = substr($fax,0,$mailPos);
								$mail = substr($fax,$mailPos);
							}
							
							echo $tel;
							echo "</br>";
							if(isset($fax)){ 
								echo $fax;
								echo "</br>";
							}
							if(isset($mail)){ 
								echo $mail;
								echo "</br>";
							}
						}
						//insert to db
						
						
						
				}
		}

       

}