<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!function_exists('getSettingVal')) {

    /**
     * (CUSTOME FUNCTION) It will return master setting value.
     * DB has 3 columns Id, Key & Value.
     * where, 
     *      $key = Key &
     *      and Value will be Return
     * 
     * @param String $key is unique slug of row
     * @return String
     */
    function getSettingVal($key) {
        $CI = & get_instance();
        $result = $CI->db->select('value')->where("key = '$key'")->get('custom_fields')->row();
        return (isset($result->value) && !empty($result->value)) ? $result->value : '';
    }

}

if (!function_exists('build_list')) {

    /**
     * (CUSTOME FUNCTION) It will build HTML ul & li tags for menu from array.
     * 
     * @param Array $arr has menu list
     * @return String
     */
    function build_list($arr) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) { //recurse child
                $out .= build_list($value);
                $out .= '</li>' . "\n";
            } else {
                if ($key == 0) { //begin new branch
                    $out = '<ul';
                    $out .= ($value) ? ' class="' . $value . '"' : ''; //branch css class
                    $out .= '>' . "\n";
                } else { //build list items
                    $attr = explode('|', $value);
                    $css = (isset($attr[1])) ? ' class="' . $attr[1] . '"' : ''; //item css class

                    $out .= '<li' . $css . '>';
                    $out .= (isset($attr[2]) && $attr[2] != '') ? anchor($attr[2], $attr[0]) : $attr[0];
                    $out .= (!isset($attr[3])) ? '</li>' : '';
                    //$out .= '</li>';
                    $out .= "\n";
                }
            }
        }

        return $out . '</ul>' . "\n";
    }

}

if (!function_exists('in_array_r')) {

    function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

}

if (!function_exists('razorpayCustomerAddUpdate')) {

    function razorpayCustomerAddUpdate($post, $sys_id) {
        $CI = & get_instance();

        $CI->load->library('razorpay');
        $CI->load->model('table/bank_accounts', 'bank_accounts');

        $name = $post->name;
        $email = $post->email;
        $phone = $post->phone;

        if ($post->type_client != 'Walk In Customer') {
            if ($post->organization_name != '') {
                $name = $post->organization_name;
            }
            /* if ($post->organization_email != ''){
              $email = $post->organization_email;
              }
              if ($post->organization_phone != ''){
              $phone = $post->organization_phone;
              } */
            if ($post->gst_no != '') {
                $phone = $sys_id;
                for ($i = strlen($phone); $i < 8; $i++) {
                    if (strlen($phone) == 7) {
                        $phone = '1' . $phone;
                    } else {
                        $phone = '0' . $phone;
                    }
                }
            }
        }

        $client = $CI->db->where(['cus_sys_id' => $sys_id])->get('razorpay_customer')->row();
        
        $createVaAccount = false;
        $rz_cus_id = null;

        if (isset($client->cus_razor_id) && $client->cus_razor_id != NULL && $client->cus_razor_id != '') {
            //$info = $CI->razorpay->customerEdit($name, $email, $phone, $client->cus_razor_id);
            //echo $sys_id;exit();
            if(isset($client->va_razor_id) && $client->va_razor_id != NULL && $client->va_razor_id != ''){
            }else{
            	$rz_cus_id = $client->cus_razor_id;
            	$createVaAccount = true;
            	$CI->db->delete('razorpay_customer', [
			    'cus_sys_id' => $sys_id
			]);
            }
        } else {
            //echo substr($name, 0, 50).' - ' .$sys_id;
            $info = $CI->razorpay->customerCreate(substr($name, 0, 50), $email, $phone, $sys_id);
            //print_r($info);exit();
            if ($info == 'Customer already exists for the merchant') {
                echo 'Customer already exists for the merchant';
                exit();
            }else{
		$rz_cus_id = $info->id;
            	$createVaAccount = true;
            }
        }
        
        if($createVaAccount) {
                $va_info = $CI->razorpay->virtualAccountCreate($rz_cus_id, substr($name, 0, 50));
                //print_r($va_info);exit();
                $bank = null;
                $vpa = null;
                if($va_info->receivers[0]->entity == 'bank_account'){
                    $bank= $va_info->receivers[0];
                    $vpa = $va_info->receivers[1];
                }else{
                    $bank= $va_info->receivers[1];
                    $vpa = $va_info->receivers[0];
                }
                $data = array(
                        'cus_razor_id'      => $rz_cus_id,
                        'cus_sys_id'        => $sys_id,
                        'va_razor_id'       => $va_info->id,
                        'description'       => $va_info->description,
                        'bank_name'         => $bank->bank_name,
                        'beneficiary_name'  => $bank->name,
                        'account_number'    => $bank->account_number,
                        'ifsc'              => $bank->ifsc,
                        'upi_id'            => $vpa->address,
                        'vpaurl'            => get_unique_str($sys_id)
                );
                $CI->db->insert('razorpay_customer', $data);

                $bank_accounts = array(
                    'type_id'   => $sys_id,
                    'name'      => $bank->bank_name,
                    'branch'    => 'Razorpay',
                    'acc_name'  => $bank->name,
                    'acc_no'    => $bank->account_number,
                    'ifsc'      => $bank->ifsc,
                );
                $CI->bank_accounts->addUpdateDefault($bank_accounts);
            }
    }

}

