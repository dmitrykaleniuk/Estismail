<?php

class ScenariosController extends AppController
{

    public $layout = "main";
    public $uses = array(
            "Scenario",
            "UserSetting",
            "Maket",
            "Letter",
            "ScenarioStep"
        );
    public $components = array('Session');

    public function index($page = 1) {

        $shown = 10;
        if (!empty($this->request->query['p'])) {
            $shown = $this->request->query['p'] * 1;
            if ($shown > 50) {
                $shown = null;
            }
            if ($shown < 0) {
                $shown = 10;
            }
        }

        $number = $page;
        $this->set("number", $number);
        $this->set("shown", $shown);


        $scenarios = $this->Scenario->find('all', array(
            'conditions' => array(
                'Scenario.user_id' => $this->user_data['User']['id']
            ),
            'limit'      => $shown,
            'page'       => $number,
            'order'      => 'Scenario.title',
        ));
        $this->set('scenarios', $scenarios);

        $count = $this->Scenario->find('count', array(
                'conditions' => array(
                    'Scenario.user_id' => $this->user_data['User']['id']
                )
            )
        );
        $this->set('count', $count);
    }
    public function add() {

        if (!empty($this->request->data['Scenario'])) {

            $title = trim($this->request->data['Scenario']['title']);
            if(empty($title)){
                $this->Session->setFlash(__('Название не может быть пустым.'), 'flash/error');
                $this->redirect('/scenarios/add');
            }

            $user_setting = $this->UserSetting->find('first', array(
                'conditions' => array(
                    'UserSetting.user_id' => $this->user_data['User']['id'],
                    'UserSetting.user_setting_field_id' => 2,
                    'UserSetting.id' => $this->request->data['Scenario']['user_setting_id'] * 1
                )
            ));
            if (empty($user_setting)) {
                $this->Session->setFlash(__('Выбранный емейл отправителя не существует.'), 'flash/error');
                $this->redirect('/scenarios/add');
            }

            $email_data = json_decode($user_setting['UserSetting']['value'], true);
            $email_domain = explode('@', $email_data['email'])[1];

            if ($this->pro_tariff['TariffPlan']['id'] > 1 && in_array($email_domain, $this->non_pers_domains)) {
                $this->Session->setFlash(__('На данном тарифе емейл должен быть на личном домене.'), 'flash/warning');
                $this->redirect('/scenarios');
            }


            $maket = $this->Maket->find('first', array(
                'conditions' => array(
                    'Maket.id' =>  $this->request->data['Maket']['id'] * 1,
                    'Maket.user_id' => $this->user_data['User']['id']
                )
            ));
            if (empty($maket)){
                $this->Session->setFlash(__('Выбранный макет не существует.'), 'flash/error');
                $this->redirect('/scenarios/add');
            }

            $letter_title = trim($this->request->data['Letter']['title']);
            if(empty($letter_title)){
                $this->Session->setFlash(__('Название письма не может быть пустым.'), 'flash/error');
                $this->redirect('/scenarios/add');
            }

            if (!empty($maket['Maket']['unique_personal_footer'])) {
                $footer = $this->_prepare_letter_body($maket['Maket']['unique_personal_footer']);
            } else {
                $footer = $this->personal_footer($this->user_data, !$this->request->data['Maket']['old']);
            }

            $maket['Maket']['body'] = $this->_prepare_letter_body($maket['Maket']['body']);

            $letter = array(
                'title' => $letter_title,
                'user_id' => $this->user_data['User']['id'],
                'login' => $this->user_data['User']['login'],
                'name' => $this->user_data['User']['default_name'],
                'body' => $maket['Maket']['body'],
                'color' => $maket['Maket']['color'],
                'footer' => $footer
            );

            if (!$this->Letter->save($letter)) {
                $this->Session->setFlash(__('Проблемы с сохранением письма.'), 'flash/error');
                $this->redirect('/scenarios/add');
            };
            $letter_id = $this->Letter->id;

            $scenario = array(
                'user_id' => $this->user_data['User']['id'],
                'title' => $title,
                'user_setting_id' => $user_setting['UserSetting']['id'],
            );


            if (!$this->Scenario->save($scenario)) {
                $this->Letter->delete($letter_id);
                $this->Session->setFlash(__('Не удалось сохранить сценарий.'), 'flash/error');
                $this->redirect('/scenarios/add');
            };
            $scenario_id = $this->Scenario->id;

            $scenario_step = array(
                'user_id' => $this->user_data['User']['id'],
                'scenario_id' => $scenario_id,
                'scenario_action_id' => 1,
                'letter_id' => $letter_id,
                'delay' => 0,
                'fixed_time' => 0

            );
            if (!$this->ScenarioStep->save($scenario_step)) {
                $this->Letter->delete($letter_id);
                $this->Scenario->delete($scenario_id);
                $this->Session->setFlash(__('Не удалось сохранить первый шаг сценария.'), 'flash/error');
                $this->redirect('/scenarios/add');
            }

            $this->Session->setFlash(__('Сценарий создан.'), 'flash/success');
            $this->redirect('/scenarios');
            //$this->redirect('/scenarios/steps/'.$scenario_id);
        }

        $sender_emails = array();
        $default_email = 0;
        $user_emails = $this->UserSetting->find('all', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->user_data['User']['id'],
                'UserSetting.user_setting_field_id' => 2,
            )
        ));

        if (empty($user_emails)) {
            $this->Session->setFlash(__('Нет эмейлов отправителя.'), 'flash/info');
            $this->redirect($this->referer());
        }


        foreach ($user_emails as $key => $value) {
            $email_data = json_decode($value['UserSetting']['value'], true);

            $email_domain = explode('@', $email_data['email'])[1];
            if ($this->pro_tariff['TariffPlan']['id'] > 1 && in_array($email_domain, $this->non_pers_domains)) {
                continue;
            }

            if ($email_data['approved']) {
                $sender_emails[$user_emails[$key]['UserSetting']['id']] = $email_data['name'] . ' <' . $email_data['email'] . '>';
            }

            if ($email_data['default']) {
                $default_email = $user_emails[$key]['UserSetting']['id'];
            }
        }

        if (empty($sender_emails)) {

            $this->Session->setFlash(__('Нет емейлов отправителя на собственном домене.'), 'flash/info');
            $this->redirect($this->referer());
        }

        $makets = $this->Maket->find('list', array(
            'fields' => array('Maket.id', 'Maket.title'),
            'conditions' => array(
                'Maket.user_id' => $this->user_data['User']['id']
            )
        ));

        $this->set('makets', $makets);
        $this->set('sender_emails', $sender_emails);
        $this->set('default_email', $default_email);


    }

    public function edit($id) {
        $id *= 1;

        $scenario = $this->Scenario->find('first', array(
            'conditions' => array(
                'Scenario.user_id' => $this->user_data['User']['id'],
                'Scenario.id' => $id
            )
        ));

        if (empty($scenario)) {
            $this->Session->setFlash(__('Сценарий не существует.'), 'flash/error');
            $this->redirect($this->referer());
        }

        if (!empty($this->request->data)) {


            $this->request->data['Scenario']['title'] = trim($this->request->data['Scenario']['title']);
            if (empty($this->request->data['Scenario']['title'])) {
                $this->Session->setFlash(__('Название не может быть пустым.'), 'flash/error');
                $this->redirect('/scenarios/edit/' . $id);
            }

            $user_setting = $this->UserSetting->find('first', array(
                'conditions' => array(
                    'UserSetting.user_id' => $this->user_data['User']['id'],
                    'UserSetting.user_setting_field_id' => 2,
                    'UserSetting.id' => $this->request->data['Scenario']['user_setting_id'] * 1
                )
            ));
            if (empty($user_setting)) {

                $this->Session->setFlash(__('Выбранного емейла отправителя не существует.'), 'flash/error');
                $this->redirect('/scenarios/edit/' . $id);
            }

            $email_data = json_decode($user_setting['UserSetting']['value'], true);
            $email_domain = explode('@', $email_data['email'])[1];
            if ($this->pro_tariff['TariffPlan']['id'] > 1 && in_array($email_domain, $this->non_pers_domains)) {

                $this->Session->setFlash(__('Для Вашего тарифа емейл отправителя должен быть на собственном домене.'), 'flash/warning');
                $this->redirect('/scenarios/edit/' . $id);

            }

            $this->Scenario->id = $id;
            $this->Scenario->save(
                array(
                    'user_setting_id' => $this->request->data['Scenario']['user_setting_id'] * 1,
                    'title' => $this->request->data['Scenario']['title']
                )
            );
            $this->Session->setFlash(__('Сценарий изменён.'), 'flash/success');
            $this->redirect('/scenarios');
        }


        $this->set('scenario', $scenario);
        $this->request->data = $scenario;

        $sender_emails = array();
        $user_emails = $this->UserSetting->find('all', array(
            'conditions' => array(
                'UserSetting.user_id' => $this->user_data['User']['id'],
                'UserSetting.user_setting_field_id' => 2
            )
        ));
        if (empty($user_emails)) {
            $this->Session->setFlash(__('Нет емейлов отправителя. Задайте его в эмейлах отправителя.'), 'flash/info');
            $this->redirect($this->referer());
        }

        foreach ($user_emails as $key => $value) {
            $email_data = json_decode($value['UserSetting']['value'], true);

            $email_domain = explode('@', $email_data['email'])[1];
            if ($this->pro_tariff['TariffPlan']['id'] > 1 && in_array($email_domain, $this->non_pers_domains)) {
                continue;
            }
            if ($email_data['approved']) {
                $sender_emails[$user_emails[$key]['UserSetting']['id']] = $email_data['name'] . ' <' . $email_data['email'] . '>';
            }
        }

        if (empty($sender_emails)) {
            $this->Session->setFlash(__('Нет емейлов отправителя на собственном домене.'), 'flash/info');
            $this->redirect($this->referer());
        }
        $this->set('sender_emails', $sender_emails);
    }

    public function delete($id) {
        $id *= 1;
        $scenario = $this->Scenario->find('first', array(
            'conditions' => array(
                'Scenario.user_id' => $this->user_data['User']['id'],
                'Scenario.id' => $id
            )
        ));

        if (empty($scenario)) {
            $this->Session->setFlash(__('Сценарий не существует.'), 'flash/error');
            $this->redirect($this->referer());
        }

        $this->ScenarioStep->deleteAll(
            array(
            'ScenarioStep.scenario_id' => $id
        ));

        $this->Scenario->delete($id);
        $this->Session->setFlash(__('Сценарий удален.'), 'flash/success');
        $this->redirect($this->referer());
    }


    public function copy($id) {
        $this->autoRender = false;

        $id *= 1;
        $this->Scenario->bindModel(
            array(
                'hasMany' => array('ScenarioStep')
            ));
        $scenario = $this->Scenario->find('first', array(
            'conditions' => array(
                'Scenario.user_id' => $this->user_data['User']['id'],
                'Scenario.id' => $id,
            ),
        ));


        if(empty($scenario['Scenario'])) {
            $this->Session->setFlash(__('Сценарий не существует.'), 'flash/error');
            $this->redirect($this->referer());
        }
        if(empty($scenario['ScenarioStep'])) {
            $this->Session->setFlash(__('Нет шагов сценария.'), 'flash/error');
            $this->redirect($this->referer());
        }

        $scenario['Scenario']['id'] = null;
        $scenario['Scenario']['title'] = $scenario['Scenario']['title'].' (copy)';


        if(!$this->Scenario->save($scenario['Scenario'])){
            $this->Session->setFlash(__('Ошибка при копировании сценария.'), 'flash/error');
            $this->redirect($this->referer());
        }
        $scenario_copy_id = $this->Scenario->id;

        $db = ConnectionManager::getDataSource('default');
        $conf = $db->config;
        $dbconn = pg_connect("host=".$conf['host']." port=".$conf['port']." dbname=".$conf['database']
            ." user=".$conf['login']." password=".$conf['password'].""
        );

        $old = Set::combine($scenario['ScenarioStep'], '{n}.parent_id', '{n}.parent_id');
        ksort($old);
        $scenario_steps = Set::combine($scenario['ScenarioStep'], '{n}.id', '{n}', '{n}.parent_id');
        ksort($scenario_steps);


        $key = 0;
        $parent_id = NULL;
        $new = array();
        while(1) {

            if($new) {
                $step_data = array_shift($new);
                $key = key($step_data);
                $parent_id = $step_data[$key];
            }

            if(empty($old)) {
                break;
            }
            $flag = array_key_exists($key, $scenario_steps);
            if($flag == false) {
                continue;
            }
            array_shift($old);
            $temp = $scenario_steps[$key];
            
            $pg_save = array();
            foreach ($temp as $k => $v) {
                $data_to_save = array();
                $letter_id = NULL;

                if ($v['scenario_action_id'] == 1) {
                    $letter = $this->Letter->find('first', array(
                        'conditions' => array(
                            'Letter.id' => $v['letter_id'],
                            'Letter.user_id' => $this->user_data['User']['id']
                        )
                    ));

                    if (empty($letter)) {
                        $this->Scenario->delete($scenario_copy_id);
                        $this->Session->setFlash(__('Письмо не существует.'), 'flash/error');
                        $this->redirect($this->referer());
                    }
                    $letter['Letter']['id'] = null;

                    if (!$this->Letter->save($letter)) {
                        $this->Scenario->delete($scenario_copy_id);
                        $this->Session->setFlash(__('Ошибка при копировании письма.'), 'flash/error');
                        $this->redirect($this->referer());
                    }
                    $letter_id = $this->Letter->id;
                }

                $data_to_save['parent_id'] = $parent_id;
                $data_to_save['user_id'] = $this->user_data['User']['id'];
                $data_to_save['scenario_id'] = $scenario_copy_id;
                $data_to_save['scenario_event_id'] = $v['scenario_event_id'];
                $data_to_save['scenario_action_id'] = $v['scenario_action_id'];
                $data_to_save['letter_id'] = $letter_id;
                $data_to_save['data'] = $v['data'];
                $data_to_save['delay'] = $v['delay'];
                $data_to_save['fixed_time'] = $v['fixed_time'];
                $queue = array_values($data_to_save);
                $pg_save[] = '(\'' . implode("','", $queue) . '\')';
            }
            $save = implode(',', $pg_save);

            $query_to_insert = 'INSERT INTO scenario_steps 
                    (parent_id, user_id, scenario_id, scenario_event_id, scenario_action_id, letter_id, data, delay, fixed_time)
                    VALUES ' . $save . ' returning id';

            $query_to_insert = str_replace('\'\'','NULL', $query_to_insert);
            try {
                $result = pg_query($dbconn, $query_to_insert);
                $return = pg_fetch_all($result);
                foreach ($return as $k => $v) {
                    $old_key = array_values($scenario_steps[$key]);
                    $new[][$old_key[$k]['id']] = $v['id'];
                }

            } catch (Exception $ex) {
                $this->Session->setFlash(__('Ошибка при копировании шагов сценария.'), 'flash/error');
                $this->redirect($this->referer());
            }

        }
        $this->Session->setFlash(__('Сценарий скопирован.'), 'flash/success');
        $this->redirect($this->referer());
    }

}