<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

	use Bitrix\Main\Loader, 
		Bitrix\Main\Entity,
		Bitrix\Main\Application,
		Bitrix\Main\Web\Uri,
		Bitrix\Main\Web\HttpClient,
		Bitrix\Main\Mail\Event,
		Bitrix\Highloadblock as HL; 

class GeoIpHuntingComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	
	private const API_KEY = "a3597a15dc214e9a4c860810c1cf4999";
	private const HL_BLOCK_NAME = "GeoIpData";
	private $emailForSendInfo = ""; 
	private $pageUrl= "";


	/**
	 * префильтры для защиты от запросов со сторонних  ресурсов
	 *
	 * @return void
	 */

    public function configureActions()
    {
        
        return [
            'data' => [
                'prefilters'  => [
					new Bitrix\Main\Engine\ActionFilter\HttpMethod(array(Bitrix\Main\Engine\ActionFilter\HttpMethod::METHOD_POST)),
					new Bitrix\Main\Engine\ActionFilter\Csrf(),
				],
                'postfilters' => []
            ]
        ];
		
    }
	
	/**
	 * Обработка аякс запроса, вернет информацию 
	 * из базы данных или удаленного сервиса данных
	 * или информацию об ошибке
	 *
	 * @param $ipAddress string
	 * @param $emailForSendInfo string
	 * @param $pageUrl string
	 * @return string
	 */
    
	public function dataAction($ipAddress, $emailForSendInfo, $pageUrl)
    {
		
		$this->initParamsForEmail($emailForSendInfo, $pageUrl);
		
		$ipAddress = trim(htmlspecialchars($ipAddress));
		
		if($this->validateIpAddress($ipAddress)) {
			
			$checkExistsLocal = $this->checkDataInHlBlock($ipAddress);
			
			if($checkExistsLocal) {
				
				return $checkExistsLocal;
				
			} else {
				
				return  $this->getRemoteIpData($ipAddress);
				
			}
			
		} else {
			
			$data = array("WARNING_DATA" => "Введите корректный адрес ip.");
			
			return $data;
			
		}   
		
    }

	
	/**
	 * инициализируем данные для отправки информнационных сообщений 
	 *
	 * @return void
	 */

	private function initParamsForEmail($emailForSendInfo, $pageUrl) 
	{
		
		$this->emailForSendInfo = $emailForSendInfo; 
		$this->pageUrl = $pageUrl;
		
	}
	
    
	/**
	 * валидация ip адреса 
	 *
	 *
	 * @param $ip string
	 * @return boolean
	 */
	
	public function validateIpAddress($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			
			return true;
			
		} else {
			
			return false;
			
		}				
	}
	
	
	/**
	 * валидация email адреса 
	 *
	 *
	 * @param $email string
	 * @return boolean
	*/
	
	public function validateEmailAddress($email)
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			
			return true;
			
		} else {
			
			return false;
			
		}				
	}
	

	/**
	 * проверить наличие данных о ip 
	 * в базе данных 
	 *
	 * @param $ip string
	 * @return boolean OR dataDb
	*/
	
	private function checkDataInHlBlock($ip) 
	{
		
		if(!Loader::includeModule("highloadblock")) {
			
			$data = array("WARNING_DATA" => "Модуль highloadblock не установлен.");
			return $data;
			
		}
		
		$entity_data_class = $this->getEntityDataClass();

		$rsData = $entity_data_class::getList(array(
		   "select" => array("*"),
		   "filter" => array("UF_IP"=> $ip),
		   "limit" => 1,
		));

		return $rsData->Fetch();
		
	}
	
	
	/**
	 * проверить наличие данных о ip 
	 * в базе данных 
	 *
	 * @param $dataResponse jsonObject
	 * @return void OR string
	*/
	
	private function addDataInHlBlock($data_block)
	{
			
		if(!Loader::includeModule("highloadblock")) {
			
			$data = array("WARNING_DATA" => "Модуль highloadblock не установлен.");
			return $data;
			
		}

		$entity_data_class = $this->getEntityDataClass();
  
	    $result = $entity_data_class::add($data_block);
	   
	    if (!$result->isSuccess()) {
			
			$data = array("WARNING_DATA" => "Произошла ошибка, данные не добавлены.");
			return $data;
			
        } 
	   
	} 


	/**
	 * запрос данных на удаленном сервере 
	 *
	 * @param $ip string
	 * @return array
	*/

	private function getRemoteIpData($ip) 
	{
	
		$data_for_block = array();
		$uri = new Uri("http://api.ipstack.com/".$ip);
		$uri->addParams(array("access_key" => self::API_KEY));
		
		$httpClient = new HttpClient();
		$jsonData = $httpClient->get($uri);
		$errors = $httpClient->getError();
		
		if ((!$jsonData || !empty($errors)) || (!$jsonData && !empty($errors))) {
			
          $strError = "";
		  
          foreach ($errors as $errorCode => $errMes) {
          
			$strError .= $errorCode . ": " . $errMes;
         
		 }
			$jsonDataError = "При выполнение запроса произошла ошибка - ".$strError;
			$data = array("WARNING_DATA" => $jsonDataError);
			$this->sendInfoEmail($jsonDataError);
			return $data;
			
		}
		
		if ($jsonData) {
			
			$dataResponse = json_decode($jsonData);
			$checkResponseRemoteServer = $this->checkResponseRemoteServer($dataResponse);
			
			if($checkResponseRemoteServer) {
				
					$data_block = array(
						"UF_IP"=>$dataResponse->ip,
						"UF_COUNTRY"=>$dataResponse->country_name,
						"UF_CITY"=>$dataResponse->city,
						"UF_CONTINENT"=>$dataResponse->continent_name
					);
				
				$this->addDataInHlBlock($data_block);		
				
				return $data_block;
				
			} else {
				
				$jsonDataError = "С удаленного сервера не приходит ответ, проверьте корректность авторизационных данных.";
				$this->sendInfoEmail($jsonDataError);
				$data = array("WARNING_DATA" => $jsonDataError);
				return $data;
				
			}
			
		}

		

	}
	
	
	private function checkResponseRemoteServer($dataResponse)
	{
		
		if(($dataResponse->ip !== false && $dataResponse->ip !== "") && ($dataResponse->country_name !== false && $dataResponse->country_name !== "")) {
			
			return true;
			
		} else {
			
			return false;
			
		}
		
	} 
	
	
	
	/**
	 * вернуть класс данных hl блока
	 *
	 *
	 * @return object
	*/
	
	private function getEntityDataClass() 
	{
		
		$hlblock = $this->checkExistsHlBlock();
			
		$hlClassName = HL\HighloadBlockTable::compileEntity($hlblock);
		$dataClass = $hlClassName->getDataClass();
					
		return  $dataClass;		
		
	}
	
	
	/**
	 * проверить наличие hl блока
	 *
	 *
	 * @return object OR boolean
	*/
	
	private function checkExistsHlBlock()
	{
		
		try
		{
			
			$hlblock = HL\HighloadBlockTable::getList([
				'filter' => ['=NAME' => self::HL_BLOCK_NAME]
			])->fetch();
			
			return $hlblock;
			
		} catch (SystemException $e) {
			
			ShowError($e->getMessage());
			
		}
				
	}
	
	/**
	 * вернуть адрес строки вызова компонента
	 *
	 *
	 * @return string
	*/
	
	public function getCurrentPageUrl()
	{
		
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		
		$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
		$uri_array = (string) $uri->getUri();
		$return_str = SITE_SERVER_NAME.$uri_array;
		
		return $return_str;
		
	}
	
	public function onPrepareComponentParams($arParams) 
	{
		
		$this->arResult["EMAIL_FOR_SEND_INFO"] = $arParams["EMAIL_FOR_INFO"];	
		$this->arResult["PAGE_URL_FOR_SEND_INFO"] = $this->getCurrentPageUrl();
		
	}
	
	
	
	public function executeComponent()
	{
				
		if(!$this->checkExistsHlBlock()) {
			
			echo "Сделайте миграцию данных highload блока GeoIpData.";
			
		} else {
			
			$this->IncludeComponentTemplate();	
			
		}

	}
	
	/**
	 * отправить сообщение
	 *
	 *
	 * @return void
	*/
	
	private function sendInfoEmail($textMessage) {
		
		try {
		
			if(($this->emailForSendInfo !== "" && $this->pageUrl !== "") && $this->validateEmailAddress($this->emailForSendInfo)) {
					
				$eventName = "GEOIP_COMPONENT_WARNING_INFO";
			
				$arFields = array(
				  'EMAIL' => $this->emailForSendInfo,
				  'URL_PAGE' => $this->pageUrl,
				  'ADDITIONAL_TEXT' => $textMessage
				);
				 
				$arrSite = 's1';
				 
				$event = new CEvent;
				
				$event->Send($eventName, $arrSite, $arFields, "N");	
				
			}
		
		} catch (SystemException $e)	{
			
			ShowError($e->getMessage());
			
		}
		
	}
	
}
