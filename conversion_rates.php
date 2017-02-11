<?php
class ConversionRate
{
  private $servername = "localhost";//would want to move these for protection
  private $username = "root";
  private $password = "";
  private $dbname = "conversion";
  private $tablename = "conversion_rates";

  private function connect(){
    return new mysqli($this->servername, $this->username, $this->password, $this->dbname);    
  }

  private function refresh_table(){
    $conn = $this->connect();
    $sql = "TRUNCATE TABLE $this->tablename";
    $conn->query($sql);    
  }

  private function insert_new($currency, $rate){
    $conn = $this->connect();
    $sql = "INSERT INTO $this->tablename (currency, rate) VALUES ('$currency', '$rate');";
    ($conn->query($sql)) ? "New record created successfully\n" : "Error: " . $sql . " " . $conn->error; // might want logging here
  }

  public function reload_data($url){
    $data = simplexml_load_file($url);
    //assume need to redo db everyday (to avoid duplicates)
    $this->refresh_table();

    foreach ($data->conversion as $conversion_rate) {
      $this->insert_new($conversion_rate->currency, $conversion_rate->rate);
    }
  }

  public function convert($amount){
    $currency =substr($amount, 0, 3);
    $conn = $this->connect();
    $sql = ("SELECT rate FROM $this->tablename WHERE currency='$currency';");
    $rate = '';
    if(!$result = $conn->query($sql)){
        die('There was an error running the query [' . $db->error . ']');
    }
    else {
      while($row = $result->fetch_assoc()){
        $rate = $row['rate']; //might want to consider duplicate records
      }
      return ($rate != '') ? 'USD ' . $rate * intval(substr($amount, 4, -1)) : 'Currency not found';
    }
  }

  public function convert_amounts($amounts) {
    $converted_amounts = [];
    foreach ($amounts as $amount) {
      array_push($converted_amounts, $this->convert($amount));
    }
    return $converted_amounts;
  }


}
$cr = new ConversionRate;
$cr->reload_data('https://wikitech.wikimedia.org/wiki/Fundraising/tech/Currency_conversion_sample?ctype=text/xml&action=raw');
$result = $cr->convert('JPY 5000');
echo $result;
$cr->convert_amounts(['JPY 5000', 'CZK 62.5']);
