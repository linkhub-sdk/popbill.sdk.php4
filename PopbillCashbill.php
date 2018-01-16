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
* Written : 2014-04-15
* Contributor : Jeong Yohan (code@linkhub.co.kr)
* Updated : 2018-01-16
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class CashbillService extends PopbillBase {

	function CashbillService($LinkID,$SecretKey) {
    	parent::PopbillBase($LinkID,$SecretKey);
    	$this->AddScope('140');
    }

    //팝빌 현금영수증 연결 url
    function GetURL($CorpNum,$UserID,$TOGO) {
    	$result = $this->executeCURL('/Cashbill/?TG='.$TOGO,$CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->url;
    }

    //관리번호 사용여부 확인
    function CheckMgtKeyInUse($CorpNum,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$response = $this->executeCURL('/Cashbill/'.$MgtKey,$CorpNum);

    	if(is_a($response,'PopbillException')) {
    		if($response->code == -14000003) { return false;}
    		return $response;
    	}
    	else {
    		return is_null($response->itemKey) == false;
    	}
    }

    // 임시저장
    function Register($CorpNum, $Cashbill, $UserID = null) {
    	$postdata = $this->Linkhub->json_encode($Cashbill);
    	return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,null,$postdata);
    }

    // 즉시발행 2018-01-16
    function RegistIssue($CorpNum, $Cashbill, $Memo, $UserID = null){
      if (!is_null($Memo) || !empty($Memo)) {
        $Cashbill->memo = $Memo;
      }
      $postdata = $this->Linkhub->json_encode($Cashbill);
      return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,"ISSUE",$postdata);
    }

    // 취소현금영수증 즉시발행 2018-01-16
    function RevokeRegistIssue($CorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate, $smssendYN = false, $memo = null,
      $UserID = null, $isPartCancel = false, $cancelType = null, $supplyCost = null, $tax = null, $serviceFee = null, $totalAmount = null){

      $request = array(
        'mgtKey' => $mgtKey,
        'orgConfirmNum' => $orgConfirmNum,
        'orgTradeDate' => $orgTradeDate,
        'smssendYN' => $smssendYN,
        'memo' => $memo,
        'isPartCancel' => $isPartCancel,
        'cancelType' => $cancelType,
        'supplyCost' => $supplyCost,
        'tax' => $tax,
        'serviceFee' => $serviceFee,
        'totalAmount' => $totalAmount,
      );

      $postdata = $this->Linkhub->json_encode($request);
      return $this->executeCURL('/Cashbill',$CorpNum,$UserID,true,'REVOKEISSUE',$postdata);
    }

    //삭제
    function Delete($CorpNum,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'DELETE','');
    }

    //수정
    function Update($CorpNum,$MgtKey,$Cashbill, $UserID = null, $writeSpecification = false) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	if($writeSpecification) {
    		$Cashbill->writeSpecification = $writeSpecification;
    	}

    	$postdata = $this->Linkhub->json_encode($Cashbill);
    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true, 'PATCH', $postdata);
    }

    //발행
    function Issue($CorpNum,$MgtKey,$Memo = '', $UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new IssueRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);

    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'ISSUE',$postdata);
    }

    //발행취소
    function CancelIssue($CorpNum,$MgtKey,$Memo = '', $UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);

    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'CANCELISSUE',$postdata);
    }


    //알림메일 재전송
    function SendEmail($CorpNum,$MgtKey,$Receiver, $UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$Request = array('receiver' => $Receiver);
    	$postdata = $this->Linkhub->json_encode($Request);

    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'EMAIL',$postdata);
    }

    //알림문자 재전송
    function SendSMS($CorpNum,$MgtKey,$Sender,$Receiver,$Contents,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$Request = array('receiver' => $Receiver,'sender'=>$Sender,'contents' => $Contents);
    	$postdata = $this->Linkhub->json_encode($Request);

    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'SMS',$postdata);
    }

    //알림팩스 재전송
    function SendFAX($CorpNum,$MgtKey,$Sender,$Receiver,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$Request = array('receiver' => $Receiver,'sender'=>$Sender);
    	$postdata = $this->Linkhub->json_encode($Request);

    	return $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum, $UserID, true,'FAX',$postdata);
    }

    //현금영수증 요약정보 및 상태정보 확인
    function GetInfo($CorpNum,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
		$result = $this->executeCURL('/Cashbill/'.$MgtKey, $CorpNum);

		if(is_a($result, 'PopbillException')){ return $result; }

		$CashbillInfo = new CashbillInfo();
		$CashbillInfo->fromJsonInfo($result);
		return $CashbillInfo;
    }

    //현금영수증 상세정보 확인
    function GetDetailInfo($CorpNum,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$result = $this->executeCURL('/Cashbill/'.$MgtKey.'?Detail', $CorpNum);

		if(is_a($result,'PopbillException')) return $result;

		$CashbillDetail = new Cashbill();

		$CashbillDetail->fromJsonInfo($result);
		return $CashbillDetail;
    }

    //현금영수증 요약정보 다량확인 최대 1000건
    function GetInfos($CorpNum,$MgtKeyList = array()) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$postdata = $this->Linkhub->json_encode($MgtKeyList);

    	$result = $this->executeCURL('/Cashbill/States', $CorpNum, null, true,null,$postdata);

		$CashbillInfoList = array();

		if(is_a($result, 'PopbillException')){ return $result; }

		for($i=0; $i<Count($result); $i++){
			$CashbillInfoObj = new CashbillInfo();
			$CashbillInfoObj->fromJsonInfo($result[$i]);
			$CashbillInfoList[$i] = $CashbillInfoObj;
		}
		return $CashbillInfoList;
    }

    //현금영수증 문서이력 확인
    function GetLogs($CorpNum,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Cashbill/'.$MgtKey.'/Logs', $CorpNum);
		if(is_a($result,'PopbillException')) return $result;

		$CashbillLogList = array();

		for($i=0; $i<Count($result); $i++){
			$CashbillLog = new CashbillLog();
			$CashbillLog->fromJsonInfo($result[$i]);
			$CashbillLogList[$i] = $CashbillLog;
		}
		return $CashbillLogList;

    }

    //팝업URL
    function GetPopUpURL($CorpNum,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=POPUP', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->url;
    }

    //인쇄URL
    function GetPrintURL($CorpNum,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=PRINT', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->url;
    }

    //공급받는자 인쇄URL
    function GetEPrintURL($CorpNum,$MgtKey,$UserID = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
        }

        $result = $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=EPRINT', $CorpNum,$UserID);
        if(is_a($result,'PopbillException')) return $result;

        return $result->url;
    }

    //공급받는자 메일URL
    function GetMailURL($CorpNum,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Cashbill/'.$MgtKey.'?TG=MAIL', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->url;
    }

    //현금영수증 다량인쇄 URL
    function GetMassPrintURL($CorpNum,$MgtKeyList = array(),$UserID = null) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$postdata = $this->Linkhub->json_encode($MgtKeyList);

    	$result = $this->executeCURL('/Cashbill/Prints', $CorpNum, $UserID, true,null,$postdata);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->url;
    }

    //발행단가 확인
    function GetUnitCost($CorpNum) {
    	$result = $this->executeCURL('/Cashbill?cfg=UNITCOST', $CorpNum);
    	if(is_a($result,'PopbillException')) return $result;

    	return $result->unitCost;
    }


    function Search($CorpNum, $DType, $SDate, $EDate, $State = array(), $TradeType = array(), $TradeUsage = array(), $TaxationType = array(), $Page, $PerPage, $Order, $QString){
      if(is_null($DType) || empty($DType)) {
        return new PopbillException('{"code" : -99999999 , "message" : "날짜유형이 입력되지 않았습니다."}');
      }
      if(is_null($SDate) || empty($SDate)) {
        return new PopbillException('{"code" : -99999999 , "message" : "시작일자가 입력되지 않았습니다."}');
      }
      if(is_null($EDate) || empty($EDate)) {
        return new PopbillException('{"code" : -99999999 , "message" : "종료일자가 입력되지 않았습니다."}');
      }
      $uri = '/Cashbill/Search';
      $uri .= '?DType='.$DType;
      $uri .= '&SDate='.$SDate;
      $uri .= '&EDate='.$EDate;
      if(!is_null($State) || !empty($State)){
        $uri .= '&State=' . implode(',',$State);
      }
      if(!is_null($TradeType) || !empty($TradeType)){
        $uri .= '&TradeType=' . implode(',',$TradeType);
      }
      if(!is_null($TradeUsage) || !empty($TradeUsage)){
        $uri .= '&TradeUsage=' . implode(',',$TradeUsage);
      }
      if(!is_null($TaxationType) || !empty($TaxationType)){
        $uri .= '&TaxationType=' . implode(',',$TaxationType);
      }
      $uri .= '&Page='.$Page;
      $uri .= '&PerPage='.$PerPage;
      $uri .= '&Order='.$Order;
      if(!is_null($QString) || !empty($QString)){
        $uri .= '&QString=' . $QString;
      }
      $response = $this->executeCURL($uri, $CorpNum, "");
      $SearchList = new CBSearchResult();
      $SearchList->fromJsonInfo($response);
      return $SearchList;
    }
}