if (!function_exists('multiarray_sort')) {

    function multiarray_sort($records, $field, $reverse = false) {

        $hash = array();

        $i = 0;
        foreach ($records as $record) {
            $hash[$record[$field] . '_' . $i] = $record;
            $i++;
        }

        ($reverse) ? ksort($hash) : krsort($hash);

        $records = array();

        foreach ($hash as $record) {
            $records [] = $record;
        }

        return $records;
    }

}

if (!function_exists('arrayToObject')) {

    function arrayToObject($d) {
        if (is_array($d)) {
            /*
             * Return array converted to object
             * Using __FUNCTION__ (Magic constant)
             * for recursive call
             */
            //return (object) array_map([__CLASS__, __METHOD__], $d);
            return (object) array_map(__FUNCTION__, $d);
        } else {
            // Return object
            return $d;
        }
    }

}

if (!function_exists('accountEntry')) {

    function accountEntry($entry) {
        $CI = & get_instance();

        $data = array();
        //Main Entry
        $tmp = array(
                'type' => $entry['type'],
                'narration' => $entry['narration'],
                'date' => $entry['date'],
                'ekey' => $entry['ekey']
        );
        $CI->db->insert('acc_entry', $tmp);
        $id = $CI->db->insert_id();

        // Ledger Entry
        $tmp = array(
                'entry_id' => $id,
                'dr_cr' => 'dr',
                'ledger' => $entry['dr_ledger'],
                'ledger_group' => $entry['dr_group'],
                'amount' => $entry['amount']
        );
        array_push($data, $tmp);

        $tmp = array(
                'entry_id' => $id,
                'dr_cr' => 'cr',
                'ledger' => $entry['cr_ledger'],
                'ledger_group' => $entry['cr_group'],
                'amount' => $entry['amount']
        );
        array_push($data, $tmp);

        if (!empty($data))
            $CI->db->insert_batch('acc_entry_ledger', $data);
    }

}

