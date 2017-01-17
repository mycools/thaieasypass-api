<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
error_reporting(15);
class Connect extends CI_Controller {
	public function index()
	{
		//header("content-type:application/json;charset:utf-8");
		//readfile("./easypass.json");exit();
		$wmsAuthSign = $this->input->get('wmsAuthSign');
		if(!$wmsAuthSign){
			$this->json->set("status","error");
			$this->json->set("error_message","Authentication required.");
			$this->json->send();
		}
		$wmsAuthSign_Checksum = substr($wmsAuthSign,0,32);
		$wmsAuthSign = substr($wmsAuthSign,32);
		$wmsAuthSign_Val = base64_decode($wmsAuthSign);
		
		if(md5($wmsAuthSign_Val) != $wmsAuthSign_Checksum){
			$this->json->set("status","error");
			$this->json->set("error_message","Invalid Authentication Key.");
			$this->json->send();
		}
		
		
		parse_str($wmsAuthSign_Val,$params);
		
		$validminutes=$params['validminutes'];
		$server_time=$params['server_time'];
		$expire = strtotime(date("Y-m-d H:i:s",$server_time) . " " . $validminutes . " minute");
		if($expire < time()){
			$this->json->set("status","error");
			$this->json->set("error_message","Expired token." .time());
			$this->json->send();
		}
		
		$Password_Checksum = substr($params['p'],0,32);
		$Password_Val = substr($params['p'],32);
		
		if(md5(base64_decode($Password_Val)) != $Password_Checksum){
			$this->json->set("status","error");
			$this->json->set("error_message","Invalid Password Key.");
			$this->json->send();
		}
		
		$params['p'] = base64_decode($Password_Val);
		
		$this->load->model('source_easypass');	
		$logedin = $this->source_easypass->setLogin($params['u'],$params['p']);
		if($logedin){
			$CustInfo = $this->source_easypass->getCustInfo();
			foreach($CustInfo['card'] as $card){
				$accountId = $card['accountId'];
				$Month = date("m");
				$Year = date("Y");
				$lastMonth = date("m",strtotime("$Year-$Month-01 -1 month"));
				$lastYear = date("Y",strtotime("$Year-$Month-01 -1 month"));
				$last2Month = date("m",strtotime("$Year-$Month-01 -2 month"));
				$last2Year = date("Y",strtotime("$Year-$Month-01 -2 month"));
				$CardInfo = $this->source_easypass->getCardInfo($accountId,$Month,$Year);
				$CardInfo2 = $this->source_easypass->getCardInfo($accountId,$lastMonth,$lastYear);
				$CardInfo3 = $this->source_easypass->getCardInfo($accountId,$last2Month,$last2Year);
				$CardInfo['logs'] = array_merge($CardInfo3['logs'],$CardInfo2['logs'],$CardInfo['logs']);
				$CardInfo['topup_logs'] = array_merge($CardInfo3['topup_logs'],$CardInfo2['topup_logs'],$CardInfo['topup_logs']);
				$CustInfo['CardInfo'][$accountId]=$CardInfo;
			}
			$this->json->set('CustInfo',$CustInfo);
			$this->json->send();
		}
	}
	function forgetpass()
	{
		$wmsAuthSign = $this->input->get('wmsAuthSign');
		if(!$wmsAuthSign){
			$this->json->set("status","error");
			$this->json->set("error_message","Authentication required.");
			$this->json->send();
		}
		$wmsAuthSign_Checksum = substr($wmsAuthSign,0,32);
		$wmsAuthSign = substr($wmsAuthSign,32);
		$wmsAuthSign_Val = base64_decode($wmsAuthSign);
		
		if(md5($wmsAuthSign_Val) != $wmsAuthSign_Checksum){
			$this->json->set("status","error");
			$this->json->set("error_message","Invalid Authentication Key.");
			$this->json->send();
		}
		
		
		parse_str($wmsAuthSign_Val,$params);
		
		$validminutes=$params['validminutes'];
		$server_time=$params['server_time'];
		
		$expire = strtotime(date("Y-m-d H:i:s",$server_time) . " " . $validminutes . " minute");
		if($expire < time()){
			$this->json->set("status","error");
			$this->json->set("error_message","Expired token." .time());
			$this->json->send();
		}
		
		$idcard_Checksum = substr($params['idcard'],0,32);
		$idcard_Val = substr($params['idcard'],32);
		
		if(md5(base64_decode($idcard_Val)) != $idcard_Checksum){
			$this->json->set("status","error");
			$this->json->set("error_message","Invalid IDCard Key.");
			$this->json->send();
		}
		
		$params['idcard'] = base64_decode($idcard_Val);
		
		$this->load->model('source_easypass');
		$this->source_easypass->forgetpass($params);
		
	}
	function wmsAuthSign()
	{
		$params = array(
			"server_time"=>time(),
			"validminutes"=>5,
			"u"=>"mycools",
			"p"=>"ja*05032530",
		);
		$params['p'] = md5($params['p']).base64_encode($params['p']);
		$str = http_build_query($params);
		$params['hash_value'] = md5($str);
		$str = http_build_query($params);
		$wmsAuthSign = md5($str).base64_encode($str);
		$link = "http://digitaltv.mycools.in.th/easypass?wmsAuthSign=".$wmsAuthSign;
		echo "<a href='".$link."' target='_blank'>".$link."</a>";
	}
	function wmsAuthSign2()
	{
		$params = array(
			"server_time"=>time(),
			"validminutes"=>5,
			"idcard"=>"1969900073487",
		);
		$params['p'] = md5($params['p']).base64_encode($params['p']);
		$str = http_build_query($params);
		$params['hash_value'] = md5($str);
		$str = http_build_query($params);
		$wmsAuthSign = md5($str).base64_encode($str);
		$link = "http://digitaltv.mycools.in.th/easypass?wmsAuthSign=".$wmsAuthSign;
		echo "<a href='".$link."' target='_blank'>".$link."</a>";
	}
}