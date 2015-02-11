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
*
* Thanks for your interest.
* We welcome any suggestions, feedbacks, blames or anything.
* ======================================================================================
*/
require_once 'popbill.php';

class StatementService extends PopbillBase {
	
	function StatementService($LinkID,$SecretKey) {
    	parent::PopbillBase($LinkID,$SecretKey);
    	$this->AddScope('121');
    	$this->AddScope('122');
    	$this->AddScope('123');
    	$this->AddScope('124');
    	$this->AddScope('125');
    	$this->AddScope('126');
    }

    # 발행단가 확인
    function GetUnitCost($CorpNum, $itemCode) {
    	$result = $this->executeCURL('/Statement/'.$itemCode.'?cfg=UNITCOST', $CorpNum);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->unitCost;
    }
    
	# 문서관리번호 사용여부 확인
    function CheckMgtKeyInUse($CorpNum,$itemCode,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$response = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum);

    	if(is_a($response,'PopbillException')) {
    		if($response->code == -12000004) { return false;}
    		return $response;
    	}
    	else {
    		return is_null($response->itemKey) == false;
    	}
    }
	
	# 임시저장
    function Register($CorpNum, $Statement, $UserID = null) {
    	$postdata = $this->Linkhub->json_encode($Statement);
    	return $this->executeCURL('/Statement/',$CorpNum,$UserID,true,null,$postdata);
    }


	# 수정
    function Update($CorpNum,$itemCode,$MgtKey,$Statement, $UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($Statement);
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum,$UserID,true,'PATCH',$postdata);
    }


	# 삭제
    function Delete($CorpNum,$itemCode,$MgtKey,$UserID = null) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Statement/'.$itemCode."/".$MgtKey, $CorpNum, $UserID,true,'DELETE','');
    }


    # 발행
    function Issue($CorpNum, $itemCode, $MgtKey, $Memo, $UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new IssueRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Statement/'.$itemCode."/".$MgtKey, $CorpNum, $UserID, true, 'ISSUE',$postdata);
    }

    # 발행취소
    function CancelIssue($CorpNum, $itemCode, $MgtKey, $Memo, $UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$Request = new MemoRequest();
    	$Request->memo = $Memo;
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Statement/'.$itemCode."/".$MgtKey, $CorpNum, $UserID, true, 'CANCEL',$postdata);
    }
	

	# 파일첨부
    function AttachFile($CorpNum,$itemCode,$MgtKey,$FilePath,$UserID = null) {
    
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    
    	$postdata = array('Filedata' => '@'.$FilePath);
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'/Files',$CorpNum,$UserID, true, null, $postdata, true);
    }

    # 첨부파일 목록 확인
    function GetFiles($CorpNum,$itemCode,$MgtKey,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'/Files',$CorpNum,$UserID);
    }
    

    # 첨부파일 삭제 
    function DeleteFile($CorpNum,$itemCode,$MgtKey,$FileID,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	if(is_null($FileID) || empty($FileID)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "파일아이디가 입력되지 않았습니다."}');
    	}
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'/Files/'.$FileID,$CorpNum,$UserID, true,'DELETE',null);
    }

	# 전자명세서 요약정보 및 상태정보 확인
    function GetInfo($CorpNum,$itemCode,$MgtKey,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum,$UserID);
	
		if(is_a($result, 'PopbillException')) return $result;
			
		$StatementInfo = new StatementInfo();
		$StatementInfo->fromJsonInfo($result);
		return $StatementInfo;
    }
	
	# 다량 전자명세서 상태,요약 정보확인
    function GetInfos($CorpNum,$itemCode,$MgtKeyList = array(),$UserID) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($MgtKeyList);
    	
    	$result = $this->executeCURL('/Statement/'.$itemCode, $CorpNum,null,true,null,$postdata);
		
		if(is_a($result,'PopbillException')) return $result;
		
		$StatementInfoList = array();

		for($i=0; $i<Count($result); $i++){
			$StmtInfoObj = new StatementInfo();
			$StmtInfoObj->fromJsonInfo($result[$i]);
			$StatementInfoList[$i] = $StmtInfoObj;
		}

		return $StatementInfoList;
	
    }

	# 이력 확인
	function GetLogs($CorpNum,$itemCode,$MgtKey) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}

    	$result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'/Logs',$CorpNum);
		
		if(is_a($result, 'PopbillException')) return $result;

		$StatementLogList = array();

		for($i=0; $i<Count($result); $i++){
			$StmtLog = new StatementLog();
			$StmtLog->fromJsonInfo($result[$i]);
			$StatementLogList[$i] = $StmtLog;
		}
		return $StatementLogList;
    }
	

	# 상세정보 확인
    function GetDetailInfo($CorpNum,$itemCode,$MgtKey,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	$result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'?Detail',$CorpNum,$UserID);
		if(is_a($result, 'PopbillException')) return $result;

		$StatementDetail = new Statement();
		$StatementDetail->fromJsonInfo($result);

		return $StatementDetail;

    }

	# 알림메일 재전송
    function SendEmail($CorpNum,$itemCode,$MgtKey,$receiver,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $receiver);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum,$UserID,true,'EMAIL',$postdata);
    }
    
	# 알림문자 재전송
    function SendSMS($CorpNum,$itemCode,$MgtKey,$sender,$receiver,$contents,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $receiver,'sender'=>$sender,'contents' => $contents);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum,$UserID,true,'SMS',$postdata);
    }


	# 전자명세서 팩스전송
    function SendFAX($CorpNum,$itemCode,$MgtKey,$sender,$receiver,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$Request = array('receiver' => $receiver,'sender'=>$sender);
    	$postdata = $this->Linkhub->json_encode($Request);
    	
    	return $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey,$CorpNum,$UserID,true,'FAX',$postdata);
    }


	# 팝빌 전자명세서 연결 URL
    function GetURL($CorpNum,$UserID,$TOGO) {
    	$result = $this->executeCURL('/Statement?TG='.$TOGO, $CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }

    # 인쇄URL
    function GetPrintURL($CorpNum,$itemCode,$MgtKey,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'?TG=PRINT',$CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }

	# 공급받는자 인쇄URL
    function GetEPrintURL($CorpNum,$itemCode,$MgtKey,$UserID) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
        }
        
        $result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'?TG=EPRINT',$CorpNum,$UserID);
        if(is_a($result,'PopbillException')) return $result;
        
        return $result->url;
    }

	# 전자명세서 보기 URL
    function GetPopUpURL($CorpNum,$itemCode,$MgtKey,$UserID) {
        if(is_null($MgtKey) || empty($MgtKey)) {
            return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
        }
        
        $result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'?TG=POPUP',$CorpNum,$UserID);
        if(is_a($result,'PopbillException')) return $result;
        
        return $result->url;
    }

	# 전자명세서 다량인쇄 URL
    function GetMassPrintURL($CorpNum,$itemCode,$MgtKeyList = array(),$UserID) {
    	if(is_null($MgtKeyList) || empty($MgtKeyList)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$postdata = $this->Linkhub->json_encode($MgtKeyList);
    	
    	$result = $this->executeCURL('/Statement/'.$itemCode.'?Print',$CorpNum,$UserID,true,'',$postdata);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }
    

    # 공급받는자 메일URL
    function GetMailURL($CorpNum,$itemCode,$MgtKey,$UserID) {
    	if(is_null($MgtKey) || empty($MgtKey)) {
    		return new PopbillException('{"code" : -99999999 , "message" : "관리번호가 입력되지 않았습니다."}');
    	}
    	
    	$result = $this->executeCURL('/Statement/'.$itemCode.'/'.$MgtKey.'?TG=MAIL',$CorpNum,$UserID);
    	if(is_a($result,'PopbillException')) return $result;
    	
    	return $result->url;
    }

}

