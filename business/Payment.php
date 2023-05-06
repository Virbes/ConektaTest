<?php
require_once("bin/conekta-php-master/lib/Conekta.php");
class Payment
{

  private $ApiKey = "key_iRJPby3iaRYKcJydo7ERndO";
  private $ApiVersion = "2.0.0";

  public $token = "";
  public $card = "";
  public $name = "";
  public $description = "";
  public $total = "";
  public $email = "";

  public function __construct($token, $card, $name, $description, $total, $email)
  {
    $this->token = $token;
    $this->card = $card;
    $this->name = $name;
    $this->description = $description;
    $this->total = $total;
    $this->email = $email;
  }

  public function Pay()
  {

    \Conekta\Conekta::setApiKey($this->ApiKey);
    \Conekta\Conekta::setApiVersion($this->ApiVersion);

    if (!$this->Validate())
      return false;

    if (!$this->CreateCustomer())
      return false;

    if (!$this->CreateOrder())
      return false;

    $this->Save();

    return true;
  }

  public function Save()
  {
    $array = array(
      'total' => $this->total,
      'description' => $this->description,
      'name' => utf8_decode($this->name),
      'number_card' => substr($this->card, strlen($this->card) - 5, 4),
      'email' => $this->email,
      'order_id' => $this->order->id
    );

    echo json_encode($array);
  }

  public function CreateOrder()
  {
    try {
      $this->order = \Conekta\Order::create(
        array(
          "amount" => $this->total,
          "line_items" => array(
            array(
              "name" => $this->description,
              "unit_price" => $this->total * 100, //se multiplica por 100 conekta
              "quantity" => 1
            ) //first line_item
          ), //line_items
          "currency" => "MXN",
          "customer_info" => array(
            "customer_id" => $this->customer->id
          ), //customer_info
          "charges" => array(
            array(
              "payment_method" => array(
                "type" => "default"
              )
            ) //first charge
          ) //charges
        ) //order
      );
    } catch (\Conekta\ProcessingError $error) {
      $this->error = $error->getMessage();
      return false;
    } catch (\Conekta\ParameterValidationError $error) {
      $this->error = $error->getMessage();
      return false;
    } catch (\Conekta\Handler $error) {
      $this->error = $error->getMessage();
      return false;
    }

    return true;
  }
  public function CreateCustomer()
  {
    try {
      $this->customer = \Conekta\Customer::create(
        array(
          "name" => $this->name,
          "email" => $this->email,
          //"phone" => "+52181818181",
          "payment_sources" => array(
            array(
              "type" => "card",
              "token_id" => $this->token
            )
          ) //payment_sources
        ) //customer
      );
    } catch (\Conekta\ProccessingError $error) {
      $this->error = $error->getMesage();
      return false;
    } catch (\Conekta\ParameterValidationError $error) {
      $this->error = $error->getMessage();
      return false;
    } catch (\Conekta\Handler $error) {
      $this->error = $error->getMessage();
      return false;
    }

    return true;
  }

  public function Validate()
  {
    if (
      $this->card == "" ||
      $this->name == "" ||
      $this->description == "" ||
      $this->total == "" ||
      $this->email == ""

    ) {
      $this->error = "El número de tarjeta, el nombre, concepto, monto y correo electrónico son obligatorios";
      return false;
    }

    if (strlen($this->card) <= 14) {
      $this->error = "El número de tarjeta debe tener al menos 15 caracteres";
      return false;
    }
    if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
      $this->error = "El correo electrónico no tiene un formato de correo valido";
      return false;
    }
    if ($this->total <= 20) {
      $this->error = "El monto debe ser mayor a 20 pesos";
      return false;
    }

    return true;
  }
}
