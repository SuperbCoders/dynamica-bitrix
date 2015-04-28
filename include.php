<?
/**
 * author Sergey Khrystenko
 * ���� ������ ������ ������
 * ������ �������� ���������� �� ������
 */
IncludeModuleLangFile(__FILE__);

/**
 * Class CItcubeDynamyca
 */
Class CItcubeDynamyca
{
    const MODULE_ID = 'itcube.dynamica';
    /**
     * @var bool|null|string
     */
    private $enableModule;
    /**
     * @var bool|null|string
     */
    private $apiToken;
    /**
     * @var bool|null|string
     */
    private $projectID;
    /**
     * @var string
     */
    private $apiUrlData;
    /**
     * @var bool|null|string
     */
    private $periodCount;
    /**
     * @var bool|null|string
     */
    private $periodType;
    /**
     * @var string
     */
    private $apiUrlForecast;
    /**
     * @var string
     */
    private $fileErrorPath;
    /**
     * @var string
     */
    private $fileIDsPath;
    /**
     * @var string
     */
    private $fileTimePath;
    /**
     * @var string
     */
    private $fileFirstExecPath;

    /**
     * ����������� ������
     */
    public function __construct(){
        $this->enableModule      = COption::GetOptionString(self::MODULE_ID, "activate_module"); //������� �� ������
        $this->apiToken          = COption::GetOptionString(self::MODULE_ID, "api_token"); //��� ����� �� ����������
        $this->projectID         = COption::GetOptionString(self::MODULE_ID, "project_id"); //�� ������� �� ����������
        $this->periodCount       = COption::GetOptionString(self::MODULE_ID, "period_count", "10"); //������ ������� ��������
        $this->periodType        = COption::GetOptionString(self::MODULE_ID, "period_type", "day"); //������ ������� ��������
        $this->apiUrlData        = 'http://dynamica.cc/api/v1/projects/'.$this->projectID.'/values'; //��� ��� �������� �������
        $this->apiUrlForecast    = 'http://dynamica.cc/api/v1/projects/'.$this->projectID.'/forecasts'; //��� ��� �������� ��������
        $this->fileErrorPath     = $_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_error.txt'; //���� � ���������� ���������� �������
        $this->fileIDsPath       = $_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_ids.txt'; //���� � �� ������������ ������� �� ����
        $this->fileFirstExecPath = $_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_firstexec.txt'; //���� �������������� ��� ������� ����� ��������� ������, ����� �� �������� ������
        $this->fileTimePath      = $_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_lastexec.txt'; //���� �� �������� ���������� ������� ������
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name){
        return $this->{$name};
    }

    /**
     * ������� ������� ������� �� �������
     * @return array
     */
    public function prepareArr(){

        CModule::IncludeModule("sale");
        CModule::IncludeModule("catalog");
        global $DB;

        $CSaleBasket      = new CSaleBasket();
        $CSaleOrder       = new CSaleOrder();
        $CSite            = new CSite();
        $USER             = new CUser();
        $CCatalogProduct  = new CCatalogProduct();
        $dateInsert       = false;
        $dateShort        = date( $DB->DateFormatToPHP($CSite->GetDateFormat("SHORT")), mktime(0, 0, 0, date("n"), date("j"), date("Y")) );
        $dateFull         = date( $DB->DateFormatToPHP($CSite->GetDateFormat("FULL")) );
        $data             = array();
        $ordersIds        = array();
        $ordersIds[0]     = $dateShort;
        $arFilter         = array();

        if( file_exists($this->fileTimePath) ){
            $arFilter[">=DATE_INSERT"] = file_get_contents($this->fileTimePath);
        }else{
            return $data;
        }

        //$dateInsert       = date( $DB->DateFormatToPHP($CSite->GetDateFormat("SHORT")), mktime(0, 0, 0, date("n"), date("j"), date("Y")) );

        if( file_exists($this->fileIDsPath) ){
            $ids = json_decode( file_get_contents($this->fileIDsPath), true );
            if( $ids[0] == $dateShort ) {
                unset($ids[0]);
                $arFilter["!ID"] = $ids;
            }else{
                unlink($this->fileIDsPath);
            }
        }

        $db_sales = $CSaleOrder->GetList(array(), $arFilter); //�������� ������� ��� ������
        while ($ar_sales = $db_sales->Fetch()) {
            $ordersIds[] = $ar_sales["ID"];

            $dbBasketItems = $CSaleBasket->GetList( //����� �������� �� ������� ��� ������
                array(),
                array(
                    "ORDER_ID" => $ar_sales["ID"]
                ),
                false,
                false,
                array("NAME", "PRODUCT_ID", "QUANTITY")
            );

            while ($arItems = $dbBasketItems->Fetch())
            {
                $date_tov = new DateTime($ar_sales["DATE_INSERT"]);

                $name = mb_convert_encoding($arItems["NAME"], "UTF-8");

                $arTovar = $CCatalogProduct->GetList( //�������� �������� �� ������ ��� ������������ �������
                    array(),
                    array("ELEMENT_NAME" => $name),
                    false,
                    array("nTopCount" => 1),
                    array("ID")
                )->Fetch();

                $data[ $arTovar["ID"] ]["NAME"]    = $name;
                $data[ $arTovar["ID"] ]["ITEMS"][] = array(
                    "timestamp" => mb_convert_encoding($date_tov->format('c'), "UTF-8"),
                    "value"     => mb_convert_encoding($arItems["QUANTITY"], "UTF-8"),
                );
            }
        }

        if( count($ordersIds) > 1 ){
            if( file_exists($this->fileIDsPath) ) {
                $ids = json_decode(file_get_contents($this->fileIDsPath), true);
                unset($ids[0]);
                foreach ($ids as $id) {
                    $ordersIds[] = $id;
                }
            }
            file_put_contents($this->fileIDsPath, json_encode($ordersIds,JSON_UNESCAPED_UNICODE));
        }

        return $data;
    }

    /**
     * �������� ������ �� ������ ����������
     * @param $data_string
     * @return mixed
     */
    public function sendCurlData($data_string){
        $data_string = json_encode($data_string,JSON_UNESCAPED_UNICODE);
        $ch = curl_init($this->apiUrlData);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Token token='.$this->apiToken)
        );

        $result = curl_exec( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        return $header["http_code"];
    }

    /**
     * �������� �� ������ ������� �� ������� ��������
     * @return mixed
     */
    public function sendCurlForecast(){
        $data_string = json_encode(
            array(
                "forecast" => array(
                    "period" => mb_convert_encoding($this->periodType, "UTF-8"),
                    "depth" => mb_convert_encoding($this->periodCount, "UTF-8")
                )
            ), JSON_UNESCAPED_UNICODE
        );

        $ch = curl_init($this->apiUrlForecast);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Token token='.$this->apiToken)
        );

        $result = curl_exec( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        return $header["http_code"];
    }

    /**
     * @return bool
     */
    public function sendStatistics(){ //������� �������� ����������
        global $DB;
        $CSite    = new CSite();
        $dateFull = date( $DB->DateFormatToPHP($CSite->GetDateFormat("FULL")) );
        if( $this->enableModule == 'Y' && $this->apiToken != '' && $this->projectID != '' ){
            $arr = $this->prepareArr(); //�������� ������ ���� �������

            if( !empty($arr) ) {
                foreach ($arr as $key => $val) { //��������� ��� � ���������� ��������� ��� ��������
                    $data = array();

                    $data["item"] = array(
                        "sku" => $key,
                        "name" => $val["NAME"]
                    );
                    $data["values"] = $val["ITEMS"];

                    $statusCode = $this->sendCurlData($data); //���������� ������

                    if( $statusCode == 201 ){
                        unset($arr[$key]);
                    }
                }
                $this->sendCurlForecast(); //���������� ������ �� �������

                if( count($arr) > 0 ){
                    if( file_exists($this->fileErrorPath) ){
                        $arError = json_decode( file_get_contents($this->fileErrorPath), true );
                        foreach( $arError as $key => $val ){
                            $arr[$key] = $val;
                        }
                    }
                    file_put_contents($this->fileErrorPath, json_encode($arr,JSON_UNESCAPED_UNICODE));
                }
            }
            file_put_contents( $this->fileTimePath, $dateFull );
        }
        return true;
    }

    /**
     * ������� ������� ������ � �� ������ � ���� ��� ������ ���������� ���������� ������
     */
    public function firstSave(){
        CModule::IncludeModule("sale");
        CModule::IncludeModule("catalog");
        global $DB;

        $CSaleBasket      = new CSaleBasket();
        $CSaleOrder       = new CSaleOrder();
        $CSite            = new CSite();
        $USER             = new CUser();
        $CCatalogProduct  = new CCatalogProduct();
        $arFilter         = array();
        $data             = array();
        $dateFull         = date( $DB->DateFormatToPHP($CSite->GetDateFormat("FULL")) );

        $db_sales = $CSaleOrder->GetList(array(), $arFilter);
        while ($ar_sales = $db_sales->Fetch()) {
            $ordersIds[] = $ar_sales["ID"];

            $dbBasketItems = $CSaleBasket->GetList(
                array(),
                array(
                    "ORDER_ID" => $ar_sales["ID"]
                ),
                false,
                false,
                array("NAME", "PRODUCT_ID", "QUANTITY")
            );

            while ($arItems = $dbBasketItems->Fetch())
            {
                $date_tov = new DateTime($ar_sales["DATE_INSERT"]);

                $name = mb_convert_encoding($arItems["NAME"], "UTF-8");

                $arTovar = $CCatalogProduct->GetList(
                    array(),
                    array("ELEMENT_NAME" => $name),
                    false,
                    array("nTopCount" => 1),
                    array("ID")
                )->Fetch();

                $data[ $arTovar["ID"] ]["NAME"]    = $name;
                $data[ $arTovar["ID"] ]["ITEMS"][] = array(
                    "timestamp" => mb_convert_encoding($date_tov->format('c'), "UTF-8"),
                    "value"     => mb_convert_encoding($arItems["QUANTITY"], "UTF-8"),
                );
            }
        }

        file_put_contents( $this->fileFirstExecPath, json_encode($data, JSON_UNESCAPED_UNICODE) );
        file_put_contents( $this->fileTimePath, $dateFull );
        CAgent::AddAgent(
            "CItcubeDynamyca::sendFirstTime();",
            "itcube.dynamica",
            "N",
            600
        );
    }

    /**
     * ������� ��� ��������� �������� ������ ���� ��������� ������
     * @return string
     */
    public static function sendIfError(){
        $stat = new CItcubeDynamyca();
        if( file_exists($stat->fileErrorPath) && $stat->enableModule == 'Y' && $stat->apiToken != '' && $stat->projectID != '' ){
            $arr = json_decode( file_get_contents($stat->fileErrorPath), true );

            if( !empty($arr) ) {
                foreach ($arr as $key => $val) {
                    $data = array();

                    $data["item"] = array(
                        "sku" => $key,
                        "name" => $val["NAME"]
                    );
                    $data["values"] = $val["ITEMS"];

                    $statusCode = $stat->sendCurlData($data);

                    if( $statusCode == 201 ){
                        unset($arr[$key]);
                    }
                }
                $stat->sendCurlForecast();

                if( count($arr) > 0 ){
                    file_put_contents($stat->fileErrorPath, json_encode($arr,JSON_UNESCAPED_UNICODE));
                }else{
                    unlink($stat->fileErrorPath);
                }
            }
        }

        return 'CItcubeDynamyca::sendIfError();';
    }

    /**
     * ������� ��� ����������� �������� ������ � ������ ��� �� ������ ��� � 10 �����, ����� �� �������� ������
     * @return string
     */
    public static function sendFirstTime(){
        $stat = new CItcubeDynamyca();
        $cnt  = 0;

        if( file_exists($stat->fileFirstExecPath) && $stat->enableModule == 'Y' && $stat->apiToken != '' && $stat->projectID != '' ) {
            $arr = json_decode(file_get_contents($stat->fileFirstExecPath), true);

            if( !empty($arr) ) {
                foreach ($arr as $key => $val) {
                    $data = array();

                    $data["item"] = array(
                        "sku" => $key,
                        "name" => $val["NAME"]
                    );
                    $data["values"] = $val["ITEMS"];

                    $statusCode = $stat->sendCurlData($data);

                    if( $statusCode == 201 ){
                        unset($arr[$key]);
                    }

                    $cnt++;
                    if( $cnt == 20 ){
                        break;
                    }
                }
                $stat->sendCurlForecast();

                if( count($arr) == 0 ){
                    CAgent::RemoveAgent("CItcubeDynamyca::sendFirstTime();", "itcube.dynamica");
                }

                file_put_contents($stat->fileFirstExecPath, json_encode($arr,JSON_UNESCAPED_UNICODE));
            }else{
                CAgent::RemoveAgent("CItcubeDynamyca::sendFirstTime();", "itcube.dynamica");
            }
        }
        return 'CItcubeDynamyca::sendFirstTime();';
    }
}
?>
