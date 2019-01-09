<?php
error_reporting(0);
/*
* Служебная фунция
*/
function p($pre) {
    print_r($pre);
}

// =======================================================================================
// =======================================================================================

/*
* Get_Config()
* Получить параметры для настройки бота
*/
function Get_Config() {
  $config = file_get_contents(__DIR__ . '/config.json');
  return json_decode($config);
}

$config = Get_Config();
$coins_par = $config->coin_1 . '_' . $config->coin_2;

// =======================================================================================
// =======================================================================================

/*
* Get_Config()
* Получить номер запроса......
*/
function Nonce() {
  $nonce_num = file_get_contents(__DIR__ . '/nonce');

  if ( $nonce_num == '' ) { $nonce_num == 0; }

  $fp = fopen(__DIR__ . '/nonce', "w+");
  $rez_nonce_num = fwrite($fp, $nonce_num = $nonce_num + 1);

  return $nonce_num;
}

// =======================================================================================
// =======================================================================================

/*
* Yobit_Api()
* Общение с биржей
*/
function Yobit_Api($method, $req = array()) { // Yobit_Api

    $config     = Get_Config();
    $nonce_num  = Nonce();

    $req['method'] = $method;
    $req['nonce'] = $nonce_num;
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac("sha512", $post_data, $config->api_secret);

        $headers = array(
            'Sign: '. $sign,
            'Key: '. $config->api_key,
        );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36');
    curl_setopt($ch, CURLOPT_URL, 'https://yobitex.net/tapi/');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $res = curl_exec($ch);

          if($res === false) {
              curl_close($ch);
              return null;
          }

    curl_close($ch);
    $result = json_decode($res, true);
    return $result;

} // END Yobit_Api


// =======================================================================================
// =======================================================================================

function depth($coins = 'ltc_rur') {
  $get_info = json_decode(file_get_contents("https://yobitex.net/api/3/depth/$coins"));
  $get_info = json_decode(json_encode($get_info), True);
  $get_info = $get_info['ltc_rur']['bids']['0']['0'];

  $CoinFile = fopen(__DIR__ . '/CoinFile.txt', "w");
  $ResCoinFile = fwrite($CoinFile, $get_info);

  return $get_info;
}

depth($coins_par);
echo "\n\r";

// =======================================================================================
// =======================================================================================

function difference($num1, $num2) {
    $a = $num1;
    $b = $num2;
    $num = $a - $b;
    $num = str_replace('-', '', $num);
    $num = $num / $a;
    $num = $num * 100;
    $res = substr($num, 0 , 5);

        if ($a < $b) {
            return ' + ' . $res . "%";
        }

        if ($a > $b) {
            return ' - ' . $res . "%";
        }

        if ($a == $b) {
            return ' 0.0 ' . "%";
        }
}

// =======================================================================================
// =======================================================================================

$i = 1;
while (true) {

  $CoinFileOpen = file_get_contents(__DIR__. '/CoinFile.txt');
  if ($CoinFileOpen == '') {
    echo "ERROR No prace\n\r";
    break;
  }

  $Current_price = json_decode(file_get_contents("https://yobitex.net/api/3/depth/$coins_par"));
  $Current_price = json_decode(json_encode($Current_price), True);
  $Current_price = $Current_price['ltc_rur']['bids']['0']['0'];

  $difference = difference($CoinFileOpen, $Current_price);

  echo " " . $i . " [" . $coins_par . "] " . "Starting price: [" . $CoinFileOpen . "] =>  Current_price: [" . $Current_price . "] " . $difference . "\n";

  $difference = substr($difference, 1 , 3);

      if ( $difference >= 0.5 ) {
        echo ' Price increased by > 0.5 %' . "\n";
      }

      if ( $difference >= 1.0 ) {
        echo ' Price increased by > 1.0 %' . "\n";
      }

  sleep(60);
  // $i ++;
  //     if ($i == 31)
  //     {
  //       $i = 1;
  //       echo "\n\r It's been 10 minutes\n\r Update price \n\r\n\r";
  //       depth($coins_par);
  //     }
}

// =======================================================================================
// =======================================================================================
?>