class Cashbill
{
	var $mgtKey;
  var $tradeDate;
  var $tradeUsage;
  var $tradeType;

  var $taxationType;
  var $supplyCost;
  var $tax;
  var $serviceFee;
  var $totalAmount;

  var $franchiseCorpNum;
  var $franchiseCorpName;
  var $franchiseCEOName;
  var $franchiseAddr;
  var $franchiseTEL;

  var $identityNum;
  var $customerName;
  var $itemName;
  var $orderNumber;

  var $email;
  var $hp;
  var $fax;
  var $smssendYN;
  var $faxsendYN;

  var $orgConfirmNum;
  var $orgTradeDate;
  var $cancelType;
  var $memo;


	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
		isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
		isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
		isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
		isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
		isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
		isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
		isset($jsonInfo->serviceFee) ? $this->serviceFee = $jsonInfo->serviceFee : null;
		isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
		isset($jsonInfo->franchiseCorpNum) ? $this->franchiseCorpNum = $jsonInfo->franchiseCorpNum : null;
		isset($jsonInfo->franchiseCorpName) ? $this->franchiseCorpName = $jsonInfo->franchiseCorpName : null;
		isset($jsonInfo->franchiseCEOName) ? $this->franchiseCEOName = $jsonInfo->franchiseCEOName : null;
		isset($jsonInfo->franchiseAddr) ? $this->franchiseAddr = $jsonInfo->franchiseAddr : null;
		isset($jsonInfo->franchiseTEL) ? $this->franchiseTEL = $jsonInfo->franchiseTEL : null;
		isset($jsonInfo->identityNum) ? $this->identityNum = $jsonInfo->identityNum : null;
		isset($jsonInfo->customerName) ? $this->customerName = $jsonInfo->customerName : null;
		isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
		isset($jsonInfo->orderNumber) ? $this->orderNumber = $jsonInfo->orderNumber : null;
		isset($jsonInfo->email) ? $this->email = $jsonInfo->email : null;
		isset($jsonInfo->hp) ? $this->hp = $jsonInfo->hp : null;
		isset($jsonInfo->fax) ? $this->fax = $jsonInfo->fax : null;
		isset($jsonInfo->smssendYN) ? $this->smssendYN = $jsonInfo->smssendYN : null;
		isset($jsonInfo->faxsendYN) ? $this->faxsendYN = $jsonInfo->faxsendYN : null;
		isset($jsonInfo->orgConfirmNum) ? $this->orgConfirmNum = $jsonInfo->orgConfirmNum : null;
    isset($jsonInfo->orgTradeDate) ? $this->orgTradeDate = $jsonInfo->orgTradeDate : null;
    isset($jsonInfo->memo) ? $this->memo = $jsonInfo->memo : null;
	}
}

