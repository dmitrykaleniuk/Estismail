<?php

	/**
	 *
	 * 1 - WebMoney (WMZ)
	 * 2 - WalletOne
	 * 3 - Yandex.money
	 * 4 - LiqPay
	 * 5 - Payeer
	 * 6 - WebMoney (WMR)
	 * 7 - WebMoney (WMU)
	 */
	class PaymentsController extends AppController {

		public $uses = array(
				'Good',
				'Currency',
				'PaymentSystem',
				'PaymentSystemField',
				'PaymentSystemCurrency',
				'UsersPaymentSystem',
				'UserPaymentSystemField'
			);

		public $components = array(
				'Webmoney',
				'Yandex',
				//'WalletOne',
				//'LiqPay',
				//'Payeer'
			);

		public function index() {

			$payment_systems = $this->PaymentSystemCurrency->find('all', array(
				'conditions' => array(
					'PaymentSystem.status' => 1
				),
			    'order' => array('PaymentSystem.order')
			));
			$this->set('payment_systems', $payment_systems);

			$this->UsersPaymentSystem->unbindModel(array(
				'belongsTo' => array('PaymentSystem')
			));
			$user_payment_system = $this->UsersPaymentSystem->find('all', array(
				'conditions' => array(
					'UsersPaymentSystem.user_id' => $this->user_data['User']['id']
				)
			));
			$user_payment_system = Set::combine($user_payment_system, '{n}.UsersPaymentSystem.payment_system_id', '{n}');
			$this->set('user_payment_system', $user_payment_system);

		}

		public function statustoggle($id) {

			$id *= 1;
			$this->autoRender = false;

			$payment_system = $this->PaymentSystem->find('first', array(
					'conditions' => array(
						'PaymentSystem.id'     => $id,
						'PaymentSystem.status' => 1
					)
				)
			);

			if (empty($payment_system)) {
				$this->pushNotify(__('Платёжная система в данный момент не активна.'), 'error');
				return false;
			}

			$user_payment_system = $this->UsersPaymentSystem->find('first', array(
					'conditions' => array(
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id'],
						'UsersPaymentSystem.payment_system_id' => $id
					),
				)
			);
			if (empty($user_payment_system)) {
				$this->pushNotify(__('Данная платёжная система ещё не подключена.'), 'error');
				return false;
			}

			// if user disables payment system - don't need any more checks
			if ($user_payment_system['UsersPaymentSystem']['status'] == 1) { // current state - active
				$this->UsersPaymentSystem->id = $user_payment_system['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->saveField('status', 0);
				$this->UsersPaymentSystem->id = null;
				$this->pushNotify(__('Платёжная система деактивирована'));
				return false;
			}

			$check = false;
			switch ($id) {
				case 1: { // WMZ
					$user_payment_system_field = $this->UserPaymentSystemField->find('first', array(
						'conditions' => array(
							'UserPaymentSystemField.user_id' => $this->user_data['User']['id'],
							'UserPaymentSystemField.payment_system_field_id' => 1,
							'UserPaymentSystemField.users_payment_system_id' => $user_payment_system['UsersPaymentSystem']['id']
						)
					));
					$info = array(
						'clientId' => $user_payment_system_field['UserPaymentSystemField']['value']
					);
					$check = $this->Webmoney->check($info);
				} break;
				case 3: { // Yandex.money
					$user_payment_system_field = $this->UserPaymentSystemField->find('first', array(
						'conditions' => array(
							'UserPaymentSystemField.user_id' => $this->user_data['User']['id'],
							'UserPaymentSystemField.payment_system_field_id' => 5,
							'UserPaymentSystemField.users_payment_system_id' => $user_payment_system['UsersPaymentSystem']['id']
						)
					));
					$info = array(
						'clientId' => $user_payment_system_field['UserPaymentSystemField']['value']
					);
					$check = $this->Yandex->check($info);
				} break;
				case 6: { // WMR
					$user_payment_system_field = $this->UserPaymentSystemField->find('first', array(
						'conditions' => array(
							'UserPaymentSystemField.user_id' => $this->user_data['User']['id'],
							'UserPaymentSystemField.payment_system_field_id' => 11,
							'UserPaymentSystemField.users_payment_system_id' => $user_payment_system['UsersPaymentSystem']['id']
						)
					));
					$info = array(
						'clientId' => $user_payment_system_field['UserPaymentSystemField']['value']
					);
					$check = $this->Webmoney->check($info);
				} break;
				case 7: { // WMU
					$user_payment_system_field = $this->UserPaymentSystemField->find('first', array(
						'conditions' => array(
							'UserPaymentSystemField.user_id' => $this->user_data['User']['id'],
							'UserPaymentSystemField.payment_system_field_id' => 13,
							'UserPaymentSystemField.users_payment_system_id' => $user_payment_system['UsersPaymentSystem']['id']
						)
					));
					$info = array(
						'clientId' => $user_payment_system_field['UserPaymentSystemField']['value']
					);
					$check = $this->Webmoney->check($info);
				} break;

				default :
					$this->pushNotify(__('Платёжная система в данный момент не активна.'), 'error');
					return false;
			}

			if (!$check) {
				$this->pushNotify(__('Плетёжная система не прошла проверку. Возможно Вы ввели неправильные данные или Ваш кошелек/магазин еще не может принимать платежи.'
				), 'error');
				return false;
			}

			$this->UsersPaymentSystem->id = $user_payment_system['UsersPaymentSystem']['id'];
			$this->UsersPaymentSystem->saveField('status', 1);
			$this->UsersPaymentSystem->id = null;
			$this->pushNotify(__('Платёжная система активирована'));
			return 1;

		}

		public function add($id) {

			//Configure::write('debug',2);
			$id *= 1;

			// check that payment system exists
			$payment_system = $this->PaymentSystem->find('first', array(
					'conditions' => array(
						'PaymentSystem.id'     => $id,
						'PaymentSystem.status' => 1
					)
				)
			);
			if (empty($payment_system)) {
				$this->pushNotify(__('Платёжная система в данный момент не активна. '), 'error');
				$this->redirect($this->referer());
			}

			$users_payment_system = $this->UsersPaymentSystem->find('first', array(
					'conditions' => array(
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id'],
						'UsersPaymentSystem.payment_system_id' => $id
					)
				)
			);
			if (!empty($users_payment_system)) {
				$this->pushNotify(__('Данная платёжная система уже подключена. '), 'error');
				$this->redirect($this->referer());
			}

			$this->set('payment_system', $payment_system);
			$this->set('users_payment_system', $users_payment_system);

			if (!empty($this->request->data)) {
				$result = false;
				switch ($id) {
					case 1:
						//webmoney WMZ
						$result = $this->_savepaymentdata1($this->request->data);
						break;
					//case 2:
					//	//walletone
					//	$result = $this->_savepaymentdata2($this->request->data);
					//	break;
					case 3:
						//yandex.money
						$result = $this->_savepaymentdata3($this->request->data);
						break;
					//case 4:
					//	//liqpay
					//	$result = $this->_savepaymentdata4($this->request->data);
					//	break;
					//case 5:
					//	//payeer
					//	$result = $this->_savepaymentdata5($this->request->data);
					//	break;
					case 6:
						//webmoney WMR
						$result = $this->_savepaymentdata6($this->request->data);
						break;
					case 7:
						//webmoney WMU
						$result = $this->_savepaymentdata7($this->request->data);
						break;
					default :
						$this->redirect($this->referer());
				}
				if (!$result) {
					return false;
				}

				$this->pushNotify(__('Информация сохранена.'));
				$this->redirect('/payments');

			}

			$this->render($id.'/add');
		}

		public function edit($id) {

//        Configure::write('debug',2);
			$id *= 1;

			$payment_system_exist = $this->PaymentSystem->find('first',
				array(
					'conditions' => array(
						'PaymentSystem.id'     => $id,
						'PaymentSystem.status' => 1
					),
				)
			);

			if (empty($payment_system_exist)) {
				$this->pushNotify(__('Платёжная система в данный момент не активна. '), 'error');
				$this->redirect($this->referer());
			}

			$user_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id'],
						'UsersPaymentSystem.payment_system_id' => $id
					),
				)
			);
			if (empty($user_payment_system_data)) {
				$this->pushNotify(__('Данная платёжная система ещё не подключена. '), 'error');
				$this->redirect($this->referer());
			}

			if (!empty($this->request->data)) {
				$result = false;
				switch ($id) {
					case 1:
						$result = $this->_savepaymentdata1($this->request->data);
						break;
					//case 2:
					//	$result = $this->_savepaymentdata2($this->request->data);
					//	break;
					case 3:
						$result = $this->_savepaymentdata3($this->request->data);
						break;
					//case 4:
					//	$result = $this->_savepaymentdata4($this->request->data);
					//	break;
					//case 5:
					//	$result = $this->_savepaymentdata5($this->request->data);
					//	break;
					case 6:
						//webmoney WMR
						$result = $this->_savepaymentdata6($this->request->data);
						break;
					case 7:
						//webmoney WMU
						$result = $this->_savepaymentdata7($this->request->data);
						break;
					default :
						$this->redirect($this->referer());
				}
				if ($result) {
					$this->redirect('/payments');
				} else {
					$this->redirect($this->referer());
				}
			} else {
				$user_payment_system_fields = $this->UserPaymentSystemField->find('all',
					array(
						'conditions' => array(
							'PaymentSystemField.payment_system_id' => $id,
							'UserPaymentSystemField.user_id'       => $this->user_data['User']['id']
						),
						'order'      => array('UserPaymentSystemField.id' => 'asc')
					)
				);
				debug($user_payment_system_fields);
				$data['UserPaymentSystemField'] = Set::combine($user_payment_system_fields,
					'{n}.UserPaymentSystemField.payment_system_field_id',
					'{n}.UserPaymentSystemField.value'
				);
				$this->request->data = $data;

			}
			$this->render($id.'/edit');
		}

		//WebMoney WMZ
		private function _savepaymentdata1($data) {

			//Configure::write('debug',2);

			if ((preg_match('/^Z\d{12}$/', $data['UserPaymentSystemField']['1'])) != 1) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (empty($data['UserPaymentSystemField']['2'])) {
				$this->pushNotify(__('Секретный номер не может быть пустым.'), 'error');

				return false;
			}
			$data['UserPaymentSystemField']['2'] = mb_substr($data['UserPaymentSystemField']['2'], 0, 100);

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 1,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);
			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 1
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(1, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(2, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;

		}

		//WalletOne
		private function _savepaymentdata2($data) {

			$data['UserPaymentSystemField']['1'] *= 1;
			if (strlen((string)$data['UserPaymentSystemField']['1']) !== 12) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (strlen($data['UserPaymentSystemField']['2']) !== 54) {
				$this->pushNotify(__('Введите пожалуйста секретный ключ.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 2,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);

			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 2
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(3, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(4, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;
		}

		//Yandex.money
		private function _savepaymentdata3($data) {

			$data['UserPaymentSystemField']['1'] *= 1;
			if (strlen((string)$data['UserPaymentSystemField']['1']) !== 15) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (strlen($data['UserPaymentSystemField']['2']) !== 24) {
				$this->pushNotify(__('Введите пожалуйста секретный ключ.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 3,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);

			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 3
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(5, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(6, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;
		}

		//LiqPay
		private function _savepaymentdata4($data) {

			if ((preg_match('/^i\d{11}$/', $data['UserPaymentSystemField']['1'])) != 1) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (strlen($data['UserPaymentSystemField']['2']) !== 40) {
				$this->pushNotify(__('Введите пожалуйста секретный ключ.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 4,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);

			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 4
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(7, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(8, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;
		}

		//Payeer
		private function _savepaymentdata5($data) {

			$data['UserPaymentSystemField']['1'] *= 1;
			if (strlen((string)$data['UserPaymentSystemField']['1']) !== 9) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (strlen($data['UserPaymentSystemField']['2']) !== 40) {
				$this->pushNotify(__('Введите пожалуйста секретный ключ.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 5,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);

			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 5
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(9, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(10, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;
		}

		//WebMoney WMR
		private function _savepaymentdata6($data) {

			if ((preg_match('/^R\d{12}$/', $data['UserPaymentSystemField']['1'])) != 1) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (empty($data['UserPaymentSystemField']['2'])) {
				$this->pushNotify(__('Секретный номер не может быть пустым.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 1,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);
			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 1
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(11, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(12, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;

		}

		//WebMoney WMU
		private function _savepaymentdata7($data) {

			if ((preg_match('/^U\d{12}$/', $data['UserPaymentSystemField']['1'])) != 1) {
				$this->pushNotify(__('Введите правильный номер кошелька. '), 'error');

				return false;

			}

			if (empty($data['UserPaymentSystemField']['2'])) {
				$this->pushNotify(__('Секретный номер не может быть пустым.'), 'error');

				return false;
			}

			$users_payment_system_data = $this->UsersPaymentSystem->find('first',
				array(
					'conditions' => array(
						'UsersPaymentSystem.payment_system_id' => 1,
						'UsersPaymentSystem.user_id'           => $this->user_data['User']['id']
					)
				)
			);
			if (empty($users_payment_system_data)) {
				$this->UsersPaymentSystem->save(array(
						'user_id'           => $this->user_data['User']['id'],
						'payment_system_id' => 1
					)
				);
				$users_payment_system_id = $this->UsersPaymentSystem->getLastInsertID();
			} else {
				$users_payment_system_id = $users_payment_system_data['UsersPaymentSystem']['id'];
				$this->UsersPaymentSystem->id = $users_payment_system_id;
				$this->UsersPaymentSystem->saveField('status', '0');
			}

			$this->_savepaymentsystemdata(13, $users_payment_system_id, $data['UserPaymentSystemField'][1]);
			$this->_savepaymentsystemdata(14, $users_payment_system_id, $data['UserPaymentSystemField'][2]);

			return true;

		}

		private function _savepaymentsystemdata(
			$payment_system_fields_id, $users_payment_system_id, $value, $user_id = null
		) {

			//Configure::write('debug',2);
			$payment_system_fields_id *= 1;
			$users_payment_system_id *= 1;

			if (!$user_id) {
				$user_id = $this->user_data['User']['id'];
			}
			// save new data

			$user_payment_system_fields_data = $this->UserPaymentSystemField->find('first',
				array(
					'conditions' => array(
						'UserPaymentSystemField.users_payment_system_id' => $users_payment_system_id,
						'UserPaymentSystemField.payment_system_field_id' => $payment_system_fields_id,
						'UserPaymentSystemField.user_id'                 => $this->user_data['User']['id']
					)
				)
			);

			if (empty($user_payment_system_fields_data)) {
				//for add
				$this->UserPaymentSystemField->id = null;
				$this->UserPaymentSystemField->save(array(
					'user_id'                 => $user_id,
					'payment_system_field_id' => $payment_system_fields_id,
					'value'                   => $value,
					'users_payment_system_id' => $users_payment_system_id
				)
				);
				$this->UserPaymentSystemField->id = null;
			} else {

				//for edit
				$this->UserPaymentSystemField->id = null;
				$this->UserPaymentSystemField->save(array(
					'id'                      => $user_payment_system_fields_data['UserPaymentSystemField']['id'],
					'user_id'                 => $user_id,
					'payment_system_field_id' => $payment_system_fields_id,
					'value'                   => $value,
					'users_payment_system_id' => $users_payment_system_id
				)
				);
				$this->UserPaymentSystemField->id = null;
			}
		}

	}
