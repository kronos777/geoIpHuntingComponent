Сценарий работы компонента:

Пользователь вводит валидный IP, отправляется запрос в HL блок, если в HL блоке присутствует запись с данным IP, то данные отображаются из базы, если в базе нет нужного ip, то запрос отправляется на один из сервисов, пользователю показываются данные из сервиса и записываются в базу.

Установка компонента:

Скопируйте  содержимое архива в корень сайта битрикс, с помощью инструмента https://marketplace.1c-bitrix.ru/solutions/sprint.migration/ сделайте миграцию данных (файлы миграций лежат в local/php_interface/migrations). Создайте почтовый шаблон отправки для события GEOIP_COMPONENT_WARNING_INFO.
В случае если данные с удаленного не поступают в классе компонента необходимо заменить значение константы api_key, значение которой Вы можете получить на сайте ipstack.com.
Вызовите компонент на странице:
$APPLICATION->IncludeComponent(
	"geoip.hunting",
	"",
	array(
		"EMAIL_FOR_INFO" => "YOUR_EMAIL"
	)
); 


