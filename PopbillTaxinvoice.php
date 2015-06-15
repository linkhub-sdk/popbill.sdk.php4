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
* Written : 2015-06-15
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

define('MgtKeyType_SELL','SELL');
define('MgtKeyType_BUY','BUY');
define('MgtKeyType_TRUSTEE','TRUSTEE');

class TaxinvoiceService extends PopbillBase {
	
	function TaxinvoiceService($LinkID,$SecretKey) {
    	parent::PopbillBase($LinkID,$SecretKey);
    	$this->AddScope('110');
    }
    
    //팝빌 세금계산서 연결 url
    function GetURL($CorpNum,$UserID,$TOGO) {
    	$result = $this->executeCURL('/Taxinvoice/?TG='.$TOGO,$CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }
    
    //관리번호 사용여부 확인
    function CheckMgtKeyInUse($CorpNum,$MgtKeyType,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$response = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey,$CorpNum);
    	
    	if(is_a($response,'PopbillException')) {
    		if($response->code == -11000005) { return false;}
    		return $response;
    	}
    	else {
    		return is_null($response->itemKey) == false;
    	}
    }
    
    //임시저장
    function Register($CorpNum, $Taxinvoice, $UserID = null, $writeSpecification = false) {
    	if($writeSpecification) {
    		$Taxinvoice->writeSpecification = $writeSpecification;
    	}
    	$postdata = $this->Linkhub->json_encode($Taxinvoice);
    	return $this->executeCURL('/Taxinvoice',$CorpNum,$UserID,true,null,$postdata);

    }    
    