if (!function_exists('accountEntryBulk')) {

	function accountEntryBulk($entries) {
		$CI = & get_instance();

		$maxId = $CI->db->select_max('id')->get('acc_entry')->row()->id;

		$dataE = array();
		$data = array();
		foreach ($entries as $entry){
			$maxId++;
			//Main Entry
			$tmp = array(
				'id'		=> $maxId,
				'type' 		=> $entry['type'],
				'narration' => $entry['narration'],
				'date' 		=> $entry['date'],
				'ekey' 		=> $entry['ekey']
			);
			array_push($dataE, $tmp);
			/*$CI->db->insert('acc_entry', $tmp);
			$id = $CI->db->insert_id();*/

			// Ledger Entry
			$tmp = array(
				'entry_id' => $maxId,
				'dr_cr' => 'dr',
				'ledger' => $entry['dr_ledger'],
				'ledger_group' => $entry['dr_group'],
				'amount' => $entry['amount']
			);
			array_push($data, $tmp);

			$tmp = array(
				'entry_id' => $maxId,
				'dr_cr' => 'cr',
				'ledger' => $entry['cr_ledger'],
				'ledger_group' => $entry['cr_group'],
				'amount' => $entry['amount']
			);
			array_push($data, $tmp);
		}

		if (!empty($data)){
			//print_r($data);
			$CI->db->insert_batch('acc_entry', $dataE,NULL,3000);
			//echo $CI->db->last_query();
			//echo '<Br/><Br/><Br/>';
			$CI->db->insert_batch('acc_entry_ledger', $data,NULL,3000);
			//echo $CI->db->last_query();
		}

	}

}

if (!function_exists('table_links')) {

    function table_links($data) {

        $CI = & get_instance();

        $records = '';

        $btn_group = FALSE;
        foreach ($data as $val) {
            if (!(in_array($CI->session->type, $val['block-user']))) {

                if (isset($val['btn-group'])) {
                    if ($val['btn-group'] == 'start') {
                        $btn_group = TRUE;
                        $records .= '<div class="btn-group">
                        <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" role="menu">';
                    } else if ($val['btn-group'] == 'end') {
                        $btn_group = FALSE;
                        $records .= '</ul> </div>';
                    }
                    continue;
                }

                if ($btn_group)
                    $records .= '<li>';

                $records .= '<a href="' . $val['url'] . '"';

                if (isset($val['onclick']) && $val['onclick'] != '')
                    $records .= ' onclick="' . $val['onclick'] . '"';

                if (isset($val['title']) && $val['title'] != '')
                    $records .= ' title="' . $val['title'] . '"';

                if (isset($val['class']) && $val['class'] != '')
                    $records .= ' class="' . $val['class'] . '"';

                $records .= '>';

                if (isset($val['icon']) && $val['icon'] != '')
                    $records .= '<i class="' . $val['icon'] . '"></i>';

                $records .= $val['text'] . '</a> ';

                if ($btn_group)
                    $records .= '</li>';
            }
        }

        return $records;
    }

}

if (!function_exists('bookingTableLinks')) {

	function bookingTableLinks($data) {

		$CI = & get_instance();

		$CI->load->library('menus');

		return $CI->menus->bookingMenu($data);
	}

}

