<?php
/**
 * Базовый класс для обмена данными с АПИ Measoft
 *
 * Created by Measoft 2019
 */

use Joomla\CMS\Factory as Factory;

defined('_JEXEC') or die;

class MeasoftCourier
{
	/**
	 * Типы оплаты
	 */
	const PAYMENT_TYPE_CASH = 'CASH';
	const PAYMENT_TYPE_CARD = 'CARD';
	const PAYMENT_TYPE_NONE = 'NO';
	const PAYMENT_TYPE_OTHER = 'OTHER';

	/**
	 * Возвратная корреспонденция
	 */
	const RETURN_YES = 'YES';
	const RETURN_NO = 'NO';

	/**
	 * Получатель оплачивает и доставку
	 */
	const RECEIVER_PAYS_YES = 'YES';
	const RECEIVER_PAYS_NO = 'NO';

	/**
	 * Забор
	 */
	const PICKUP_YES = 'YES';
	const PICKUP_NO = 'NO';

	/**
	 * Разрешить частитчную доставку
	 */
	const ACCEPT_PARTIALLY_YES = 'YES';
	const ACCEPT_PARTIALLY_NO = 'NO';
	private static $instance;
	/**
	 * Разрешить исключения в случае ошибок
	 * @var bool
	 */
	public $enableExceptions = true;
	/**
	 * Версия класса
	 * @var string
	 */
	protected $version = '2.0.0';
	/**
	 * Учетные данные для авторизации в АПИ
	 * @var string
	 */
	protected $login = null, $password = null, $extracode = null;
	/**
	 * Ссылка на АПИ
	 * @var string
	 */
	protected $url = 'https://home.courierexe.ru/api/';
	/**
	 * Лог ответов от АПИ
	 * @var array
	 */
	protected $responses = array();
	/**
	 * Лог ошибок от АПИ
	 * @var array
	 */
	protected $errors = array();

	private function __construct($login, $password, $extracode)
	{
		$this->login     = $login;
		$this->password  = $password;
		$this->extracode = $extracode;

		$language = Factory::getLanguage();
		$language->load('lib_measoft', JPATH_SITE, $language->getTag(), true);
	}

	public static function getInstance()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('params'))
			->from($db->quoteName('#__jshopping_shipping_ext_calc'))
			->where($db->quoteName('alias') . ' = ' . $db->quote('sm_courierexe'));
		$config = unserialize($db->setQuery($query)->loadResult());

		// Check is $_instance has been set
		if (!isset(self::$instance))
		{
			// Creates sets object to instance
			self::$instance = new MeasoftCourier($config['api_user_name'], $config['api_user_pw'],
				$config['api_user_extra']);
		}

		// Returns the instance
		return self::$instance;
	}

	/**
	 * Расчет стоимости доставки согласно тарифам КС
	 *
	 * @param   array  $params     - параметры для расчета
	 * @param   bool   $priceOnly  - возврат только стоимости
	 *
	 * @return integer|array|false - возвращает массив или число стоимости
	 */
	public function calculate(array $params, $priceOnly = false)
	{
		if (!isset($params['townto']) || !$params['townto'])
		{
			return $this->error('Не указан город назначения');
		}

		$data = array(
			'calc' => array(
				'attributes' => array(
					'mass'    => 0.1,
					'service' => 1,
				)
			)
		);
		foreach ($params as $param => $val)
		{
			//Исключение для получения расчета всех видов срочности
			if ($param == 'service' && $val === null)
			{
				unset($data['calc']['attributes'][$param]);
			}
			else
			{
				$data['calc']['attributes'][$param] = $val;
			}
		}

		$cost    = array();
		$results = $this->sendRequest($this->makeXML('calculator', $data));
		if (is_object($results) || is_array($results))
		{
			foreach ($results as $result)
			{
				$cost[(int) $result->service] = array(
					'price' => (double) $result->price,
					'days'  => array(
						'min' => (int) $result->mindeliverydays,
						'max' => (int) $result->maxdeliverydays,
					),
				);
			}
		}
		else
		{
			return $results;
		}

		if ($priceOnly)
		{
			$cost = array_shift($cost);

			return $cost['price'];
		}

		return $cost;
	}

	/**
	 * Генерация ошибки и запись ее в историю
	 *
	 * @param        $message  - сообщение об ошибке
	 * @param   int  $code     - код ошибки
	 *
	 * @return bool
	 * @throws MeasoftCourier_Exception
	 */
	protected function error($message, $code = 0)
	{
		$this->errors[] = $message;
		if ($this->enableExceptions)
		{
			throw new MeasoftCourier_Exception($message, $code);
		}

		return false;
	}

	/**
	 * Отправка запроса к АПИ
	 *
	 * @param $data  - XML с запросом
	 *
	 * @return SimpleXMLElement|false - XML ответ от сервера или false в случае ошибки
	 */
	protected function sendRequest($data)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml; charset=utf-8'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$contents = curl_exec($ch);
		$headers  = curl_getinfo($ch);
		curl_close($ch);

		if ($headers['http_code'] != 200 || !$contents)
		{
			return $this->error('Ошибка сервиса');
		}

		$this->responses[] = $contents;