class CashbillInfo
{
	var $itemKey;
	var $mgtKey;
	var $tradeDate;
	var $issueDT;
	var $customerName;
	var $itemName;
	var $identityNum;
	var $taxationType;
	var $totalAmount;
	var $tradeUsage;
	var $tradeType;
	var $stateCode;
	var $stateDT;
	var $printYN;
	var $confirmNum;
	var $orgTradeDate;
	var $orgConfirmNum;
	var $ntssendDT;
	var $ntsresult;
	var $ntsresultDT;
	var $ntsresultCode;
	var $ntsresultMessage;
	var $regDT;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->itemKey) ? $this->itemKey = $jsonInfo->itemKey : null;
		isset($jsonInfo->mgtKey) ? $this->mgtKey = $jsonInfo->mgtKey : null;
		isset($jsonInfo->tradeDate) ? $this->tradeDate = $jsonInfo->tradeDate : null;
		isset($jsonInfo->customerName) ? $this->customerName = $jsonInfo->customerName : null;
		isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
		isset($jsonInfo->identityNum) ? $this->identityNum = $jsonInfo->identityNum : null;
		isset($jsonInfo->taxationType) ? $this->taxationType = $jsonInfo->taxationType : null;
		isset($jsonInfo->totalAmount) ? $this->totalAmount = $jsonInfo->totalAmount : null;
		isset($jsonInfo->tradeUsage) ? $this->tradeUsage = $jsonInfo->tradeUsage : null;
		isset($jsonInfo->tradeType) ? $this->tradeType = $jsonInfo->tradeType : null;
		isset($jsonInfo->stateCode) ? $this->stateCode = $jsonInfo->stateCode : null;
		isset($jsonInfo->stateDT) ? $this->stateDT = $jsonInfo->stateDT : null;
		isset($jsonInfo->printYN) ? $this->printYN = $jsonInfo->printYN : null;
		isset($jsonInfo->confirmNum) ? $this->confirmNum = $jsonInfo->confirmNum : null;
		isset($jsonInfo->orgTradeDate) ? $this->orgTradeDate = $jsonInfo->orgTradeDate : null;
		isset($jsonInfo->ntssendDT) ? $this->ntssendDT = $jsonInfo->ntssendDT : null;
		isset($jsonInfo->ntsresult) ? $this->ntsresult = $jsonInfo->ntsresult : null;
		isset($jsonInfo->ntsresultDT) ? $this->ntsresultDT = $jsonInfo->ntsresultDT : null;
		isset($jsonInfo->ntsresultCode) ? $this->ntsresultCode = $jsonInfo->ntsresultCode : null;
		isset($jsonInfo->ntsresultMessage) ? $this->ntsresultMessage = $jsonInfo->ntsresultMessage : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
	}
}