if (!function_exists('daCalculator')) {

    /**
     * (CUSTOME FUNCTION) it will calculate DA(Driver Allowance)
     * 
     * @param int $did is driver id
     * @param date $fromdate is start date in dd-mm-yyyy format
     * @param date $todate is end date in dd-mm-yyyy format
     * @param int $noIncludeId is current id where from checking DA (Id of smtt_booking_settlement_driver)
     * @param int $booking_id booking id
     * @return array ['da'] amount and $return['breakup'] of da day wise
     */
    function daCalculator($did, $fromdate, $todate, $noIncludeId, $booking_id) {
        $CI = & get_instance();

        $return = array();
        $return['da'] = 0;

        $daAmount = getSettingVal('da');

        $settlement_info = $CI->db->select('startdate,enddate')->where(['booking_id' => $booking_id])
                                        ->get("booking_settlement")->row();

        //var_dump($settlement_info);
        $period = getDatesFromRange($fromdate, $todate);
        foreach ($period as $value) {
            //echo $value.'<br/>';
            //}
            //for ($x=strtotime($fromdate);$x<=strtotime($todate);$x+=86400){
            //$date =  date('Y-m-d',$x);
            $date = $value;
            //echo $date .' = '.$daAmount.'<br/>';
            $flag = 1;

            $result = $CI->db->query("SELECT bsd.id, bsd.booking_id, bsd.from_date, bsd.to_date, bsd.driver_allowance, bsd.vehicle_name, bs.startdate, bs.enddate FROM smtt_booking_settlement_driver AS bsd LEFT JOIN smtt_booking_settlement AS bs ON (bs.booking_id = bsd.booking_id) WHERE ( '$date' BETWEEN bsd.from_date AND bsd.to_date ) AND bsd.driver_id = $did AND bsd.id != $noIncludeId")->result();
            //echo $CI->db->last_query().'<br/>';
            foreach ($result as $val) {

                if (date('Y-m-d', $val->startdate) == $date) {
                    if ($val->startdate >= strtotime($date . ' 02:00 AM')) {
                        $flag = daCalculatorFn($daAmount, $val->from_date, $val->to_date, $val->driver_allowance);
                        if ($flag == 0) {
                            $return['breakup'][$date]['booking'] = $val->booking_id;
                            $return['breakup'][$date]['vehicle_name'] = $val->vehicle_name;
                            $return['breakup'][$date]['from_date'] = $val->from_date;
                            $return['breakup'][$date]['to_date'] = $val->to_date;
                            $return['breakup'][$date]['driver_allowance'] = $val->driver_allowance;
                            break;
                        }
                    } else {
                        $flag = 0;
                    }
                } else if (date('Y-m-d', $val->enddate) == $date) {
                    if ($val->enddate >= strtotime($date . ' 02:00 AM')) {

                        $flag = daCalculatorFn($daAmount, $val->from_date, $val->to_date, $val->driver_allowance);
                        if ($flag == 0) {
                            $return['breakup'][$date]['booking'] = $val->booking_id;
                            $return['breakup'][$date]['vehicle_name'] = $val->vehicle_name;
                            $return['breakup'][$date]['from_date'] = $val->from_date;
                            $return['breakup'][$date]['to_date'] = $val->to_date;
                            $return['breakup'][$date]['driver_allowance'] = $val->driver_allowance;
                            break;
                        }
                    } else {
                        $flag = 0;
                    }
                } else {
                    $flag = daCalculatorFn($daAmount, $val->from_date, $val->to_date, $val->driver_allowance);
                    if ($flag == 0) {
                        $return['breakup'][$date]['booking'] = $val->booking_id;
                        $return['breakup'][$date]['vehicle_name'] = $val->vehicle_name;
                        $return['breakup'][$date]['from_date'] = $val->from_date;
                        $return['breakup'][$date]['to_date'] = $val->to_date;
                        $return['breakup'][$date]['driver_allowance'] = $val->driver_allowance;
                        break;
                    }
                }
            }

            if (count((array) $result) == 0) {
                if (date('Y-m-d', $settlement_info->startdate) == $date) {
                    if (($settlement_info->startdate >= strtotime($date . ' 02:00 AM')) || ($settlement_info->enddate >= strtotime($date . ' 02:00 AM'))) {
                        $flag = 1;
                    } else {
                        $flag = 0;
                    }
                } else if (date('Y-m-d', $settlement_info->enddate) == $date) {
                    if ($settlement_info->enddate >= strtotime($date . ' 02:00 AM')) {

                        $flag = 1;
                    } else {
                        $flag = 0;
                    }
                } else {
                    $flag = 1;
                }
            }
            //echo $date .' = '.$flag.'<br/>';

            if ($flag == 1) {
                //echo $date .' = '.$daAmount.'<br/>';
                $return['da'] += $daAmount;
            }
        }
        //exit();
        return $return;
    }

}



if (!function_exists('daCalculatorFn')) {

    function daCalculatorFn($daAmount, $fromdate, $todate, $driver_allowance) {
        $datediff = strtotime($todate) - strtotime($fromdate);

        $rowDA = (floor($datediff / (60 * 60 * 24)) + 1) * $daAmount;
        if ($rowDA > $driver_allowance) {
            return 1;
        } else {
            return 0;
        }
    }

}