class Statement {
	var $itemCode;             
	var $mgtKey;               
	var $invoiceNum;           
	var $formCode;             
	var $writeDate;            
	var $taxType;              

	var $senderCorpNum;      
	var $senderTaxRegID;     
	var $senderCorpName;     
	var $senderCEOName;      
	var $senderAddr;         
	var $senderBizClass;     
	var $senderBizType;      
	var $senderContactName;  
	var $senderDeptName;     
	var $senderTEL;          
	var $senderHP;           
	var $senderEmail;        
	var $senderFAX;          

	var $receiverCorpNum;    
	var $receiverTaxRegID;   
	var $receiverCorpName;   
	var $receiverCEOName;    
	var $receiverAddr;       
	var $receiverBizClass;   
	var $receiverBizType;    
	var $receiverContactName;
	var $receiverDeptName;   
	var $receiverTEL;        
	var $receiverHP;         
	var $receiverEmail;      
	var $receiverFAX;        

	var $taxTotal;           
	var $supplyCostTotal;    
	var $totalAmount;        
	var $purposeType;        
	var $serialNum;          
	var $remark1;            
	var $remark2;            
	var $remark3;            
	var $businessLicenseYN;  
	var $bankBookYN;         
	var $faxsendYN;          
	var $smssendYN;          
	var $autoacceptYN;       