class CashbillLog
{
	var $docLogType;
	var $log;
	var $procType;
	var $procMemo;
	var $regDT;
	var $ip;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->ip) ? $this->ip = $jsonInfo->ip : null;
		isset($jsonInfo->docLogType) ? $this->docLogType = $jsonInfo->docLogType : null;
		isset($jsonInfo->log) ? $this->log = $jsonInfo->log : null;
		isset($jsonInfo->procType) ? $this->procType = $jsonInfo->procType : null;
		isset($jsonInfo->procMemo) ? $this->procMemo = $jsonInfo->procMemo : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;

	}
}

class MemoRequest {
	var $memo;
}
class IssueRequest {
	var $memo;
}

class CBSearchResult {
  var $code;
  var $total;
  var $perPage;
  var $pageNum;
  var $pageCount;
  var $message;
  var $list;
  function fromJsonInfo($jsonInfo) {
    isset($jsonInfo->code ) ? $this->code = $jsonInfo->code : null;
    isset($jsonInfo->total ) ? $this->total = $jsonInfo->total : null;
    isset($jsonInfo->perPage ) ? $this->perPage = $jsonInfo->perPage : null;
    isset($jsonInfo->pageNum ) ? $this->pageNum = $jsonInfo->pageNum : null;
    isset($jsonInfo->pageCount ) ? $this->pageCount = $jsonInfo->pageCount : null;
    isset($jsonInfo->message ) ? $this->message = $jsonInfo->message : null;
    $InfoList = array();
    for ( $i = 0; $i < Count($jsonInfo->list); $i++ ) {
      $InfoObj = new CashbillInfo();
      $InfoObj->fromJsonInfo($jsonInfo->list[$i]);
      $InfoList[$i] = $InfoObj;
    }
    $this->list = $InfoList;
  }
}
?>
