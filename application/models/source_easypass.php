<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Project Name : Mycools.in.th
* Build Date : 11/3/2559 BE
* Author Name : Jarak Kritkiattisak
* File Name : source_3bb.php
* File Location : /Volumes/Macintosh HD/Users/mycools/Library/Caches/Coda 2/663A5F31-9970-4A90-8F30-46ED4257F0DC
* File Type : Controller	
* Remote URL : http://digitaltv.mycools.in.th/application/models/Source_easypass.php
*/
class Source_easypass extends CI_Model {
	/**
	* Source_easypass
	*
	* Index Page for Source_3bb Controller
	*
	* @return	nulled
	*/
	var $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36';
	var $cookie_file;
	function __construct()
	{
		parent::__construct();
		!$this->load->helper('simple_html_dom');
		$this->cookie_file = APPPATH.'cache/cookie_easypass.txt';
	}
	public function setLogin($user,$pass)
	{
		$url = "http://thaieasypass.com/EPCustInfo/Login.aspx";
		$login_page_html = $this->_get($url);
		$login_page = str_get_html($login_page_html);
		$viewstate = $login_page->find('input[id="__VIEWSTATE"]',0)->value;
		$params = array(
			"__VIEWSTATE"=>$viewstate,
			"txtUser"=>$user,
			"txtPWD"=>$pass,
		);
		$login_res = $this->_post($url,$params);
		preg_match_all("/alert\('(.*)\'\)/", $login_res, $output_array);
		$error_res = @$output_array[1];
		if(count($error_res) != 0){
			$this->json->set('status','error');	
			$this->json->set('error_message',$error_res[0]);	
			$this->json->send();
		}
		
		preg_match_all("/location.replace\('(.*)\'\)/", $login_res, $output_array2);
		$js_res = @$output_array2[1];
		if(count($js_res) == 0){
			$this->json->set('status','error');	
			$this->json->set('error_message','ระบบทำงานผิดพลาด ไม่สามารถเข้าสู่ระบบได้ในขณะนี้');	
			$this->json->send();
		}
		if($js_res[0]="CustInfoView.aspx"){
			return true;
		}
	}
	public function forgetpass($params)
	{
		$url = "http://thaieasypass.com/EPCustInfo/ForgotPWD.aspx";
		$forgetpass_page_html = $this->_get($url);
		$forgetpass_page = str_get_html($forgetpass_page_html);
		$viewstate = $forgetpass_page->find('input[id="__VIEWSTATE"]',0)->value;
		$post_params = array(
			"__VIEWSTATE"=>$viewstate,
			"txtCardID"=>$params['idcard']
		);
		$forgetpass_res = $this->_post($url,$post_params);
		preg_match_all("/alert\('(.*)\'\)/", $forgetpass_res, $output_array);
		
		$error_res = @$output_array[1];
		if(count($error_res) != 0){
			$this->json->set('status','error');	
			$this->json->set('error_message',$error_res[0]);	
			$this->json->send();
		}
	}
	public function getCustInfo()
	{
		
		$url = "http://thaieasypass.com/EPCustInfo/CustInfoView.aspx";
		$CustInfoView = $this->_get($url);
		
		$CustInfo = str_get_html($CustInfoView);
		$Cus = $CustInfo->find("table",0);
		$rowcus = str_get_html($Cus)->find("span.th_lb");
		$rowinfo = array();
		$allow_label = array(
			"ชื่อ - นามสุกล"=>"fullname",
			"ที่อยู่ติดต่อ"=>"address",
			"ที่อยู่ออกใบกำกับภาษี"=>"address_vat"
		);
		foreach($rowcus as $row){
			$th_lb = trim($row->innertext);
			$th_desc = $row->parent()->parent()->find("td.th_desc",0)->innertext;
			if(array_key_exists($th_lb,$allow_label)){
				$rowinfo[$allow_label[$th_lb]]=trim(str_replace('&nbsp;','',$th_desc));
			}
			
		}
		
		$Card = $CustInfo->find("table",2);
		$rowcard = str_get_html($Card)->find("tr");
		$rowcardinfo = array();
		$label = array(
			'accountId',
			'accountStatus',
			'easypassId',
			'smartcardId',
			'carNo'
		);
		
		foreach($rowcard as $row){
			/*$th_lb = $row->find("span.th_lb");*/
			$th_desc = $row->find("td.th_desc");
			/*if($th_lb){
				
				foreach($th_lb as $lb){
					$label[]=strip_tags($lb->innertext);
				}
				
			}*/
			if($th_desc){
				$card = array();
				foreach($th_desc as $desc){
					/*$card[]=array(
					'label'=>$label[count($card)],
					'desc'=>trim(str_replace('<br/>'," ",str_replace('&nbsp;','',($desc->innertext))))
					);*/
					$card[$label[count($card)]]=trim(str_replace('<br/>'," ",str_replace('&nbsp;','',($desc->innertext))));
				}
				$rowcardinfo[]=$card;
			}
			
		}
		return array(
			'profile'=>$rowinfo,
			'card'=>$rowcardinfo,
			);
	}
	function getCardInfo($cardId,$month,$year)
	{
		$url = "http://thaieasypass.com/EPCustInfo/Default.aspx";
		$form_page_html = $this->_get($url);
		$DefaultInfo = str_get_html($form_page_html);
		$VIEWSTATE = $DefaultInfo->find('input[id="__VIEWSTATE"]',0)->value;
		$EVENTVALIDATION = $DefaultInfo->find('input[id="__EVENTVALIDATION"]',0)->value;
		$Button1 = $DefaultInfo->find('input[id="Button1"]',0)->value;
		$params = array(
			'__VIEWSTATE'=>$VIEWSTATE,
			'__EVENTVALIDATION'=>$EVENTVALIDATION,
			'selMonth'=>$month,
			'selYear'=>$year,
			'Button1'=>$Button1
		);
		$_CardInfoResult = $this->_post($url,$params);
		
		$CardInfoResult = str_get_html($_CardInfoResult);
		$CardInfoResultIframe = $CardInfoResult->find('iframe',0)->src;
		parse_str($CardInfoResultIframe,$out);
		$_ReportViewerWebControl = $this->_get("http://thaieasypass.com".$out['amp;ReportUrl'],array(),$url);
		 
		
		$ReportViewerWebControl = str_get_html($_ReportViewerWebControl);
		$Table =$ReportViewerWebControl->find('TABLE.a4',0);
		$r11 = $Table->find('TR DIV.r11');
		$a210 = $Table->find('TR TD.a210');
		$r11_tmp = array();
		$a210_tmp = array();
		$topup_logs = array();
		$allow_label = array(
			"หมายเลขแทค"=>"smartcardId",
			"เงินคงเหลือ"=>"Balance",
			"เลขบัญชีแทค"=>"easypassId",
			"แอคชั่น / สถานะบัตร"=>"accountStatus",
			"บัตรสมาร์ทการ์ด"=>"smartcardId",
			"วันที่รีจิสเตอร์"=>"registerDate",
			"ทะเบียนรถ"=>"carNo",
			"เงินประกัน"=>"cashDeposit",
			"รายละเอียดรถ"=>"carInfo",
			"หมายเลขเจ้าของบัตร"=>"accountId",
			"ชื่อเจ้าของบัตร"=>"accountName",
			/*"หมายเลขบัตรประจำตัว",*/
			"เบอร์โทรติดต่อ"=>"PhoneNo"
		);
		$setLabel = "";
		
		foreach($r11 as $row){
			$row->innertext = trim($row->innertext);
			if($setLabel!=""){
				$r11_tmp[$setLabel] = $row->innertext;
				$setLabel = "";
			}
			if($setLabel=="" && array_key_exists($row->innertext,$allow_label)){
				$setLabel = $allow_label[trim($row->innertext)];
			}
			
		}
		$topup = 0;
		foreach($a210 as $row){
			 $seq = $row->find('DIV.r11',0)->innertext;
			 $easypass = $row->parent()->find('TD.a211 DIV.r11',0)->innertext;
			 $type = $row->parent()->find('TD.a212 DIV.r11',0)->innertext;
			 $checkpoint = $row->parent()->find('TD.a213 DIV.r11',0)->innertext;
			 $gate = $row->parent()->find('TD.a214 DIV.r11',0)->innertext;
			 $date = $row->parent()->find('TD.a215 DIV.r11',0)->innertext;
			 $total = trim($row->parent()->find('TD.a216 DIV.r11',0)->innertext);
			 list($smartcardId,$easypassId) = explode("<br/>",$easypass);
			 if(trim($type)=="เติมเงิน"){
				 //$topup+=($total);
				 $logo = "thumb.png";
				 if(strpos($checkpoint,"TMB") !== false){
					 $logo = "tmb.png";
				 }else if(strpos($checkpoint,"SCB") !== false){
					 $logo = "scb.png";
				 }else if(strpos($checkpoint,"KTB") !== false){
					 $logo = "ktb.png";
				 }else if(strpos($checkpoint,"UOB") !== false){
					 $logo = "uob.png";
				 }else if(strpos($checkpoint,"Bualuang") !== false){
					 $logo = "scb.png";
				 }else if(strpos($checkpoint,"Krungsri") !== false){
					 $logo = "krungsri.png";
				 }else if(strpos($checkpoint,"Kbank") !== false || strpos($gate,"K-Mobile") !== false){
					 $logo = "kbank.png";
				 }else if(strpos($checkpoint,"Tesco") !== false){
					 $logo = "tesco.png";
				 }else if(strpos($checkpoint,"Counter") !== false){
					 $logo = "cs.png";
				 }else if(strpos($checkpoint,"True") !== false){
					 $logo = "truemoney.png";
				 }else if(strpos($checkpoint,"Easy") !== false){
					 $logo = "easytopup.png";
				 }
				 $topup_logs[]=array(
				 	"smartcardId"=>$smartcardId,
					"easypassId"=>$easypassId,
					"Branch"=>$checkpoint,
					"Channel"=>$gate,
					"logo"=> $logo,
					"Date"=>$date,
					"Amount"=>$total
				 );
			 }else{
				 $a210_tmp[] = array(
					"No"=>$seq,
					"smartcardId"=>$smartcardId,
					"easypassId"=>$easypassId,
					"Type"=>$type,
					"Checkpoint"=>$checkpoint,
					"Gate"=>$gate,
					"Date"=>$date,
					"Amount"=>$total
				 );
			 }
		}
		
		$r11_tmp['logs'] = $a210_tmp;
		//$r11_tmp['topup'] = number_format($topup,2);
		$r11_tmp['remain'] = number_format($r11_tmp["Balance"],2);
		$r11_tmp['topup_logs'] = $topup_logs;
		return $r11_tmp;
	}
	private function _get($url,$params = array(),$referrer='')
	{
		$httpcurl = curl_init();
		if($referrer != ''){
			curl_setopt($httpcurl, CURLOPT_REFERER, $referrer);
		}
		curl_setopt($httpcurl, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($httpcurl, CURLOPT_URL, $url);
		curl_setopt($httpcurl, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($httpcurl, CURLOPT_COOKIEFILE, $this->cookie_file);
		
		curl_setopt($httpcurl, CURLOPT_POST, false);
		curl_setopt($httpcurl, CURLOPT_HEADER, false);
		curl_setopt($httpcurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($httpcurl, CURLOPT_FOLLOWLOCATION, false);
		$res = curl_exec($httpcurl);
		if(curl_errno($httpcurl)){ 
			$this->json->set('status','error');	
			$this->json->set('error_message',curl_error($httpcurl));	
			$this->json->send();
		}
		curl_close($httpcurl);
		return $res;	
	}
	private function _post($url,$params = array())
	{
		$postvars = http_build_query($params);
		$httpcurl = curl_init();
		curl_setopt($httpcurl, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($httpcurl, CURLOPT_URL, $url);
		curl_setopt($httpcurl, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($httpcurl, CURLOPT_COOKIEFILE, $this->cookie_file);
		
		curl_setopt($httpcurl, CURLOPT_POST, true);
		curl_setopt($httpcurl, CURLOPT_POSTFIELDS, $postvars);
		curl_setopt($httpcurl, CURLOPT_HEADER, false);
		curl_setopt($httpcurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($httpcurl, CURLOPT_FOLLOWLOCATION, false);
		$res = curl_exec($httpcurl);
		if(curl_errno($httpcurl)){ 
			$this->json->set('status','error');	
			$this->json->set('error_message',curl_error($httpcurl));	
			$this->json->send();
		}
		curl_close($httpcurl);
		return $res;	
	}
}

/* End of file source_3bb.php */
/* Location: ./application/controllers/source_3bb.php */