	var $detailList;
	var $propertyBag;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->itemCode ) ? ($this->itemCode = $jsonInfo->itemCode ) : null;
		isset($jsonInfo->mgtKey ) ? ($this->mgtKey = $jsonInfo->mgtKey ) : null;
		isset($jsonInfo->invoiceNum ) ? ($this->invoiceNum = $jsonInfo->invoiceNum ) : null;
		isset($jsonInfo->formCode ) ? ($this->formCode = $jsonInfo->formCode ) : null;
		isset($jsonInfo->writeDate ) ? ($this->writeDate = $jsonInfo->writeDate ) : null;
		isset($jsonInfo->taxType ) ? ($this->taxType = $jsonInfo->taxType ) : null;

		isset($jsonInfo->senderCorpNum ) ? ($this->senderCorpNum = $jsonInfo->senderCorpNum ) : null;
		isset($jsonInfo->senderTaxRegID ) ? ($this->senderTaxRegID = $jsonInfo->senderTaxRegID ) : null;
		isset($jsonInfo->senderCorpName ) ? ($this->senderCorpName = $jsonInfo->senderCorpName ) : null;
		isset($jsonInfo->senderCEOName ) ? ($this->senderCEOName = $jsonInfo->senderCEOName ) : null;
		isset($jsonInfo->senderAddr ) ? ($this->senderAddr = $jsonInfo->senderAddr ) : null;
		isset($jsonInfo->senderBizClass ) ? ($this->senderBizClass = $jsonInfo->senderBizClass ) : null;
		isset($jsonInfo->senderBizType ) ? ($this->senderBizType = $jsonInfo->senderBizType ) : null;
		isset($jsonInfo->senderContactName ) ? ($this->senderContactName = $jsonInfo->senderContactName ) : null;
		isset($jsonInfo->senderDeptName ) ? ($this->senderDeptName = $jsonInfo->senderDeptName ) : null;
		isset($jsonInfo->senderTEL ) ? ($this->senderTEL = $jsonInfo->senderTEL ) : null;
		isset($jsonInfo->senderHP ) ? ($this->senderHP = $jsonInfo->senderHP ) : null;
		isset($jsonInfo->senderEmail ) ? ($this->senderEmail = $jsonInfo->senderEmail ) : null;
		isset($jsonInfo->senderFAX ) ? ($this->senderFAX = $jsonInfo->senderFAX ) : null;

		isset($jsonInfo->receiverCorpNum ) ? ($this->receiverCorpNum = $jsonInfo->receiverCorpNum ) : null;
		isset($jsonInfo->receiverTaxRegID ) ? ($this->receiverTaxRegID = $jsonInfo->receiverTaxRegID ) : null;
		isset($jsonInfo->receiverCorpName ) ? ($this->receiverCorpName = $jsonInfo->receiverCorpName ) : null;
		isset($jsonInfo->receiverCEOName ) ? ($this->receiverCEOName = $jsonInfo->receiverCEOName ) : null;
		isset($jsonInfo->receiverAddr ) ? ($this->receiverAddr = $jsonInfo->receiverAddr ) : null;
		isset($jsonInfo->receiverBizClass ) ? ($this->receiverBizClass = $jsonInfo->receiverBizClass ) : null;
		isset($jsonInfo->receiverBizType ) ? ($this->receiverBizType = $jsonInfo->receiverBizType ) : null;
		isset($jsonInfo->receiverContactName ) ? ($this->receiverContactName = $jsonInfo->receiverContactName ) : null;
		isset($jsonInfo->receiverDeptName ) ? ($this->receiverDeptName = $jsonInfo->receiverDeptName ) : null;
		isset($jsonInfo->receiverTEL ) ? ($this->receiverTEL = $jsonInfo->receiverTEL ) : null;
		isset($jsonInfo->receiverHP ) ? ($this->receiverHP = $jsonInfo->receiverHP ) : null;

		isset($jsonInfo->receiverEmail ) ? ($this->receiverEmail = $jsonInfo->receiverEmail ) : null;
		isset($jsonInfo->receiverFAX ) ? ($this->receiverFAX = $jsonInfo->receiverFAX ) : null;
		isset($jsonInfo->taxTotal ) ? ($this->taxTotal = $jsonInfo->taxTotal ) : null;
		isset($jsonInfo->supplyCostTotal ) ? ($this->supplyCostTotal = $jsonInfo->supplyCostTotal ) : null;
		isset($jsonInfo->totalAmount ) ? ($this->totalAmount = $jsonInfo->totalAmount ) : null;
		isset($jsonInfo->purposeType ) ? ($this->purposeType = $jsonInfo->purposeType ) : null;
		isset($jsonInfo->serialNum ) ? ($this->serialNum = $jsonInfo->serialNum ) : null;

		isset($jsonInfo->remark1 ) ? ($this->remark1 = $jsonInfo->remark1 ) : null;
		isset($jsonInfo->remark2 ) ? ($this->remark2 = $jsonInfo->remark2 ) : null;
		isset($jsonInfo->remark3 ) ? ($this->remark3 = $jsonInfo->remark3 ) : null;
		isset($jsonInfo->businessLicenseYN ) ? ($this->businessLicenseYN = $jsonInfo->businessLicenseYN ) : null;
		isset($jsonInfo->bankBookYN ) ? ($this->bankBookYN = $jsonInfo->bankBookYN ) : null;

		isset($jsonInfo->faxsendYN ) ? ($this->faxsendYN = $jsonInfo->faxsendYN ) : null;
		isset($jsonInfo->bankBookYN ) ? ($this->bankBookYN = $jsonInfo->bankBookYN ) : null;
		isset($jsonInfo->smssendYN ) ? ($this->smssendYN = $jsonInfo->smssendYN ) : null;
		isset($jsonInfo->autoacceptYN ) ? ($this->autoacceptYN = $jsonInfo->autoacceptYN ) : null;
		
		if(!is_null($jsonInfo->detailList)){
			$StatementDetailList = array();
			for($i=0; $i<Count($jsonInfo->detailList); $i++){
				$StatementDetail = new StatementDetail();
				$StatementDetail->fromJsonInfo($jsonInfo->detailList[$i]);
				$StatementDetailList[$i] = $StatementDetail;
			}

			$this->detailList = $StatementDetailList;
		}

		isset($jsonInfo->propertyBag  ) ? ($this->propertyBag  = $jsonInfo->propertyBag  ) : null;

	}
}