    //삭제
    function Delete($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'DELETE','');
    }
    
    //수정
    function Update($CorpNum,$MgtKeyType,$MgtKey,$Taxinvoice, $UserID = null, $writeSpecification = false) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	if($writeSpecification) {
    		$Taxinvoice->writeSpecification = $writeSpecification;
    	}
    	
    	$postdata = $this->Linkhub->json_encode($Taxinvoice);
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true, 'PATCH', $postdata);
    }
    
    //발행예정
    function Send($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$EmailSubject = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$Request->emailSubject = $EmailSubject;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'SEND',$postdata);
    }
    
    //발행예정취소
    function CancelSend($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'CANCELSEND',$postdata);
    }
    
    //발행예정 승인
    function Accept($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'ACCEPT',$postdata);
    }
    
    //발행예정 거부
    function Deny($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'DENY',$postdata);
    }
    
    //발행
    function Issue($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$EmailSubject = null , $ForceIssue = false, $UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new IssueRequest();
    	$Request->memo = $Memo;
    	$Request->emailSubject = $EmailSubject;
    	$Request->forceIssue = $ForceIssue;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'ISSUE',$postdata);
    }
    
    //발행취소
    function CancelIssue($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'CANCELISSUE',$postdata);
    }
    
    //역)발행요청
    function Request($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'REQUEST',$postdata);
    }
    
    //역)발행요청 거부
    function Refuse($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'REFUSE',$postdata);
    }
    
    //역)발행요청 취소
    function CancelRequest($CorpNum,$MgtKeyType,$MgtKey,$Memo = '',$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'CANCELREQUEST',$postdata);
    }
    
    //국세청 즉시전송 요청
    function SendToNTS($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'NTS','');
    }
    
    //알림메일 재전송
    function SendEmail($CorpNum,$MgtKeyType,$MgtKey,$Receiver,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $Receiver);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'EMAIL',$postdata);
    }
    
    //알림문자 재전송
    function SendSMS($CorpNum,$MgtKeyType,$MgtKey,$Sender,$Receiver,$Contents,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $Receiver,'sender'=>$Sender,'contents' => $Contents);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'SMS',$postdata);
    }
    
    //알림팩스 재전송
    function SendFAX($CorpNum,$MgtKeyType,$MgtKey,$Sender,$Receiver,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $Receiver,'sender'=>$Sender);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum, $UserID, true,'FAX',$postdata);
    }
    
    //세금계산서 요약정보 및 상태정보 확인
    function GetInfo($CorpNum,$MgtKeyType,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
		$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey, $CorpNum);
		
		$TaxinvoiceInfo = new TaxinvoiceInfo();
		$TaxinvoiceInfo->fromJsonInfo($result);
		
		return $TaxinvoiceInfo;
    }
    
    //세금계산서 상세정보 확인 
    function GetDetailInfo($CorpNum,$MgtKeyType,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
		$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'?Detail', $CorpNum);
		
		if(is_a($result, 'PopbillException')){ return $result; }

		$TaxinvoiceDetail = new Taxinvoice();
		$TaxinvoiceDetail->fromJsonInfo($result);
		return $TaxinvoiceDetail;
	}
	
    //세금계산서 요약정보 다량확인 최대 1000건
    function GetInfos($CorpNum,$MgtKeyType,$MgtKeyList = array()) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($MgtKeyList);
    	
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType, $CorpNum, null, true,null,$postdata);

		$TaxinvoiceInfoList = array();

		for($i = 0; $i < Count($result); $i++){
			$TaxinvoiceInfo = new TaxinvoiceInfo();
			$TaxinvoiceInfo->fromJsonInfo($result[$i]);
			$TaxinvoiceInfoList[$i] = $TaxinvoiceInfo;
		}
		return $TaxinvoiceInfoList;
    }
    
    //세금계산서 문서이력 확인 
    function GetLogs($CorpNum,$MgtKeyType,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'/Logs', $CorpNum);
		
		$TaxinvoiceLogList = array();
		
		if(is_a($result, 'PopbillException')){ return $result; }

		for($i=0; $i<Count($result); $i++){
			$TaxinvoiceLogObj = new TaxinvoiceLog();
			$TaxinvoiceLogObj->fromJsonInfo($result[$i]);

			$TaxinvoiceLogList[$i] = $TaxinvoiceLogObj;

		}
		return $TaxinvoiceLogList;

    }
    
    //파일첨부
    function AttachFile($CorpNum,$MgtKeyType,$MgtKey,$FilePath , $UserID = null) {
    
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    
    	$postdata = array('Filedata' => '@'.$FilePath);
    	
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'/Files', $CorpNum, $UserID, true,null,$postdata,true);
    
    }
    
    //첨부파일 목록 확인 
    function GetFiles($CorpNum,$MgtKeyType,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
		
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'/Files', $CorpNum);
    }
    
    //첨부파일 삭제 
    function DeleteFile($CorpNum,$MgtKeyType,$MgtKey,$FileID,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	if(is_null($FileID) || empty($FileID)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "파일아이디가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'/Files/'.$FileID, $CorpNum,$UserID,true,'DELETE','');
    }
    
    //팝업URL
    function GetPopUpURL($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'?TG=POPUP', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }
    
    //인쇄URL
    function GetPrintURL($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'?TG=PRINT', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }

    //공급받는자 인쇄URL
    function GetEPrintURL($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
        }
        
        $result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'?TG=EPRINT', $CorpNum,$UserID);
        if(is_a($result,'PopbillException')) return $result;
        
        return $result->url;
    }
    
    //공급받는자 메일URL
    function GetMailURL($CorpNum,$MgtKeyType,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'/'.$MgtKey.'?TG=MAIL', $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }
    
    //세금계산서 다량인쇄 URL
    function GetMassPrintURL($CorpNum,$MgtKeyType,$MgtKeyList = array(),$UserID = null) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($MgtKeyList);
    	
    	$result = $this->executeCURL('/Taxinvoice/'.$MgtKeyType.'?Print', $CorpNum, $UserID, true,null,$postdata);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }
    
    //회원인증서 만료일 확인
    function GetCertificateExpireDate($CorpNum) {
    	$result = $this->executeCURL('/Taxinvoice?cfg=CERT', $CorpNum);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->certificateExpiration;
    }
    
    //발행단가 확인
    function GetUnitCost($CorpNum) {
    	$result = $this->executeCURL('/Taxinvoice?cfg=UNITCOST', $CorpNum);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->unitCost;
    }
    
    //대용량 연계사업자 유통메일목록 확인
    function GetEmailPublicKeys($CorpNum) {
    	return $this->executeCURL('/Taxinvoice/EmailPublicKeys', $CorpNum);
    }
    
}

