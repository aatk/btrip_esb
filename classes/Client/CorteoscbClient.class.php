<?php

class CorteoscbClient extends ex_component
{
    private $metod;
    private $gds;
    private $connectionInfo;
    private $Auth;

    public function CreateDB()
    {
        $info["corteos_token"] = array(
            "id" => array('type' => 'int(11)', 'null' => 'NOT NULL', 'inc' => true),
            "login" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "token" => array('type' => 'varchar(50)', 'null' => 'NOT NULL'),
            "datecreate" => array('type' => 'datetime', 'null' => 'NOT NULL'),
            "datevalid" => array('type' => 'datetime', 'null' => 'NOT NULL')
        );

        $this->create($this->connectionInfo['database_type'], $info);
    }

    public function getsettings()
    {
        $settings = [
            "email" => "",
            "password" => "",
            "url" => ""
        ];

        return $settings;
    }


    public function __construct($metod = "", $connectionInfo = null, $debug = false)
    {
        parent::__construct($metod, $connectionInfo, $debug);

        $this->metod = $metod;
        if (isset($this->POST["connector"])) {
            $this->gds = $this->POST;
        } else {
            $this->gds = $_SESSION["i4b"][mb_strtolower($this->classname)];
        }
        $this->connectionInfo = $_SESSION["i4b"]["connectionInfo"]; //Прочитаем
        $this->Auth = new Auth();
    }


    public function getinfo($params)
    {
        $result = ["result" => false];

        if ($this->Auth->userauth()) {
            $result["result"] = true;

            $text = $this->phpinput;

            $userinfo = $this->Auth->getusersessioninfo();
            $basicauth = [
                "username" => $userinfo["login"],
                "password" => $userinfo["basicpassword"]
            ];

            $exparam = [
                "basicauth" => $basicauth
            ];

            $this->gds["connector"] = 1;
            $post = $this->gds;
            $post["fileinfo"] = $text;

            $res = $this->http_c_post("https://btrip.ru/Parser/Corteoscb/", $post, $exparam);

            if (isset($res["content"])) {
                $result = json_decode($res["content"], true);
            }

        } else {
            $result["error"] = "Authorization fail";
        }
        return $result;
    }

}

/*

Перед созданием услуги Кортеос

Если СтруктураУслуги.Свойство("nomenclature") Тогда
	Если СтруктураУслуги.nomenclature = "Проживание" ИЛИ СтруктураУслуги.nomenclature = "ОтменаПроживания" Тогда
		ИДУслуги = СтруктураУслуги.Synh;

		Запрос = Новый Запрос;
		Запрос.УстановитьПараметр("Характеристика", ПланыВидовХарактеристик.НаборХарактеристикДляНоменклатуры.НомерБрони );
		Запрос.УстановитьПараметр("ЗначениеХарактеристики", ИДУслуги);
		Запрос.Текст = "ВЫБРАТЬ ПЕРВЫЕ 1
		               |	ИнформацияПоУслуге.Услуга КАК Услуга,
		               |	ИнформацияПоУслуге.Характеристика КАК Характеристика,
		               |	ИнформацияПоУслуге.ЗначениеХарактеристики КАК ЗначениеХарактеристики
		               |ИЗ
		               |	РегистрСведений.ИнформацияПоУслуге КАК ИнформацияПоУслуге
		               |ГДЕ
		               |	ИнформацияПоУслуге.Характеристика = &Характеристика
		               |	И ИнформацияПоУслуге.ЗначениеХарактеристики = &ЗначениеХарактеристики";
		Выборка = Запрос.Выполнить().Выбрать();
		Если Выборка.Следующий() Тогда
			ВхОбъект = Выборка.Услуга;

			Запрос = Новый Запрос;
			Запрос.УстановитьПараметр("Услуга", ВхОбъект);
			Запрос.УстановитьПараметр("Характеристика", ПланыВидовХарактеристик.НаборХарактеристикДляНоменклатуры.IDСинхронизации );
			Запрос.Текст = "ВЫБРАТЬ ПЕРВЫЕ 1
			               |	ИнформацияПоУслуге.Услуга КАК Услуга,
			               |	ИнформацияПоУслуге.Характеристика КАК Характеристика,
			               |	ИнформацияПоУслуге.ЗначениеХарактеристики КАК ЗначениеХарактеристики
			               |ИЗ
			               |	РегистрСведений.ИнформацияПоУслуге КАК ИнформацияПоУслуге
			               |ГДЕ
			               |	ИнформацияПоУслуге.Услуга = &Услуга
			               |	И ИнформацияПоУслуге.Характеристика = &Характеристика";
			Выборка = Запрос.Выполнить().Выбрать();
			Если Выборка.Следующий() Тогда
				IDСинхронизации = Выборка.ЗначениеХарактеристики;
				СтруктураУслуги.Synh = IDСинхронизации;
				СтруктураУслуги.ReservationNumber = ИДУслуги;
			КонецЕсли;

		КонецЕсли;
	КонецЕсли;
КонецЕсли;



 */