Class StatementDetail{
	var $serialNum;
	var $purchaseDT;
	var $itemName;
	var $spec;
	var $unit;
	var $qty;
	var $unitCost;
	var $supplyCost;
	var $tax;
	var $remark;
	var $spare1;
	var $spare2;
	var $spare3;
	var $spare4;
	var $spare5;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->serialNum ) ? ($this->serialNum = $jsonInfo->serialNum ) : null;
		isset($jsonInfo->purchaseDT ) ? ($this->purchaseDT = $jsonInfo->purchaseDT ) : null;
		isset($jsonInfo->itemName ) ? ($this->itemName = $jsonInfo->itemName ) : null;
		isset($jsonInfo->spec ) ? ($this->spec = $jsonInfo->spec ) : null;
		isset($jsonInfo->unit ) ? ($this->unit = $jsonInfo->unit ) : null;
		isset($jsonInfo->qty ) ? ($this->qty = $jsonInfo->qty ) : null;
		isset($jsonInfo->unitCost ) ? ($this->unitCost = $jsonInfo->unitCost ) : null;
		isset($jsonInfo->supplyCost ) ? ($this->supplyCost = $jsonInfo->supplyCost ) : null;
		isset($jsonInfo->tax ) ? ($this->tax = $jsonInfo->tax ) : null;
		isset($jsonInfo->remark ) ? ($this->remark = $jsonInfo->remark ) : null;
		isset($jsonInfo->spare1 ) ? ($this->spare1 = $jsonInfo->spare1 ) : null;
		isset($jsonInfo->spare2 ) ? ($this->spare2 = $jsonInfo->spare2 ) : null;
		isset($jsonInfo->spare3 ) ? ($this->spare3 = $jsonInfo->spare3 ) : null;
		isset($jsonInfo->spare4 ) ? ($this->spare4 = $jsonInfo->spare4 ) : null;
		isset($jsonInfo->spare5 ) ? ($this->spare5 = $jsonInfo->spare5 ) : null;

	}
}

