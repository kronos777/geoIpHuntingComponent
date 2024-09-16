<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCss('/bitrix/css/main/bootstrap.css');
?>
<div class="row justify-content-center" style="margin-top: 50px;">
	<div class="col-md-9">
		<div class="form-group col-md-12">
			<input type="text" placeholder="Введите IP адрес" class="form-control" id="ipAddress">
			<div id="buttonSend" class="btn btn-primary col-md-12" style="margin-top: 25px;">Проверить</div>  
		</div>
		<div class="col-md-12" style="margin-top: 15px; visibility: hidden;" id="showDataBlock"> 
			<table class="table">
			  <thead class="thead-dark">
				<tr>
				  <th scope="col">IP адресс</th>
				  <th scope="col">Континент</th>
				  <th scope="col">Страна</th>
				  <th scope="col">Город</th>
				</tr>
			  </thead>
			  <tbody>
				<tr>
				  <th id="resultDataIp"> </th>
				  <td id="resultDataContinent"> </td>
				  <td id="resultDataCountry"> </td>
				  <td id="resultDataCity"> </td>
				</tr>
			  </tbody>
			</table>
		  </div>
		  
		<div class="alert alert-danger form-group" role="alert" id="alertMessageContainer" style="margin-top: 15px; visibility: hidden; float: left; width: 100%;">
		</div> 
	  </div>
	</div>
<script>
	BX.ready(function(){
		
			const showDataBlock = BX('showDataBlock');
			const ipData = BX('ipAddress');
			const buttonSend = BX('buttonSend');
			const resultDataIp = BX('resultDataIp');
			const resultDataContinent = BX('resultDataContinent');
			const resultDataCountry = BX('resultDataCountry');
			const resultDataCity = BX('resultDataCity');
			const alertMessage = BX('alertMessageContainer');
			
			BX.bind(buttonSend, 'click', () => {
				var request = BX.ajax.runComponentAction('geoip.hunting', 'data', {
					mode: 'class',
					data: {
						ipAddress: ipData.value,
						emailForSendInfo: '<? echo $arResult["EMAIL_FOR_SEND_INFO"]; ?>',
						pageUrl: '<? echo $arResult["PAGE_URL_FOR_SEND_INFO"]; ?>',
						sessid: BX.message('bitrix_sessid')
					}
				});

				request.then(function (response) {
					
					if(response.data.WARNING_DATA != "" && response.data.WARNING_DATA != undefined) {
						alertMessage.innerText = response.data.WARNING_DATA;
						alertMessage.style.visibility = 'visible';
					} else {
						alertMessage.innerText = "";
						alertMessage.style.visibility = 'hidden';
					}
					
					var ip = response.data.UF_IP;
					var continent = response.data.UF_CONTINENT;
					var country = response.data.UF_COUNTRY;
					var city = response.data.UF_CITY;
										
					if(response.data.UF_IP != "" && response.data.UF_IP != undefined) {
						showDataBlock.style.visibility = 'visible';
						
						resultDataIp.innerText = ip;
						resultDataContinent.innerText = continent;
						resultDataCountry.innerText = country;
						resultDataCity.innerText = city;	
					} else {
						showDataBlock.style.visibility = 'hidden';
					}
					
				});
			});		
	});
</script>