class Taxinvoice{
	var $writeSpecification;
	var $writeDate;
	var $chargeDirection;
	var $issueType;
	var $issueTiming;
	var $taxType;
	var $invoicerCorpNum;
	var $invoicerMgtKey;
	var $invoicerTaxRegID;
	var $invoicerCorpName;
	var $invoicerCEOName;
	var $invoicerAddr;
	var $invoicerBizClass;
	var $invoicerBizType;
	var $invoicerContactName;
	var $invoicerDeptName;
	var $invoicerTEL;
	var $invoicerHP;
	var $invoicerEmail;
	var $invoicerSMSSendYN;
	
	var $invoiceeCorpNum;
	var $invoiceeType;
	var $invoiceeMgtKey;
	var $invoiceeTaxRegID;
	var $invoiceeCorpName;
	var $invoiceeCEOName;
	var $invoiceeAddr;
	var $invoiceeBizClass;
	var $invoiceeBizType;
	var $invoiceeContactName1;
	var $invoiceeDeptName1;
	var $invoiceeTEL1;
	var $invoiceeHP1;
	var $invoiceeEmail2;
	var $invoiceeContactName2;
	var $invoiceeDeptName2;
	var $invoiceeTEL2;
	var $invoiceeHP2;
	var $invoiceeEmail1;
	var $invoiceeSMSSendYN;
	
	var $trusteeCorpNum;
	var $trusteeMgtKey;
	var $trusteeTaxRegID;
	var $trusteeCorpName;
	var $trusteeCEOName;
	var $trusteeAddr;
	var $trusteeBizClass;
	var $trusteeBizType;
	var $trusteeContactName;
	var $trusteeDeptName;
	var $trusteeTEL;
	var $trusteeHP;
	var $trusteeEmail;
	var $trusteeSMSSendYN;
	
	var $taxTotal;
	var $supplyCostTotal;
	var $totalAmount;
	var $modifyCode;
	var $purposeType;
	var $serialNum;
	var $cash;
	var $chkBill;
	var $credit;
	var $note;
	var $remark1;
	var $remark2;
	var $remark3;
	var $kwon;
	var $ho;
	var $businessLicenseYN;
	var $bankBookYN;
	var $faxsendYN;
	var $faxreceiveNum;
	var $originalTaxinvoiceKey;
	var $ntsconfirmNum;
	var $detailList;
	var $addContactList;

