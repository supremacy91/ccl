#!/usr/local/bin/php -q
<?php
set_time_limit(3600);
//error_reporting(0);
date_default_timezone_set('Europe/Amsterdam');

require_once(dirname(__FILE__)."/classes/config.php");
$aConfig = new config(); 
 
$classes = $aConfig->uri['classes'];

function __autoload($className){
    global $classes;
    if(file_exists(dirname(__FILE__)."/classes/".$className.".php")){
        require_once(dirname(__FILE__)."/classes/".$className.".php");
    }
} 

function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

$filename=''; 
$linelength=0;
$delimiter=',';
$enclosure='"';
$escape='\\';
$fields=29;


try{
	if(isset($_GET['position']) && is_numeric($_GET['position'])){
		$position = $_GET['position'];
	}else{
		$position = 0;
	}
	$linelimit = 100000; 
	$datestring = date("y-m-d");
	$filename_currentstock = dirname(__FILE__).'/icg/OHF_calexis_new_'.$datestring.'_v0.csv';
	if(!is_file($filename_currentstock)){
		$datestring = date('y-m-d', strtotime(' -1 days'));
		$filename_currentstock = dirname(__FILE__).'/icg/OHF_calexis_new_'.$datestring.'_v0.csv';
		if(!is_file($filename_currentstock)){
			//Send notification
			 $email = new email;
			 $to_email = "tjerk@firstaidmarketing.nl";
			 $to_name = "Tjerk Rintjema";
			 $subject = "Synchronization error";
			 $message = "There is no recent synch file. Contact ICG";
			 $email->send($to_name,$to_email,$subject,$message); 
			 //$to_email = "info@calexis.nl";
			 //$to_name = "Calexis Schoenmode";
			 //$email->send($to_name,$to_email,$subject,$message); 
			 exit();
			 //header("Location: index.php?view=products/index_import.php");
		}
	 }
	
	/* 
	
	LOADING IN REQUIRED CSV FILES FIRST (CATEGORIES, BRANDS, AND PREVIOUS STOCKFILE)
	
	*/
	
	$filename_categories = dirname(__FILE__).'/icg/Categories.csv';
	if(!is_file($filename_categories)){
		//Trigger exception
		 throw new Exception('No Category synch file. Contact ICG'); 
	 }
	$filename_brands = dirname(__FILE__).'/icg/Merk.csv';
	if(!is_file($filename_brands)){
		//Trigger exception
		 throw new Exception('No Brand synch file. Contact ICG'); 
	 }
	$filename_discount = dirname(__FILE__).'/icg/Discountlist.csv';
	if(!is_file($filename_discount)){
		//Trigger exception
		 throw new Exception('No Discountlist synch file'); 
	 }
	 $filename_normalized = dirname(__FILE__).'/icg/Normalizedcolors.csv';
	if(!is_file($filename_normalized)){
		//Trigger exception
		 throw new Exception('No Normalized Colors synch file'); 
	 }
	$filename_colorcodes = dirname(__FILE__).'/icg/Colorcodes.csv';
	if(!is_file($filename_colorcodes)){
		//Trigger exception
		 throw new Exception('No Color Codes synch file'); 
	 }
	$datestring = date('y-m-d', strtotime(' -1 days'));
	$filename_previousstock = dirname(__FILE__).'/icg/OHF_calexis_new_'.$datestring.'_v0.csv';
	 
	$category_array = array();
	$brands_array = array();
	$discount_array = array();
	$normalized_array = array();
	$colorcodes_array = array();
	$previousstock_array = array();
	//Categories
	//$startTime = microtime(true);
	if (($handle = fopen($filename_categories, "r")) !== FALSE) {
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=',';
		$enclosure='"';
		$escape='\\';
		$fields=2;
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				$currentrow = $data[0];
				if(empty($currentrow)){
					continue;
				}
				$id = $data[0];
				$name = $data[1];
				$category_array[$id] = $name;
				$previousrow = $currentrow;    
				
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
	unset($handle,$previousrow,$currentrow);
	//Brands
	//$startTime = microtime(true);
	if (($handle = fopen($filename_brands, "r")) !== FALSE) {
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=',';
		$enclosure='"';
		$escape='\\';
		$fields=2;
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				$currentrow = $data[0];
				if(empty($currentrow)){
					continue;
				}
				$id = $data[0];
				$name = $data[1];
				$brands_array[$id] = $name;
				$previousrow = $currentrow;    
				
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
	unset($handle,$previousrow,$currentrow);
	//Discountlist
	//$startTime = microtime(true);
	if (($handle = fopen($filename_discount, "r")) !== FALSE) {
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=';';
		$enclosure='"';
		$escape='\\';
		$fields=4;
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				$currentrow = $data[0];
				if(empty($currentrow)){
					continue;
				}
				$id = $data[0];
				$grp = $data[1];
				$discount = $data[3];
				$discount_array[$id] = $discount;
				$previousrow = $currentrow;    
				
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
	unset($handle,$previousrow,$currentrow);
	/* NORMALIZED COLORS */
	//$startTime = microtime(true);
	if (($handle = fopen($filename_normalized, "r")) !== FALSE) {
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=';';
		$enclosure='"';
		$escape='\\';
		$fields=2;
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				$currentrow = $data[0];
				if(empty($currentrow)){
					continue;
				}
				$color = $data[0];
				$normalized = $data[1];
				$normalized_array[$color] = $normalized;
				$previousrow = $currentrow;    
				
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
	unset($handle,$previousrow,$currentrow,$normalized,$color);
	/*  COLOR CODES */
	//$startTime = microtime(true);
	if (($handle = fopen($filename_colorcodes, "r")) !== FALSE) {
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=';';
		$enclosure='"';
		$escape='\\';
		$fields=2;
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				$currentrow = $data[0];
				if(empty($currentrow)){
					continue;
				}
				$color = $data[0];
				$code = $data[1];
				$colorcodes_array[$color] = $code;
				$previousrow = $currentrow;    
				
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
	unset($handle,$previousrow,$currentrow,$color,$code);
	
	
	$excludecollection = array('F/W15/16','F/W 14/15','2015 Zomer','SS16','','FW16');
	
	/*Previousstock */
	$previousstockfile = false;
	//Load in previous stock file, only when available
	if(is_file($filename_previousstock)){
		$previousstockfile = true;
		//$startTime = microtime(true);
		if (($handle = fopen($filename_previousstock, "r")) !== FALSE) {
			$previousrow = "";
			$currentrow = "";
			$row = 1;
			fseek($handle, $position);
			
			$linelength=0;
			$delimiter=',';
			$enclosure='"';
			$escape='\\';
			$fields=29;  //29
			while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
				$num = count($data);
				//Only continue when it has the required number of fields (and is according to format)
				if($num == $fields){
					if(!in_array($data[8],$excludecollection)){
					$currentrow = $data[1];
					if(empty($currentrow)){
						continue;
					}
					$id = $data[1];
					$grp = $data[2];
					$itemname = $data[3];
					$itemafdeling = $data[4];
					$itemsectie = $data[5];
					$itemfamilie = $data[6];
					$itemsubfamilie = $data[7];
					$categorykey = $itemafdeling.",".$itemsectie.",".$itemfamilie.",".$itemsubfamilie;
					$itemcategory = (array_key_exists($categorykey, $category_array) ? $category_array[$categorykey] : "");
					$itemcollection = $data[8];
					$itemsize = $data[9];
					$itemcolor = $data[10];
					/* Processing color
						1. Splitting the string by using the / seperator
						2. Then analyze the word by a given defined list of colors.
						3. Turn ZW into Zwart for example.
					*/
					if(array_key_exists($itemcolor, $normalized_array)){
						$normalizedcolor = $normalized_array[$itemcolor];
						if(array_key_exists($normalizedcolor, $colorcodes_array)){
							$normalizedcolorcode = $colorcodes_array[$normalizedcolor];
						}else{
							$normalizedcolorcode = "#ffffff";
						}
					}else{
						$normalizedcolor = $itemcolor;
						$normalizedcolorcode = "#ffffff";
					}
				
					$itembrand = (array_key_exists($data[11], $brands_array) ? $brands_array[$data[11]] : "");
					$itempurchaseprice = $data[12];
					$itemsellingprice = $data[13];
					$itemdiscountprice = (array_key_exists($id, $discount_array) ? $discount_array[$id] : '');
					$itemvat = $data[14];
					$itemsale = (!empty($itemdiscountprice) ? 'Sale' : 'New Collection');
					$itemstock = intval($data[15]);
					$item_material = $data[16];
					$item_sole = $data[17];
					$item_innersole = $data[18];
					$item_binnenvoering = $data[19];
					$item_uitneembaar = $data[20];
					$item_closing = $data[21];
					$item_heel = $data[22];
					$item_plateauniveau = $data[23];
					$item_sizefits = $data[24];
					$item_ean = $data[25];
					$item_origin = $data[26];
					$item_material2 = $data[27];
					$item_freetext = $data[28];
					
					//Create small standard product description
					$itemname = $itemname." in de kleur ".strtolower($itemcolor);
					//Item photo
					$itemphoto = array();
					$grpreplaced = str_replace(array("/"," "),array("-","_"),$grp);
					$colorreplaced = str_replace(array("/"," "),array("_","-"),$itemcolor);
					$itemphotodir = dirname(__FILE__).'/../pub/media/import/product-photos/IMG_'.$grpreplaced.'/IMG_'.$grpreplaced.'.'.$colorreplaced;
					if(is_dir($itemphotodir)){
						if ($photohandle = opendir($itemphotodir)) {		
							/* This is the correct way to loop over the directory. */
							while (false !== ($newentry = readdir($photohandle))) {
								$newfilename = dirname(__FILE__).'/../pub/media/import/product-photos/IMG_'.$grpreplaced.'/IMG_'.$grpreplaced.'.'.$colorreplaced.'/'.$newentry;
								if(is_file($newfilename)){
									$a = getimagesize($newfilename);
									$image_type = $a[2];
									if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))){
										$filename = 'https://calexis.nl/userfiles/files/product-photos/IMG_'.$grpreplaced.'/IMG_'.$grpreplaced.'.'.$colorreplaced.'/'.$newentry;
										//Add the file to the array of photos
										array_push($itemphoto, $filename);
									}	
								}
							}
						}
					} 
					sort($itemphoto);
					$itemphotostring = implode("|",$itemphoto);
					if(isset($photohandle)) closedir($photohandle);
					unset($newfilename, $a, $image_type, $photohandle, $itemphoto); 
					if(!isset($previousstock_array[$id])){
						//This is the first occurence of this itemid, so add to the array					
						if(!empty($itemphotostring)){
							$previousstock_array[$id] = 
								array(
								"ref"=> $id, 
								"grp" => $grp, 
								"name" => $itemname, 
								"category" => $itemcategory, 
								"collection" => $itemcollection, 
								"size" => $itemsize, 
								"color" => $normalizedcolor, 
								"brand" => $itembrand, 
								"purchaseprice" => $itemdiscountprice, 
								"sellingprice" => $itemsellingprice, 
								"vat" => $itemsale, 
								"stock" => $itemstock, 
								"material" => $item_material,
								"sole" => $item_sole,
								"innersole" => $item_innersole,
								"interior" => $item_binnenvoering,
								"demountable" => $item_uitneembaar,
								"closing" => $item_closing,
								"heel" => $item_heel,
								"plateaulevel" => $item_plateauniveau,
								"sizefits" => $item_sizefits,
								"ean" => $item_ean,
								"origin" => $item_origin,
								"material2" => $item_material2,
								"freetext" => $item_freetext,
								"photo" => $itemphotostring,
								"normalizedcolor" => $normalizedcolorcode);
						}
					}else{
						$previousstock_array[$id]['stock'] = $previousstock_array[$id]['stock'] + $itemstock;
					}
					
					
					$previousrow = $currentrow;    
					}
				}else{
					//Log error in file
					error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
					error_log(print_r($data),0);
				}
				$row++;
			}
			fclose($handle);
			//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
		}else{
			//Failed to open file
			error_log("Failed to open CSV file.",0);
		}	
	}
	unset($handle,$previousrow,$currentrow);
	/*
	
	NOW THE ITERATIONS STARTS 
	
	*/
	$productarray = array();
	//$startTime = microtime(true);
	if (($handle = fopen($filename_currentstock, "r")) !== FALSE) {
		
		$previousrow = "";
		$currentrow = "";
		$row = 1;
		fseek($handle, $position);
		
		$linelength=0;
		$delimiter=',';
		$enclosure='"';
		$escape='\\';
		$fields=29; //29
		while (($data = fgetcsv($handle, $linelength, $delimiter, $enclosure, $escape)) !== FALSE) {
			$num = count($data);
			//Only continue when it has the required number of fields (and is according to format)
			if($num == $fields){
				if(!in_array($data[8],$excludecollection)){
				//Add the row to the resultset    
				$currentrow = $data[1];
				if(empty($currentrow)){
					continue;
				}
				
				$id = (!empty($data[1]) ? $data[1] : 0);
				
				//Might have been added in the previousrow.
				if(isset($previousstock_array[$id])){
					//Remove this from previousstock array, as the only important is the current stock.
					unset($previousstock_array[$id]);
				}
				   
				$previousrow = $currentrow;    
				}
			}else{
				//Log error in file
				error_log("Row ".$row." has not been pushed, not according to format ".count($data),0);
				error_log(print_r($data),0);
			}
			$row++;
		}
		fclose($handle);
		
		//error_log("".$row." rows are processed and took ". number_format(( microtime(true) - $startTime), 4) ." Seconds",0); 
		//Now go over the previous stock items, as the remainder should be put on zero stock.
		foreach($previousstock_array as $key => $value){
			$id = $key;
			$grp = $previousstock_array[$key]['grp'];
			$name = $previousstock_array[$key]['name'];
			$category = $previousstock_array[$key]['category'];
			$collection = $previousstock_array[$key]['collection'];
			$size = $previousstock_array[$key]['size'];
			$color = $previousstock_array[$key]['color'];
			$brand = $previousstock_array[$key]['brand'];
			$purchaseprice = $previousstock_array[$key]['purchaseprice'];
			$sellingprice = $previousstock_array[$key]['sellingprice'];
			$vat = $previousstock_array[$key]['vat'];
			$stock = 0;
			$material = $previousstock_array[$key]['material'];
			$sole = $previousstock_array[$key]['sole'];
			$innersole = $previousstock_array[$key]['innersole'];
			$interior = $previousstock_array[$key]['interior'];
			$demountable = $previousstock_array[$key]['demountable'];
			$closing = $previousstock_array[$key]['closing'];
			$heel = $previousstock_array[$key]['heel'];
			$plateaulevel = $previousstock_array[$key]['plateaulevel'];
			$sizefits = $previousstock_array[$key]['sizefits'];
			$ean = $previousstock_array[$key]['ean'];
			$origin = $previousstock_array[$key]['origin'];
			$material2 = $previousstock_array[$key]['material2'];
			$freetext = $previousstock_array[$key]['freetext'];
			$photo = $previousstock_array[$key]['photo'];
			$normalizedcolor = $previousstock_array[$key]['normalizedcolor'];
			//Add zero stock to product array					
			if(!empty($photo)){
				$productarray[$id] = 
					array(
						"ref"=> $id, 
						"grp" => $grp,
						"name" => $name, 
						"category" => $category, 
						"collection" => $collection, 
						"size" => $size, 
						"color" => $color, 
						"brand" => $brand, 
						"purchaseprice" => $purchaseprice, 
						"sellingprice" => $sellingprice, 
						"vat" => $vat, 
						"stock" => $stock, 
						"material" => $material,
						"sole" => $sole,
						"innersole" => $innersole,
						"interior" => $interior,
						"demountable" => $demountable,
						"closing" => $closing,
						"heel" => $heel,
						"plateaulevel" => $plateaulevel,
						"sizefits" => $sizefits,
						"ean" => $ean,
						"origin" => $origin,
						"material2" => $material2,
						"freetext" => $freetext,
						"photo" => $photo,
						"normalizedcolor" => $normalizedcolor);
			}	
		}
		//Now write entries to a new file.
		if(!empty($productarray)){
			$fp = fopen(dirname(__FILE__).'/../var/import/cron/hour/smallsync-'.date('YmdHis').'.csv', 'w');
			$headerarray = array(
								"ref"=> "SKU", 
								"grp" => "GROUP", 
								"name" => "Short Description", 
								"category" => "Category", 
								"collection" => "Collection", 
								"size" => "Size", 
								"color" => "Color", 
								"brand" => "Brand", 
								"purchaseprice" => "Discount Price", 
								"sellingprice" => "Selling Price", 
								"vat" => "Discount", 
								"stock" => "Stock", 
								"material" => "Material",
								"sole" => "Sole",
								"innersole" => "Innersole",
								"interior" => "Interior",
								"demountable" => "Demountable",
								"closing" => "Closing",
								"heel" => "Heel",
								"plateaulevel" => "Plateaulevel",
								"sizefits" => "Sizefits",
								"ean" => "EAN",
								"origin" => "Origin",
								"material2" => "Material2",
								"freetext" => "Freetext",
								"photo" => "Photos",
								"normalizedcolor" => "Color Hex");
			fputcsv($fp, $headerarray);
			foreach ($productarray as $fields) {
				fputcsv($fp, $fields);
			}
			
			fclose($fp);
		}
	}else{
		//Failed to open file
		error_log("Failed to open CSV file.",0);
	}
		
	
	 $email = new email;
	 $to_email = "tjerk@firstaidmarketing.nl";
	 $to_name = "Tjerk Rintjema";
	 $subject = "File Prep succesful";
	 $message = "The zero stock import file is successfully prepped";
	 //$email->send($to_name,$to_email,$subject,$message); 
	 $to_email = "info@calexis.nl";
	 $to_name = "Calexis Schoenmode";
	 //$email->send($to_name,$to_email,$subject,$message); 
}catch (Exception $e){
	//Log errors
	 //Send error message
	 $email = new email;
	 $to_email = "tjerk@firstaidmarketing.nl";
	 $to_name = "Tjerk Rintjema";
	 $subject = "File Prep errors";
	 $message = "The following error(s) occurred:<br/>".$e->getMessage();
	 $email->send($to_name,$to_email,$subject,$message);     
	 error_log($e->getMessage(),0);
}

?>