if (!function_exists('insterGooglePackageKms')) {

    /**
     * insert route km by fetched by google
     * 
     * @param string $key route string
     * @param int $kms kilometer
     * @param string $city City
     */
    function insterGooglePackageKms($key, $kms = 0, $city = '') {

        $CI = & get_instance();
        $tmp = array(
                'key_' => md5($key),
                'for_' => $key,
                'city' => $city,
                'kms' => $kms
        );
        $CI->db->insert('package_google_kms', $tmp);
    }

}

if (!function_exists('getGooglePackageKms')) {

    /**
     * 
     * get route km by key id
     * 
     * @param string $key route string
     * @return int kilometer
     */
    function getGooglePackageKms($key) {

        $CI = & get_instance();
        $result = $CI->db->select('kms')->where('key_', md5($key))->get('package_google_kms')->row();

        //print_r($result);
        return isset($result->kms) ? $result->kms : 0;
    }

}

if (!function_exists('insterCityCoordinates')) {

    /**
     * insert (lat,lng) Coordinates of city fetched by google
     * 
     * @param string $city City address
     * @param string $coordinates (lat,lng) Coordinates
     */
    function insterCityCoordinates($city, $coordinates) {

        $CI = & get_instance();
        $tmp = array(
                'key_' => md5($city),
                'name' => $city,
                'cordinate' => $coordinates
        );
        $CI->db->insert('package_city_cordinates', $tmp);
    }

}

if (!function_exists('getCityCoordinates')) {

    /**
     * 
     * get (lat,lng) Coordinates by key id
     * 
     * @param string $city City address
     * @return string (lat,lng) Coordinates
     */
    function getCityCoordinates($city) {

        $CI = & get_instance();
        $result = $CI->db->select('cordinate')->where('key_', md5($city))->get('package_city_cordinates')->row();

        //print_r($result);
        return isset($result->cordinate) ? $result->cordinate : '';
    }

}

if (!function_exists('getDatesFromRange')) {

	/**
	 * Generate an array of string dates between 2 dates
	 *
	 * @param string $start  Start date
	 * @param string $end    End date
	 * @param string $format Output format Default: Y-m-d
	 *
	 * @return array
	 * @throws Exception
	 */
    function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        $array = array();
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }

}

if (!function_exists('moneyFormat')) {

    function moneyFormat($amt) {

        $hasNegetive = FALSE;

        if ($amt < 0) {
            $hasNegetive = TRUE;
            $amt *= -1;
        }

        $amount = number_format($amt, 2, ".", "");

        $full_amount = explode('.', $amount);

        $money = $full_amount[0];
        $dec = $full_amount[1];

        $len = strlen($money);
        $m = '';
        $money = strrev($money);
        for ($i = 0; $i < $len; $i++) {
            if (( $i == 3 || ($i > 3 && ($i - 1) % 2 == 0) ) && $i != $len) {
                $m .= ',';
            }
            $m .= $money[$i];
        }

        $return = strrev($m);

        $return = "₹" . $return . "." . $dec;

        if ($hasNegetive) {
            $return = "-" . $return;
        }

        return $return;
    }

}

if (!function_exists('number_to_words')) {

    function number_to_words($number) {
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
        $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : '';
                $str [] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
            } else
                $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) : '';
        return ($Rupees ? $Rupees : '') . $paise;
    }

}

if (!function_exists('checkIfCashCollected')) {

    function checkIfCashCollected($flag = TRUE) {
        $CI = & get_instance();
        if ($CI->session->type == 'settlement_executive') {
            $main = $CI->db->select("b.id", false)
                        ->from('smtt_report_approval AS b')
                        //->join('smtt_user AS u', 'u.id = b.approved_by', 'left')
                        ->where("b.sheet_date='" . date('Y-m-d') . "' and b.for_executive_id= {$CI->session->userid}")
                        ->get()->row();
            
            //echo $CI->db->last_query();

            if (isset($main->id) && $main->id > 0) {
                if($flag){
                    echo '<h1><center>Your are not authorized to access this web page, after admin received balance payment from you today.</center></h1>';
                    exit();
                }else{
                    return TRUE;
                }
            }
        }
        if(!$flag){
            return FALSE;
        }
    }

}