	function fromjsonInfo($jsonInfo){

		isset($jsonInfo->writeSpecification ) ? ($this->writeSpecification = $jsonInfo->writeSpecification) : null;
		isset($jsonInfo->writeDate ) ? ($this->writeDate = $jsonInfo->writeDate ) : null;
		isset($jsonInfo->chargeDirection ) ? ($this->chargeDirection = $jsonInfo->chargeDirection ) : null;
		isset($jsonInfo->issueType ) ? ($this->issueType = $jsonInfo->issueType ) : null;
		isset($jsonInfo->issueTiming ) ? ($this->issueTiming  = $jsonInfo->issueTiming ) : null;
		isset($jsonInfo->taxType ) ? ($this->taxType  = $jsonInfo->taxType ) : null;
		isset($jsonInfo->invoicerCorpNum ) ? ($this->invoicerCorpNum = $jsonInfo->invoicerCorpNum ) : null;
		isset($jsonInfo->invoicerMgtKey ) ? ($this->invoicerMgtKey = $jsonInfo->invoicerMgtKey ) : null;
		isset($jsonInfo->invoicerTaxRegID) ? ($this->invoicerTaxRegID = $jsonInfo->invoicerTaxRegID ) : null;
		isset($jsonInfo->invoicerCorpName ) ? ($this->invoicerCorpName = $jsonInfo->invoicerCorpName ) : null; 
		isset($jsonInfo->invoicerCEOName ) ? ($this->invoicerCEOName = $jsonInfo->invoicerCEOName ) : null; 
		isset($jsonInfo->invoicerAddr ) ? ($this->invoicerAddr = $jsonInfo->invoicerAddr ) : null; 
		isset($jsonInfo->invoicerBizClass ) ? ($this->invoicerBizClass = $jsonInfo->invoicerBizClass ) : null; 
		isset($jsonInfo->invoicerBizType ) ? ($this->invoicerBizType= $jsonInfo->invoicerBizType ) : null; 
		isset($jsonInfo->invoicerContactName ) ? ($this->invoicerContactName = $jsonInfo->invoicerContactName ) : null; 
		isset($jsonInfo->invoicerDeptName ) ? ($this->invoicerDeptName = $jsonInfo->invoicerDeptName) : null; 
		isset($jsonInfo->invoicerTEL ) ? ($this->invoicerTEL = $jsonInfo->invoicerTEL ) : null; 
		isset($jsonInfo->invoicerHP ) ? ($this->invoicerHP = $jsonInfo->invoicerHP ) : null; 
		isset($jsonInfo->invoicerEmail ) ? ($this->invoicerEmail = $jsonInfo->invoicerEmail ) : null; 
		isset($jsonInfo->invoicerSMSSendYN ) ? ($this->invoicerSMSSendYN = $jsonInfo->invoicerSMSSendYN ) : null; 
		
		isset($jsonInfo->invoiceeCorpNum ) ? ($this->invoiceeCorpNum = $jsonInfo->invoiceeCorpNum ) : null; 
		isset($jsonInfo->invoiceeType ) ? ($this->invoiceeType = $jsonInfo->invoiceeType ) : null; 
		isset($jsonInfo->invoiceeMgtKey ) ? ($this->invoiceeMgtKey = $jsonInfo->invoiceeMgtKey ) : null; 
		isset($jsonInfo->invoiceeTaxRegID ) ? ($this-> invoiceeTaxRegID = $jsonInfo->invoiceeTaxRegID ) : null; 
		isset($jsonInfo->invoiceeCorpName ) ? ($this->invoiceeCorpName = $jsonInfo->invoiceeCorpName ) : null; 
		isset($jsonInfo->invoiceeCEOName ) ? ($this->invoiceeCEOName = $jsonInfo->invoiceeCEOName ) : null; 
		isset($jsonInfo->invoiceeAddr ) ? ($this->invoiceeAddr = $jsonInfo->invoiceeAddr ) : null; 
		isset($jsonInfo->invoiceeBizClass ) ? ($this->invoiceeBizClass = $jsonInfo->invoiceeBizClass ) : null; 
		isset($jsonInfo->invoiceeBizType ) ? ($this->invoiceeBizType = $jsonInfo->invoiceeBizType ) : null; 
		isset($jsonInfo->invoiceeContactName1 ) ? ($this->invoiceeContactName1 = $jsonInfo->invoiceeContactName1 ) : null; 
		isset($jsonInfo->invoiceeDeptName1 ) ? ($this->invoiceeDeptName1 = $jsonInfo->invoiceeDeptName1 ) : null; 
		isset($jsonInfo->invoiceeTEL1 ) ? ($this->invoiceeTEL1 = $jsonInfo->invoiceeTEL1 ) : null; 
		isset($jsonInfo->invoiceeHP1 ) ? ($this->invoiceeHP1 = $jsonInfo->invoiceeHP1 ) : null; 
		isset($jsonInfo->invoiceeEmail2 ) ? ($this->invoiceeEmail2 = $jsonInfo->invoiceeEmail2 ) : null; 
		isset($jsonInfo->invoiceeContactName2 ) ? ($this->invoiceeContactName2 = $jsonInfo->invoiceeContactName2 ) : null; 
		isset($jsonInfo->invoiceeDeptName2 ) ? ($this->invoiceeDeptName2 = $jsonInfo->invoiceeDeptName2 ) : null; 
		isset($jsonInfo->invoiceeTEL2 ) ? ($this->invoiceeTEL2 = $jsonInfo->invoiceeTEL2 ) : null; 
		isset($jsonInfo->invoiceeHP2 ) ? ($this->invoiceeHP2 = $jsonInfo->invoiceeHP2 ) : null; 
		isset($jsonInfo->invoiceeEmail1 ) ? ($this->invoiceeEmail1 = $jsonInfo->invoiceeEmail1 ) : null; 
		isset($jsonInfo->invoiceeSMSSendYN ) ? ($this->invoiceeSMSSendYN = $jsonInfo->invoiceeSMSSendYN ) : null; 
		
		isset($jsonInfo->trusteeCorpNum ) ? ($this->trusteeCorpNum  = $jsonInfo->trusteeCorpNum ) : null; 
		isset($jsonInfo->trusteeMgtKey ) ? ($this->trusteeMgtKey = $jsonInfo->trusteeMgtKey ) : null; 
		isset($jsonInfo->trusteeTaxRegID ) ? ($this->trusteeTaxRegID = $jsonInfo->trusteeTaxRegID ) : null; 
		isset($jsonInfo->trusteeCorpName ) ? ($this->trusteeCorpName = $jsonInfo->trusteeCorpName ) : null; 
		isset($jsonInfo->trusteeCEOName ) ? ($this->trusteeCEOName = $jsonInfo->trusteeCEOName ) : null; 
		isset($jsonInfo->trusteeAddr ) ? ($this->trusteeAddr = $jsonInfo->trusteeAddr ) : null; 
		isset($jsonInfo->trusteeBizClass ) ? ($this->trusteeBizClass = $jsonInfo->trusteeBizClass ) : null; 
		isset($jsonInfo->trusteeBizType ) ? ($this->trusteeBizType = $jsonInfo->trusteeBizType ) : null; 
		isset($jsonInfo->trusteeContactName ) ? ($this->trusteeContactName = $jsonInfo->trusteeContactName ) : null; 
		isset($jsonInfo->trusteeDeptName ) ? ($this->trusteeDeptName  = $jsonInfo->trusteeDeptName ) : null; 
		isset($jsonInfo->trusteeTEL ) ? ($this->trusteeTEL = $jsonInfo->trusteeTEL ) : null; 
		isset($jsonInfo->trusteeHP ) ? ($this->trusteeHP = $jsonInfo->trusteeHP ) : null; 
		isset($jsonInfo->trusteeEmail ) ? ($this->trusteeEmail = $jsonInfo->trusteeEmail ) : null; 
		isset($jsonInfo->trusteeSMSSendYN ) ? ($this->trusteeSMSSendYN = $jsonInfo->trusteeSMSSendYN ) : null; 
		
		isset($jsonInfo->taxTotal ) ? ($this->taxTotal = $jsonInfo->taxTotal ) : null; 
		isset($jsonInfo->supplyCostTotal ) ? ($this->supplyCostTotal = $jsonInfo->supplyCostTotal ) : null; 
		isset($jsonInfo->totalAmount ) ? ($this->totalAmount = $jsonInfo->totalAmount ) : null; 
		isset($jsonInfo->modifyCode ) ? ($this->modifyCode = $jsonInfo->modifyCode ) : null; 
		isset($jsonInfo->purposeType ) ? ($this->purposeType = $jsonInfo->purposeType ) : null; 
		isset($jsonInfo->serialNum ) ? ($this->serialNum = $jsonInfo->serialNum ) : null; 
		isset($jsonInfo->cash ) ? ($this->cash = $jsonInfo->cash ) : null; 
		isset($jsonInfo->chkBill ) ? ($this->chkBill = $jsonInfo->chkBill ) : null; 
		isset($jsonInfo->credit ) ? ($this->credit = $jsonInfo->credit ) : null; 
		isset($jsonInfo->note ) ? ($this->note = $jsonInfo->note ) : null; 
		isset($jsonInfo->remark1 ) ? ($this->remark1 = $jsonInfo->remark1 ) : null; 
		isset($jsonInfo->remark2 ) ? ($this->remark2 = $jsonInfo->remark2 ) : null; 
		isset($jsonInfo->remark3 ) ? ($this->remark3 = $jsonInfo->remark3 ) : null; 
		isset($jsonInfo->kwon ) ? ($this->kwon = $jsonInfo->kwon ) : null; 
		isset($jsonInfo->ho ) ? ($this->ho = $jsonInfo->ho ) : null; 
		isset($jsonInfo->businessLicenseYN ) ? ($this->businessLicenseYN = $jsonInfo->businessLicenseYN ) : null; 
		isset($jsonInfo->bankBookYN ) ? ($this->bankBookYN = $jsonInfo->bankBookYN ) : null; 
		isset($jsonInfo->faxsendYN ) ? ($this->faxsendYN = $jsonInfo->faxsendYN ) : null; 
		isset($jsonInfo->faxreceiveNum ) ? ($this->faxreceiveNum = $jsonInfo->faxreceiveNum ) : null; 
		isset($jsonInfo->originalTaxinvoiceKey ) ? ($this->originalTaxinvoiceKey = $jsonInfo->originalTaxinvoiceKey ) : null; 
		isset($jsonInfo->ntsconfirmNum ) ? ($this->ntsconfirmNum = $jsonInfo->ntsconfirmNum) : null;

		if (isset($jsonInfo->detailList)) {
			$DetailList = array();
			for($i=0; $i<Count($jsonInfo->detailList); $i++){
				$TaxinvoiceDetailObj = new TaxinvoiceDetail();
				$TaxinvoiceDetailObj->fromJsonInfo($jsonInfo->detailList[$i]);
				$DetailList[$i] = $TaxinvoiceDetailObj;
			}
			$this->detailList = $DetailList;
		}

		if (isset($jsonInfo->addContactList)) {
			$contactList = array();
			for($i=0; $i<Count($jsonInfo->addContactList); $i++){
				$TaxinvoiceContactObj = new TaxinvoiceAddContact();
				$TaxinvoiceContactObj->fromJsonInfo($jsonInfo->addContactList[$i]);
				$contactList[$i] = $TaxinvoiceContactObj;
			}

			$this->addContactList = $contactList;
		}
	}
}


