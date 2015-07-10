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
* Author : Jeong Yohan (code@linkhub.co.kr)
* Written : 2015-07-10
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class ClosedownService extends PopbillBase {
	
	function ClosedownService($LinkID,$SecretKey) {
    	parent::PopbillBase($LinkID,$SecretKey);
    	$this->AddScope('170');
    }
    
    
    //휴폐업조회 - 단건
    function CheckCorpNum($MemberCorpNum,$CheckCorpNum) {
    	if(is_null($MemberCorpNum) || empty($MemberCorpNum)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "팝빌회원 사업자번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/CloseDown?CN='.$CheckCorpNum, $MemberCorpNum);
		if(is_a($result,'PopbillException')) return $result;

		$CorpState= new CorpState();
		$CorpState->fromJsonInfo($result);

		return $CorpState;
    }

	//휴폐업조회 - 대량
    function CheckCorpNums($MemberCorpNum,$CorpNumList = array()) {
    	
		if(is_null($MemberCorpNum) || empty($MemberCorpNum)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "팝빌회원 사업자번호가 입력되지 않았습니다."}');
    	}
		
		if(is_null($CorpNumList) || empty($CorpNumList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "조회할 사업자번호 배열이 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($CorpNumList);
    	
    	$result = $this->executeCURL('/CloseDown', $MemberCorpNum, null, true,null,$postdata);
		
		$CorpStateList = array();
		
		if(is_a($result, 'PopbillException')){ return $result; }

		for($i=0; $i<Count($result); $i++){
			$CorpState = new CorpState();
			$CorpState->fromJsonInfo($result[$i]);
			$CorpStateList[$i] = $CorpState;
		}
		return $CorpStateList;
    }
    
    //발행단가 확인
    function GetUnitCost($CorpNum) {
    	$result = $this->executeCURL('/CloseDown/UnitCost', $CorpNum);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->unitCost;
    }
}

class CorpState
{
	var $corpNum;
	var $state;
	var $ctype;
	var $stateDate;
	var $checkDate;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->corpNum) ? $this->corpNum = $jsonInfo->corpNum : null;
		isset($jsonInfo->state) ? $this->state = $jsonInfo->state : null;
		isset($jsonInfo->type) ? $this->type = $jsonInfo->type : null;
		isset($jsonInfo->stateDate) ? $this->stateDate = $jsonInfo->stateDate : null;
		isset($jsonInfo->checkDate) ? $this->checkDate = $jsonInfo->checkDate : null;
	}
}


?>