if (!function_exists('encryptUrl')) {

    function encryptUrl($str) {
        //$CI = & get_instance();
        //$str = $CI->encryption->encrypt($str);
        $str = rand(1000,9999).'#@@#'.$str;
        return 'enc_-_-_'. urlencode(base64_encode($str));
    }

}


if (!function_exists('decryptUrl')) {

    function decryptUrl($str) {
        //$CI = & get_instance();
        $lg = explode('enc_-_-_', $str);
        $str = base64_decode(urldecode($lg[1]));
        $lg = explode('#@@#', $str);
        return $lg[1];
    }

}

if (!function_exists('matchKeywordInString')) {
	function matchKeywordInString($str, array $arr)
	{
		foreach ($arr as $a) {
			if (stripos($str, $a) !== false) return true;
		}
		return false;
	}
}

if (!function_exists('bookingStatus')) {

	function bookingStatus($str) {
		$rt = '';
		if($str == 'enquiry'){
			$rt = '<span class="label label-default">';
		}else if($str == 'draft'){
			$rt = '<span class="label label-default">';
		}else if($str == 'advance_pending'){
			$rt = '<span class="label label-warning">';
		}else if($str == 'confirm'){
			$rt = '<span class="label label-info">';
		}else if($str == 'ongoing'){
			$rt = '<span class="label label-primary">';
		}else if($str == 'settle'){
			$rt = '<span class="label label-success">';
		}else if($str == 'unsettle'){
			$rt = '<span class="label label-warning">';
		}else if($str == 'completed'){
			$rt = '<span class="label label-success">';
		}else if($str == 'cancle'){
			$rt = '<span class="label label-danger">';
		}else{
			$rt = '<span class="label label-default">';
		}
		$rt .= ucfirst($str) .'</span>';
		return $rt;
	}

}

if (!function_exists('get_executives')) {
    function get_executives(){
        $CI = & get_instance();
        $executive = $CI->db->select('id,name,phone,status,type')
            ->where(['type !=' => 'system'])->get('user')->result();

        $return = array();

        foreach ($executive as $row){
            $return[$row->id] = $row->name .' | '. $row->type;
        }

        return $return;
    }

}


if (!function_exists('get_single_executive')) {
    function get_single_executive($id){
        $CI = & get_instance();
        $executive = $CI->db->select('id,name,phone,status,type')
            ->where(['id !=' => $id])->get('user')->row();


        return $executive;
    }

}


if (!function_exists('get_employee_ledger')) {
    function get_employee_ledger($type, $flag = ""){
        $grp = 3;
        if($type == 'driver'){
            $grp = 4;
            if($flag == 'leave'){
                $grp = 13;
            }
            else if($flag == 'booking_advance'){
                $grp = 8;
            }
        }else if($type == 'conductor'){
            $grp = 16;
        }
        return $grp;
    }

}


if (!function_exists('get_ledger_type')) {
    function get_ledger_type($type){
        if(isset($_GET['type'])){
            if($_GET['type'] == 'salary'){
                $type = (($type == 'Debit')? 'Paid' : 'Due');
            }
        }
        return $type;
    }

}

if (!function_exists('get_unique_str')) {
    function get_unique_str($input = '', $length = 8){
        $str = dechex($input.'');
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strlen = strlen($str);
        $encode = '';
        if($strlen >= $length){
            $encode = $strlen;
        }else{
            $encode = substr(str_shuffle($permitted_chars), 3, ($length - $strlen)) . $str;
        }
        return $encode;
    }

}