class TaxinvoiceDetail {
	var $serialNum;
	var $purchaseDT;
	var $itemName;
	var $spec;
	var $qty;
	var $unitCost;
	var $supplyCost;
	var $tax;
	var $remark;
	
	function fromJsonInfo($jsonInfo) {
		isset($jsonInfo->serialNum) ? $this->serialNum = $jsonInfo->serialNum : null;
		isset($jsonInfo->purchaseDT) ? $this->purchaseDT = $jsonInfo->purchaseDT : null;
		isset($jsonInfo->itemName) ? $this->itemName = $jsonInfo->itemName : null;
		isset($jsonInfo->spec) ? $this->spec = $jsonInfo->spec : null;
		isset($jsonInfo->qty) ? $this->qty = $jsonInfo->qty : null;
		isset($jsonInfo->unitCost) ? $this->unitCost = $jsonInfo->unitCost : null;
		isset($jsonInfo->supplyCost) ? $this->supplyCost = $jsonInfo->supplyCost : null;
		isset($jsonInfo->tax) ? $this->tax = $jsonInfo->tax : null;
		isset($jsonInfo->remark) ? $this->remark = $jsonInfo->remark : null;
	}
}
class TaxinvoiceAddContact {
	var $serialNum;
	var $email;
	var $contactName;

	
	function fromJsonInfo($jsonInfo) {
		isset($jsonInfo->serialNum) ? $this->serialNum = $jsonInfo->serialNum : null;
		isset($jsonInfo->email) ? $this->email = $jsonInfo->email : null;
		isset($jsonInfo->contactName) ? $this->contactName = $jsonInfo->contactName : null;
	}
}