//print_r($contents);
		$xml = simplexml_load_string($contents);

		return $this->checkResponse($xml) ? $xml : false;
	}

	/**
	 * Проверка ответа АПИ на ошибки
	 *
	 * @param $xml  - ответ от сервера
	 *
	 * @return bool - результат проверки
	 */
	protected function checkResponse($xml)
	{
		if (!($xml instanceof SimpleXMLElement))
		{
			$this->error('Ошибка сервиса');
		}

		$attr = $xml->attributes();
		if (isset($attr['error']))
		{
			return $this->error($this->getErrorMessage((int) $attr['error']), (int) $attr['error']);
		}

		return true;
	}

	/**
	 * Получение текстового сообщение об ошибке по ее коду от АПИ
	 *
	 * @param $code  - код ошибки
	 *
	 * @return string - текст ошибки
	 */
	protected function getErrorMessage($code)
	{
		$errors = array(
			0  => 'OK',
			1  => 'Неверный xml',
			2  => 'Широта не указана',
			3  => 'Долгота не указана',
			4  => 'Дата и время запроса не указаны',
			5  => 'Точность не указана',
			6  => 'Идентификатор телефона не указан',
			7  => 'Идентификатор телефона не найден',
			8  => 'Неверная широта',
			9  => 'Неверная долгота',
			10 => 'Неверная точность',
			11 => 'Заказы не найдены',
			12 => 'Неверные дата и время запроса',
			13 => 'Ошибка mysql',
			14 => 'Неизвестная функция',

			15 => 'Тариф не найден',
			18 => 'Город отправления не указан',
			19 => 'Город назначения не указан',
			20 => 'Неверная масса',
			21 => 'Город отправления не найден',
			22 => 'Город назначения не найден',
			23 => 'Масса не указана',
			24 => 'Логин не указан',
			25 => 'Ошибка авторизации',
			26 => 'Логин уже существует',
			27 => 'Клиент уже существует',
			28 => 'Адрес не указан',
			29 => 'Более не поддерживается',
			30 => 'Настройка sip не выполнена',
			31 => 'Телефон не указан',
			32 => 'Телефон курьера не указан',
			33 => 'Ошибка соединения',
			34 => 'Неверный номер',
			35 => 'Неверный номер',
			36 => 'Ошибка определения тарифа',
			37 => 'Ошибка определения тарифа',
			38 => 'Тариф не найден',
			39 => 'Тариф не найден',
		);

		return isset($errors[$code]) ? $errors[$code] : 'Неизвестная ошибка';
	}

	/**
	 * Генерирует XML объект из массива
	 *
	 * @param          $action    - метод АПИ
	 * @param   array  $data      - данные для запроса
	 * @param   bool   $withAuth  - использовать авторизацию или нет
	 *
	 * @return string - XML строка
	 */
	protected function makeXML($action, $data = array(), $withAuth = true)
	{
		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><' . $action . '/>');
		if ($withAuth)
		{
			$auth = $xml->addChild('auth');
			$auth->addAttribute('login', $this->login);
			$auth->addAttribute('pass', $this->password);
			$auth->addAttribute('extra', $this->extracode);
		}
//print_r($data);
		if (!empty($data))
		{
			foreach ($data as $node => $value)
			{
				$this->addXMLnode($xml, $node, $value);
			}
		}

//print_r($xml);
		return $xml->asXML();
	}

	protected function addXMLnode(SimpleXMLElement &$xml, $name, $data)
	{
		if($xml->getName() == 'items'){
			$name = 'item';
		}
		$node = $xml->addChild($name,
			is_array($data) ? (isset($data['value']) && is_scalar($data['value']) ? $data['value'] : null) : $data);
		if (isset($data['attributes']))
		{
			foreach ($data['attributes'] as $name => $value)
			{
				$node->addAttribute($name, $value);
			}
		}
		elseif (is_array($data))
		{
			foreach ($data as $name => $value)
			{
				if (!is_array($value) || $value !== array_values($value))
				{
					$this->addXMLnode($node, $name, $value);
				}
				else
				{
					foreach ($value as $item)
					{
						$this->addXMLnode($node, $name, $item);
					}
				}
			}
		}

		if (isset($data['value']) && is_array($data['value']))
		{
			foreach ($data['value'] as $name => $value)
			{
				$this->addXMLnode($node, $name, $value);
			}
		}

		return true;
	}

	/**
	 * @param   array  $order  - информация о заказе
	 * @param   array  $items  - товары в заказе
	 *
	 * @return string|false - номер заказа или false в случае ошибки
	 */
	public function orderCreate(array $order = array(), array $items = array())
	{
		if (empty($order) || empty($items))
		{
			return $this->error('Пустой массив заказа');
		}

		$order_items = array();
		$inshprice   = $weight = 0;
		if (!empty($items))
		{
			foreach ($items as $item)
			{
				if (!in_array($item['name'], array('Доставка', 'Скидка', 'Наценка')))
				{
					//Расчёт стоимости всех товаров
					$inshprice += $item['retprice'] * $item['quantity'];

					//Расчёт массы всех товаров
					$weight += $item['mass'] * $item['quantity'];
				}

				$order_item = array(
					'attributes' => array(
						'quantity' => $item['quantity'] ?: $item['quantity'],
						'mass'     => $item['mass'] ?: 0.1,
						'retprice' => $item['retprice'],
						'barcode'  => strip_tags($item['barcode']),
						'type'     => 1,
					),
					'value'      => $item['name'],
				);
				//Если передан код
				if (isset($item['extcode']) && $item['extcode'])
				{
					$order_item['attributes']['extcode'] = strip_tags($item['extcode']);
				}
				//Если передан артикул
				if (isset($item['article']) && $item['article'])
				{
					$order_item['attributes']['article'] = strip_tags($item['article']);
				}
				//Если передана ставка НДС
				if (isset($item['VATrate']) && $item['VATrate'])
				{
					$order_item['attributes']['VATrate'] = strip_tags($item['VATrate']);
				}
				//Если передан тип вложения
				if (isset($item['type']) && (int) $item['type'])
				{
					$order_item['attributes']['type'] = strip_tags($item['type']);
				}

				$order_items[] = $order_item;
			}
		}

		$data = array();
		foreach ($order as $param => $value)
		{
			switch ($param)
			{
				case 'sender':
					$data[$param] = array(
						'attributes' => array(
							'type'           => 4,
							'module'         => $value['module'],
							'module_version' => $value['module_version'],
							'cms_version'    => $value['cms_version'],
						)
					);
					break;

				case 'company':
				case 'person':
				case 'phone':
				case 'zipcode':
				case 'town':
				case 'address':
				case 'date':
				case 'time_min':
				case 'time_max':
					$data['receiver'][$param] = $value;
					break;

				case 'weight':
					$data[$param] = $value ?: $weight;
					break;
				case 'inshprice':
					$data[$param] = $value ?: $inshprice;
					break;
				case 'quantity':
				case 'service':
					$data[$param] = $value ?: 1;
					break;
				case 'discount':
					$data[$param] = $value ?: 0;
					break;
				case 'paytype':
					$data[$param] = $value && in_array($value, $this->getPaymentTypes()) ?: self::PAYMENT_TYPE_CASH;
					break;
				case 'receiverpays':
					$data[$param] = $value && in_array($value,
						array(self::RECEIVER_PAYS_YES, self::RECEIVER_PAYS_NO)) ?: self::RECEIVER_PAYS_NO;
					break;
				case 'return':
					$data[$param] = $value && in_array($value,
						array(self::RETURN_YES, self::RETURN_NO)) ?: self::RETURN_NO;
					break;

				default:
					$data[$param] = $value;
			}
		}
		$data['items']['item'] = $order_items;

		$result = $this->sendRequest($this->makeXML('neworder', [
			'order' => [
				'attributes' => ['orderno' => $order['orderno']],
				'value'      => $data,
			],
			'items' => $order_items
		]));
		if (isset($result->createorder[0]['orderno']))
		{
			return (string) $result->createorder[0]['orderno'];
		}

		return false;
	}

	/**
	 * Возвращает массив доступных типов оплаты
	 *
	 * @return array
	 */
	public function getPaymentTypes()
	{
		return array(
			self::PAYMENT_TYPE_CASH,
			self::PAYMENT_TYPE_CARD,
			self::PAYMENT_TYPE_NONE,
			self::PAYMENT_TYPE_OTHER,
		);
	}

	/**
	 * Получение статуса заказа по его номеру
	 *
	 * @param   string  $number  - номер заказа
	 *
	 * @return string|false - текстовый статус заказа или false в случае ошибки
	 */
	public function orderStatus($number)
	{
		$statuses = array(
			'NEW'              => 'Новый',
			'PICKUP'           => 'Забран у отправителя',
			'ACCEPTED'         => 'Получен складом',
			'INVENTORY'        => 'Инвентаризация',
			'DEPARTURING'      => 'Планируется отправка',
			'DEPARTURE'        => 'Отправлено со склада',
			'DELIVERY'         => 'Выдан курьеру на доставку',
			'COURIERDELIVERED' => 'Доставлен (предварительно)',
			'COMPLETE'         => 'Доставлен',
			'PARTIALLY'        => 'Доставлен частично',
			'COURIERRETURN'    => 'Курьер вернул на склад',
			'CANCELED'         => 'Не доставлен (Возврат/Отмена)',
			'RETURNING'        => 'Планируется возврат',
			'RETURNED'         => 'Возвращен',
			'CONFIRM'          => 'Согласована доставка',
			'DATECHANGE'       => 'Перенос',
			'NEWPICKUP'        => 'Создан забор',
			'UNCONFIRM'        => 'Не удалось согласовать доставку',
			'PICKUPREADY'      => 'Готов к выдаче',
			'AWAITING_SYNC'    => 'Ожидание синхронизации',
		);

		if (!$number)
		{
			return $this->error('Не указан номер заказа');
		}

		$result = $this->sendRequest($this->makeXML('statusreq', ['orderno' => $number]));
		$attrs  = $result->attributes();
		if ($attrs['count'] > 0)
		{
			$status = trim((string) $result->order[0]->status);

			return isset($statuses[$status]) ? $statuses[$status] : $status;
		}
		else
		{
			return $this->error('Заказ №' . $number . ' не найден');
		}
	}

	/**
	 * Получение списка городов по заданному критерию
	 *
	 * @param   array  $conditions  - условия для поиска
	 * @param   array  $limit       - ограничения результатов
	 * @param   array  $search      - поиск по кодам, conditions и limit игнорируются
	 *
	 * @return array|false - найденные города или false в случае ошибки
	 */
	public function citiesList(array $conditions = array(), array $limit = array(), array $search = array())
	{
		if (empty($conditions) && empty($limit) && empty($search))
		{
			return $this->error('Не указаны параметры для поиска');
		}

		//Ограничиваем кол-во результатов, если не указано другое
		$request = array('limit' => array('limitcount' => 10));

		if (!empty($conditions))
		{
			foreach ($conditions as $condition => $value)
			{
				$request['conditions'][$condition] = $value;
			}
		}

		if (!empty($limit))
		{
			foreach ($limit as $option => $value)
			{
				switch ($option)
				{
					case 'countall':
						$request['limit'][$option] = $value && strtoupper($value) != 'NO' ? 'YES' : 'NO';
						break;
					default:
						$request['limit'][$option] = $value;
				}
			}
		}

		if (!empty($search))
		{
			foreach ($search as $field => $value)
			{
				$request['codesearch'][$field] = $value;
			}
		}

		$results = $this->sendRequest($this->makeXML('townlist', $request, false));

		if (!empty($results))
		{
			$cities = array();
			foreach ($results as $result)
			{
				$cities[(int) $result->code] = array(
					'code'      => (int) $result->code,
					'name'      => (string) $result->name,
					'fiascode'  => (string) $result->fiascode,
					'kladrcode' => (string) $result->kladrcode,
					'shortname' => (string) $result->shortname,
					'typename'  => (string) $result->typename,
					'region'    => array(
						'code' => (int) $result->city->code,
						'name' => (string) $result->city->name,
					),
				);
			}

			return $cities;
		}
		else
		{
			return $this->error('Ничего не найдено');
		}
	}

	public function __get($name)
	{
		switch ($name)
		{
			case 'error':
				$value = !empty($this->errors) ? $this->errors[count($this->errors) - 1] : null;
				break;
			case 'response':
				$value = !empty($this->responses) ? $this->responses[count($this->responses) - 1] : null;
				break;
			default:
				$value = null;
		}

		return $value;
	}

	public function servicesList()
	{
		$results = $this->sendRequest($this->makeXML('services', array()));
		if (!empty($results))
		{
			return $results->xpath('./service');
		}
		else
		{
			return array();
		}

	}

	public function servicesPvzlist($params)
	{
		$params['json'] = 'NO';

		$results = $this->sendRequest($this->makeXML('pvzlist', $params));

		if (!empty($results))
		{
			return $results->xpath('./pvz');
		}
		else
		{
			return array();
		}
	}
}

class MeasoftCourier_Exception extends Exception
{
}
