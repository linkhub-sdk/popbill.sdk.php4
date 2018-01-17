<?php
/**
* =====================================================================================
* Class for base module for Popbill API SDK. It include base functionality for
* RESTful web service request and parse json result. It uses Linkhub module
* to accomplish authentication APIs.
*
* This module uses curl and openssl for HTTPS Request. So related modules must
* be installed and enabled.
*
* http://www.linkhub.co.kr
* Author : Kim Seongjun (pallet027@gmail.com)
* Written : 2014-06-23
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anythings.
* ======================================================================================
*/

require_once 'Linkhub/linkhub.auth.php';
require_once 'Linkhub/JSON.php';

class PopbillBase
{
	var $Token_Table = array();

	//생성자
  function PopbillBase($LinkID,$SecretKey) {
  	$this->Linkhub = new Linkhub($LinkID,$SecretKey);
  	$this->scopes[] = 'member';
  	$this->IsTest = false;
  	$this->VERS = '1.0';
  	$this->ServiceID_REAL = 'POPBILL';
  	$this->ServiceID_TEST = 'POPBILL_TEST';
  	$this->ServiceURL_REAL = 'https://popbill.linkhub.co.kr';
  	$this->ServiceURL_TEST = 'https://popbill_test.linkhub.co.kr';
  }

  //테스트모드 설정
  function IsTest($T) {$this->IsTest = $T;}

  //스코프 추가
  function AddScope($scope) {$this->scopes[] = $scope;}

  //팝빌 연결 URL함수
  function GetPopbillURL($CorpNum ,$UserID, $TOGO) {
  	$response = $this->executeCURL('/?TG='.$TOGO,$CorpNum,$UserID);
  	if(is_a($response ,'PopbillException')) return $response;
  	return $response->url;
  }

  // 파트너 관리자 팝업 URL
  function GetPartnerURL($CorpNum, $TOGO) {
    $response = $this->Linkhub->getPartnerURL($this->getsession_Token($CorpNum), $this->IsTest ? $this->ServiceID_TEST : $this->ServiceID_REAL, $TOGO);
    if(is_a($response ,'PopbillException')) return $response;
    return $response->url;
  }


 	//가입여부 확인
 	function CheckIsMember($CorpNum , $LinkID) {
 		return $this->executeCURL('/Join?CorpNum='.$CorpNum.'&LID='.$LinkID);
 	}

  // 아이디 중복여부 확인 2018-01-16
  function CheckID($ID) {
 		return $this->executeCURL('/IDCheck?ID='.$ID);
 	}

  //회원가입
  function JoinMember($JoinForm) {
  	$postdata = $this->Linkhub->json_encode($JoinForm);
 		return $this->executeCURL('/Join',null,null,true,null,$postdata);

  }

  //회원 잔여포인트 확인
  function GetBalance($CorpNum) {
  	$_Token = $this->getsession_Token($CorpNum);
  	if(is_a($_Token,'PopbillException')) return $_Token;
  	return $this->Linkhub->getBalance($_Token,$this->IsTest ? $this->ServiceID_TEST : $this->ServiceID_REAL);
  }

  //파트너 잔여포인트 확인
  function GetPartnerBalance($CorpNum) {
  	$_Token = $this->getsession_Token($CorpNum);
  	if(is_a($_Token,'PopbillException')) return $_Token;

  	return $this->Linkhub->getPartnerBalance($_Token ,$this->IsTest ? $this->ServiceID_TEST : $this->ServiceID_REAL);
  }

  /************ 이하 내부 함수 ***************************/
  function getsession_Token($CorpNum) {

	$_targetToken = null;

	if(array_key_exists($CorpNum, $this->Token_Table)) {
		$_targetToken = $this->Token_Table[$CorpNum];
	}

  	$Refresh = false;

  	if(is_null($_targetToken)) {
  		$Refresh = true;
  	}
  	else {
  		$Expiration = gmdate($_targetToken->expiration);
  		$now = gmdate("Y-m-d H:i:s",time());
  		$Refresh = $Expiration < $now;
  	}

  	if($Refresh) {

  		$_targetToken = $this->Linkhub->getToken($this->IsTest ? $this->ServiceID_TEST : $this->ServiceID_REAL,$CorpNum, $this->scopes);
  		//TODO return Exception으로 처리 변경...
  		if(is_a($_targetToken,'LinkhubException')) {
  			return new PopbillException($_targetToken);
  		}
  		$this->Token_Table[$CorpNum] = $_targetToken;
  	}

  	return $_targetToken->session_token;
  }

  function executeCURL($uri,$CorpNum = null,$userID = null,$isPost = false, $action = null, $postdata = null,$isMultiPart=false) {

		$http = curl_init(($this->IsTest ? $this->ServiceURL_TEST : $this->ServiceURL_REAL).$uri);
		$header = array();

		if(is_null($CorpNum) == false) {
			$_Token = $this->getsession_Token($CorpNum);
    		if(is_a($_Token,'PopbillException')) return $_Token;

			$header[] = 'Authorization: Bearer '.$_Token;
		}
		if(is_null($userID) == false) {
			$header[] = 'x-pb-userid: '.$userID;
		}
		if(is_null($action) == false) {
			$header[] = 'X-HTTP-Method-Override: '.$action;
		}
		if($isMultiPart == false) {
			$header[] = 'Content-Type: Application/json';
		}

		if($isPost) {
			curl_setopt($http, CURLOPT_POST,1);
			curl_setopt($http, CURLOPT_POSTFIELDS, $postdata);
		}
		curl_setopt($http, CURLOPT_HTTPHEADER,$header);
		curl_setopt($http, CURLOPT_RETURNTRANSFER, TRUE);

		$responseJson = curl_exec($http);
		$http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);

		curl_close($http);

		if($http_status != 200) {
			return new PopbillException($responseJson);
		}

		return $this->Linkhub->json_decode($responseJson);
	}
}

// 회원가입정보 구조체
class JoinForm
{
	var $LinkID;
	var $CorpNum;
	var $CEOName;
	var $CorpName;
	var $Addr;
	var $ZipCode;
	var $BizType;
	var $BizClass;
	var $ContactName;
	var $ContactEmail;
	var $ContactTEL;
	var $ID;
	var $PWD;
}

class ChargeInfo
{
  var $unitCost;
  var $chargeMethod;
  var $rateSystem;

  function fromJsonInfo($jsonInfo){
    isset($jsonInfo->unitCost) ? $this->unitCost = $jsonInfo->unitCost : null;
    isset($jsonInfo->chargeMethod) ? $this->chargeMethod = $jsonInfo->chargeMethod : null;
    isset($jsonInfo->rateSystem) ? $this->rateSystem = $jsonInfo->rateSystem : null;
  }
}

//예외클래스
class PopbillException
{
	var $code;
	var $message;

	function PopbillException($responseJson) {
		if(is_a($responseJson,'LinkhubException')) {
			$this->code = $responseJson->code;
			$this->message = $responseJson->message;
			return $this;
		}
		$json = new Services_JSON();
		$result = $json->decode($responseJson);
		$this->code = $result->code;
		$this->message = $result->message;
		$this->isException = true;
		return $this;
	}
	function __toString() {
		return "[code : {$this->code}] : {$this->message}\n";
	}
}
?>