class TaxinvoiceInfo {
	var $itemKey;							
	var $taxType;
	var $writeDate;
	var $regDT; 
	var $invoicerCorpName; 
	var $invoicerCorpNum; 
	var $invoicerMgtKey; 
	var $invoiceeCorpName; 
	var $invoiceeCorpNum; 
	var $invoiceeMgtKey; 
	var $trusteeCorpName; 
	var $trusteeCorpNum; 
	var $trusteeMgtKey; 
	var $supplyCostTotal; 
	var $taxTotal; 
	var $purposeType; 
	var $modifyCode; 
	var $issueType; 
	var $issueDT; 
	var $preIssueDT; 
	var $stateCode; 
	var $stateDT; 
	var $openYN; 
	var $openDT;
	var $ntsconfirmNum; 
	var $ntssendDT; 
	var $ntsresultDT; 
	var $ntssendErrCode;
	var $stateMemo; 
	var $ntsresult; 

	function fromJsonInfo($jsonInfo) {
		isset($jsonInfo->itemKey) ? ($this->itemKey = $jsonInfo->itemKey) : null;
		isset($jsonInfo->taxType) ? $this->taxType = $jsonInfo->taxType : null;
		isset($jsonInfo->writeDate) ? $this->writeDate = $jsonInfo->writeDate  : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT  : null;
		isset($jsonInfo->invoicerCorpName) ? $this->invoicerCorpName = $jsonInfo->invoicerCorpName  : null;
		isset($jsonInfo->invoicerCorpNum) ? $this->invoicerCorpNum = $jsonInfo->invoicerCorpNum : null;
		isset($jsonInfo->invoicerMgtKey) ? $this->invoicerMgtKey = $jsonInfo->invoicerMgtKey : null;
		isset($jsonInfo->invoiceeCorpName) ? $this->invoiceeCorpName = $jsonInfo->invoiceeCorpName : null;
		isset($jsonInfo->invoiceeCorpNum) ? $this->invoiceeCorpNum = $jsonInfo->invoiceeCorpNum : null;
		isset($jsonInfo->invoiceeMgtKey) ? $this->invoiceeMgtKey = $jsonInfo->invoiceeMgtKey : null;
		isset($jsonInfo->trusteeCorpName) ? $this->trusteeCorpName = $jsonInfo->trusteeCorpName : null;
		isset($jsonInfo->trusteeCorpNum) ? $this->trusteeCorpNum = $jsonInfo->trusteeCorpNum : null;
		isset($jsonInfo->trusteeMgtKey) ? $this->trusteeMgtKey = $jsonInfo->trusteeMgtKey : null;
		isset($jsonInfo->supplyCostTotal) ? $this->supplyCostTotal = $jsonInfo->supplyCostTotal : null;
		isset($jsonInfo->taxTotal) ? $this->taxTotal = $jsonInfo->taxTotal : null;
		isset($jsonInfo->purposeType) ? $this->purposeType = $jsonInfo->purposeType : null;
		isset($jsonInfo->modifyCode) ? $this->modifyCode = $jsonInfo->modifyCode : null;
		isset($jsonInfo->issueType) ? $this->issueType = $jsonInfo->issueType : null;
		isset($jsonInfo->preIssueDT) ? $this->preIssueDT = $jsonInfo->preIssueDT : null;
		isset($jsonInfo->stateCode) ? $this->stateCode = $jsonInfo->stateCode : null;
		isset($jsonInfo->stateDT) ? $this->stateDT = $jsonInfo->stateDT : null;
		isset($jsonInfo->openYN) ? $this->openYN = $jsonInfo->openYN : null;
		isset($jsonInfo->openDT) ? $this->openDT = $jsonInfo->openDT : null;
		isset($jsonInfo->ntsconfirmNum) ? $this->ntsconfirmNum = $jsonInfo->ntsconfirmNum : null;
		isset($jsonInfo->ntssendDT) ? $this->ntssendDT = $jsonInfo->ntssendDT : null;
		isset($jsonInfo->ntsresultDT) ? $this->ntsresultDT = $jsonInfo->ntsresultDT : null;
		isset($jsonInfo->ntssendErrCode) ? $this->ntssendErrCode = $jsonInfo->ntssendErrCode : null;
		isset($jsonInfo->stateMemo) ? $this->stateMemo = $jsonInfo->stateMemo : null;
		isset($jsonInfo->ntsresult) ? $this->ntsresult = $jsonInfo->ntsresult : null;
    }
}

class TaxinvoiceLog {
	var $ip;
	var $docLogType;
	var $log;
	var $procType;
	var $procCorpName;
	var $procMemo;
	var $regDT;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->ip) ? $this->ip = $jsonInfo->ip : null;
		isset($jsonInfo->docLogType) ? $this->docLogType = $jsonInfo->docLogType : null;
		isset($jsonInfo->log) ? $this->log = $jsonInfo->log : null;
		isset($jsonInfo->procType) ? $this->procType = $jsonInfo->procType : null;
		isset($jsonInfo->procCorpName) ? $this->procCorpName = $jsonInfo->procCorpName : null;
		isset($jsonInfo->procMemo) ? $this->procMemo = $jsonInfo->procMemo : null;
		isset($jsonInfo->regDT) ? $this->regDT = $jsonInfo->regDT : null;
	}
}

class MemoRequest {
	var $memo;
	var $emailSubject;
}

class IssueRequest {
	var $memo;
	var $emailSubject;
	var $forceIssue;
}
?>
