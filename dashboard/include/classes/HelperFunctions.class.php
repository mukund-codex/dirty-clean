<?php
	/*
	 * HelperFunctions
	 * v1.0.1
	 * This class is only for generic helper methods and no database actions are made here
	 */
	class HelperFunctions{

		// == COMMON HELPER FUNCTIONS ==
		function getValidatedPermalink($permalink){ // v2.0.0
			$permalink = trim($permalink, '()');
			$replace_keywords = array("-:-", "-:", ":-", " : ", " :", ": ", ":",
				"-@-", "-@", "@-", " @ ", " @", "@ ", "@", 
				"-.-", "-.", ".-", " . ", " .", ". ", ".", 
				"-\\-", "-\\", "\\-", " \\ ", " \\", "\\ ", "\\",
				"-/-", "-/", "/-", " / ", " /", "/ ", "/", 
				"-&-", "-&", "&-", " & ", " &", "& ", "&", 
				"-,-", "-,", ",-", " , ", " ,", ", ", ",", 
				" ",
				"---", "--", " - ", " -", "- ",
				"-#-", "-#", "#-", " # ", " #", "# ", "#",
				"-$-", "-$", "$-", " $ ", " $", "$ ", "$",
				"-%-", "-%", "%-", " % ", " %", "% ", "%",
				"-^-", "-^", "^-", " ^ ", " ^", "^ ", "^",
				"-*-", "-*", "*-", " * ", " *", "* ", "*",
				"-(-", "-(", "(-", " ( ", " (", "( ", "(",
				"-)-", "-)", ")-", " ) ", " )", ") ", ")",
				"-;-", "-;", ";-", " ; ", " ;", "; ", ";",
				"-'-", "-'", "'-", " ' ", " '", "' ", "'",
				"-?-", "-?", "?-", " ? ", " ?", "? ", "?",
				'-"-', '-"', '"-', ' " ', ' "', '" ', '"',
				"-!-", "-!", "!-", " ! ", " !", "! ", "!");
			$escapedPermalink = str_replace($replace_keywords, '-', $permalink); 
			return strtolower($escapedPermalink);
		}
		function getActiveLabel($isActive){
			if($isActive){
				return 'Yes';
			} else {
				return 'No';
			}
		}
		function limitDescText($content, $charLength){
			if(strlen($content) > $charLength){
				return substr($content, 0, $charLength).'...';
			} else {
				return $content;
			}
		}
		function getCommaFormatedStringWithLastCommaReplacedWithAnd($displayArr){
			if(count($displayArr) > 0){
				$displayStr = implode(", ", $displayArr);

				if(count($displayArr) > 1){
					$lastComma = strpos($displayStr, ", ", strlen($displayStr) - strlen($displayArr[count($displayArr) - 1]) - 2);
					$displayStr = substr_replace($displayStr, " and ", $lastComma, 2);
				}

				return $displayStr;
			} else {
				return "";
			}
		}
		function getCommaFormatedStringWithLastCommaReplacedWithOr($displayArr){
			if(count($displayArr) > 0){
				$displayStr = implode(", ", $displayArr);

				if(count($displayArr) > 1){
					$lastComma = strpos($displayStr, ", ", strlen($displayStr) - strlen($displayArr[count($displayArr) - 1]) - 2);
					$displayStr = substr_replace($displayStr, " or ", $lastComma, 2);
				}

				return $displayStr;
			} else {
				return "";
			}
		}
		static function isValidEmailAddress($email){
			return preg_match("/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/", $email);
		}
		// == COMMON HELPER FUNCTIONS ==

		// == IMAGE HELPER FUNCTIONS PER PROJECT ==
		function getImageDir($imageFor, $dirPrefix = ""){
			switch($imageFor){
				case "user":
					return $dirPrefix."images/content/user/"; // add / at end
					break;
				case "studio_pic":
					return $dirPrefix."images/content/studio_pic/"; // add / at end
					break;
				case "designs":
					return $dirPrefix."images/content/designs/"; // add / at end
					break;
				case "category":
					return $dirPrefix."images/category/"; // add / at end
					break;
				case "category_styles":
					return $dirPrefix."images/content/category_styles/"; // add / at end
					break;
				case "reference_files":
					return $dirPrefix."images/content/reference_files/"; // add / at end
					break;
				case "attachments":
					return $dirPrefix."images/content/attachments/"; // add / at end
					break;
				case "clients":
					return $dirPrefix."images/clients/"; // add / at end
					break;
				case "design_submissions":
					return $dirPrefix."images/content/design_submissions/"; // add / at end
					break;
				case "content_submissions":
					return $dirPrefix."images/content/content_submissions/"; // add / at end
					break;
				case "final_src_files":
					return $dirPrefix."images/content/final_src_files/"; // add / at end
					break;
				case "post_brief":
					return $dirPrefix."images/content/postBrief_images/"; // add / at end
					break;
				case "interior_post_brief":
					return $dirPrefix."images/content/interior_postBrief_images/"; // add / at end
					break;
				default:
					return false;
					break;
			}
		}
		function getImageUrl($imageFor, $fileName, $imageSuffix, $dirPrefix = ""){
			$fileDir = $this->getImageDir($imageFor, $dirPrefix);
			if($fileDir === false){ // custom directory not found, error!
				$fileDir = "../images/"; // add / at end
				$defaultImageUrl = $fileDir."default.jpg";
				return $defaultImageUrl;
			} else { // process custom directory
				$defaultImageUrl = $fileDir."default.jpg";
				if(empty($fileName)){
					return $defaultImageUrl;
				} else {
					$image_name = strtolower(pathinfo($fileName, PATHINFO_FILENAME));
					$image_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
					if(!empty($imageSuffix)){
						$imageUrl = $fileDir.$image_name."_".$imageSuffix.".".$image_ext;
					} else {
						$imageUrl = $fileDir.$image_name.".".$image_ext;
					}
					if(file_exists($imageUrl)){
						return $imageUrl;
					} else {
						return $defaultImageUrl;
					}
				}
			}
		}
		function unlinkImage($imageFor, $fileName, $imageSuffix, $dirPrefix = ""){
			$fileDir = $this->getImageDir($imageFor, $dirPrefix);
			if($fileDir === false){ // custom directory not found, error!
				return false;
			} else { // process custom directory
				$defaultImageUrl = $fileDir."default.jpg";

				$imagePath = $this->getImageUrl($imageFor, $fileName, $imageSuffix, $dirPrefix);
				if($imagePath != $defaultImageUrl){
					$status = unlink($imagePath);
					return $status;
				} else {
					return false;
				}
			}
		}
		// == IMAGE HELPER FUNCTIONS PER PROJECT ==

		// == DATE TIME HELPER FUNCTIONS ==
		function formatTimeRemainingInText($dateTime, $isComplete = false){
			if($isComplete){
				return "<strong>Complete!</strong>";
			} else if(!empty($dateTime)){
				$timestampDiff = strtotime($dateTime) - time();
				if($timestampDiff <=0 ){ // over due
					$then = new DateTime($dateTime);
					$now = new DateTime();
					$sinceThen = $now->diff($then);

					if($sinceThen->y > 0){
						return '<strong class="text-danger">'.$sinceThen->y." year(s) over due</strong>";
					}
					if($sinceThen->m > 0){
						return '<strong class="text-danger">'.$sinceThen->m." month(s) over due</strong>";
					}
					if($sinceThen->d > 0){
						return '<strong class="text-danger">'.$sinceThen->d." day(s) over due</strong>";
					}
					if($sinceThen->h > 0){
						return '<strong class="text-danger">'.$sinceThen->h." hour(s) over due</strong>";
					}
					if($sinceThen->i > 0){
						return '<strong class="text-danger">'.$sinceThen->i." minutes(s) over due</strong>";
					}
				} else { // time remaining
					$then = new DateTime($dateTime);
					$now = new DateTime();
					$sinceThen = $now->diff($then);

					if($sinceThen->y > 0){
						return $sinceThen->y." year(s) left";
					}
					if($sinceThen->m > 0){
						return $sinceThen->m." month(s) left";
					}
					if($sinceThen->d > 0){
						return $sinceThen->d." day(s) left";
					}
					if($sinceThen->h > 0){
						return '<strong class="text-danger">'.$sinceThen->h."</strong> hour(s) remaining";
					}
					if($sinceThen->i > 0){
						return '<strong class="text-danger">'.$sinceThen->i."</strong> minutes(s) remaining";
					}
				}

			} else {
				return "-";
			}
		}
		function formatDateTime($dateTime, $defaultFormat = "d M, Y h:i a T"){
			if(empty($dateTime)){
				return "-";
			} else {
				return date($defaultFormat, strtotime($dateTime));
			}
		}
		function formatDate($dateTime, $defaultFormat = "d M, Y T"){
			if(empty($dateTime)){
				return "-";
			} else {
				return date($defaultFormat, strtotime($dateTime));
			}
		}
		function formatDates($dateTime, $defaultFormat = "d M, Y"){
			if(empty($dateTime)){
				return "-";
			} else {
				return date($defaultFormat, strtotime($dateTime));
			}
		}
		function formatTime($dateTime, $defaultFormat = "h:i a T"){
			if(empty($dateTime)){
				return "-";
			} else {
				return date($defaultFormat, strtotime($dateTime));
			}
		}
		// == DATE TIME HELPER FUNCTIONS ==

		// == NUMBER FORMAT ==
		function formatAmount($amount){
			$amount = (float) $amount;
			return number_format($amount,  2, '.', ',');
		}
		function formatAmountForTotal($amount){
			$amount = (float) $amount;
			$amount = round($amount);
			return number_format($amount,  2, '.', ',');
		}
		function formatNumberAsText($number){
			$numberLength = strlen($number);

			if($numberLength > 3){
				$number = (float) $number;
				$multiplier = 1;
				$suffix = "";
				switch($numberLength){
					case 4:
					case 5:
					case 6:
						$multiplier = 1000;
						$suffix = "K";
						break;
					case 7:
					case 8:
					case 9:
						$multiplier = 1000000;
						$suffix = "M";
						break;
					case 10:
					case 11:
					case 12:
						$multiplier = 1000000000;
						$suffix = "B";
						break;
				}
				$number = $number / $multiplier;
				$number = number_format($number,  1, '.', '');
				$number = $number.$suffix;
			}
			return $number;
		}
		// == NUMBER FORMAT ==
		// Amount Number to string 
		function getCurrency($number)
		{   
			$number = round($number);
			$decimal = round($number - ($no = floor($number)), 2) * 100;
			$hundred = null;
			$digits_length = strlen($no);
			$i = 0;
			$str = array();
			$words = array(0 => '', 1 => 'one', 2 => 'two',
				3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
				7 => 'seven', 8 => 'eight', 9 => 'nine',
				10 => 'ten', 11 => 'eleven', 12 => 'twelve',
				13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
				16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
				19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
				40 => 'forty', 50 => 'fifty', 60 => 'sixty',
				70 => 'seventy', 80 => 'eighty', 90 => 'ninety');
			$digits = array('', 'hundred','thousand','lakh', 'crore');
			while( $i < $digits_length ) {
				$divider = ($i == 2) ? 10 : 100;
				$number = floor($no % $divider);
				$no = floor($no / $divider);
				$i += $divider == 10 ? 1 : 2;
				if ($number) {
					$plural = (($counter = count($str)) && $number > 9) ? 's' : null;
					$hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
					$str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
				} else $str[] = null;
			}
			$Rupees = implode('', array_reverse($str));
			$paise = ($decimal) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
			//return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise;
			return ucfirst(($Rupees ? $Rupees . '' : '') . $paise);
		}
		
	}
?>