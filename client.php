<?php
// model
class Book
{
  public $name;
  public $year;
}

// create instance and set a book name
$book      =new Book();
$book->name='test 2';
$params = array('UID'=> 'test', 'PWD'=> 'test');
// initialize SOAP client and call web service function
$client=new SoapClient('https://docs.narrandera.nsw.gov.au/srv.asmx?WSDL');
$resp  =$client->AuthenticateUser($params);

// dump response
var_dump($resp);