class StatementInfo {

	var $itemKey;
	var $stateCode;
	var $taxType;
	var $purposeType;
	var $writeDate;
	var $senderCorpName;
	var $senderCorpNum;
	var $receiverCorpName;
	var $receiverCorpNum;
	var $supplyCostTotal;
	var $taxTotal;
	var $issueDT;
	var $stateDT;
	var $openYN;
	var $openDT;
	var $stateMemo;
	var $regDT;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->itemKey ) ? ($this->itemKey = $jsonInfo->itemKey ) : null;
		isset($jsonInfo->stateCode ) ? ($this->stateCode = $jsonInfo->stateCode ) : null;
		isset($jsonInfo->taxType ) ? ($this->taxType = $jsonInfo->taxType ) : null;
		isset($jsonInfo->purposeType ) ? ($this->purposeType = $jsonInfo->purposeType ) : null;
		isset($jsonInfo->writeDate ) ? ($this->writeDate = $jsonInfo->writeDate ) : null;
		isset($jsonInfo->senderCorpName ) ? ($this->senderCorpName = $jsonInfo->senderCorpName ) : null;
		isset($jsonInfo->senderCorpNum ) ? ($this->senderCorpNum = $jsonInfo->senderCorpNum ) : null;
		isset($jsonInfo->receiverCorpName ) ? ($this->receiverCorpName = $jsonInfo->receiverCorpName ) : null;
		isset($jsonInfo->receiverCorpNum ) ? ($this->receiverCorpNum = $jsonInfo->receiverCorpNum ) : null;
		isset($jsonInfo->supplyCostTotal ) ? ($this->supplyCostTotal = $jsonInfo->supplyCostTotal ) : null;
		isset($jsonInfo->taxTotal ) ? ($this->taxTotal = $jsonInfo->taxTotal ) : null;
		isset($jsonInfo->issueDT ) ? ($this->issueDT = $jsonInfo->issueDT ) : null;
		isset($jsonInfo->stateDT ) ? ($this->stateDT = $jsonInfo->stateDT ) : null;
		isset($jsonInfo->openYN ) ? ($this->openYN = $jsonInfo->openYN ) : null;
		isset($jsonInfo->openDT ) ? ($this->openDT = $jsonInfo->openDT ) : null;
		isset($jsonInfo->stateMemo ) ? ($this->stateMemo = $jsonInfo->stateMemo) : null;
		isset($jsonInfo->regDT ) ? ($this->regDT = $jsonInfo->regDT ) : null;
	}
}

class StatementLog {
	var $docLogType;
	var $log;
	var $procType;
	var $procCorpName;
	var $procMemo;
	var $regDT;
	var $ip;

	function fromJsonInfo($jsonInfo){
		isset($jsonInfo->docLogType) ? ($this->docLogType = $jsonInfo->docLogType ) : null;
		isset($jsonInfo->log) ? ($this->log = $jsonInfo->log ) : null;
		isset($jsonInfo->procType) ? ($this->procType = $jsonInfo->procType ) : null;
		isset($jsonInfo->procCorpName) ? ($this->procCorpName = $jsonInfo->procCorpName ) : null;
		isset($jsonInfo->procMemo) ? ($this->procMemo = $jsonInfo->procMemo ) : null;
		isset($jsonInfo->regDT) ? ($this->regDT = $jsonInfo->regDT ) : null;
		isset($jsonInfo->ip) ? ($this->ip = $jsonInfo->ip ) : null;
	}
}


class MemoRequest {
	var $memo;
}
class IssueRequest {
	var $memo;
}
?>
