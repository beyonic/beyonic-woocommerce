<?php

/**
 * Class BPG_Payment_Tests
 *
 * @package Beyonic_Payment_Gateway
 */
class BPG_Payment_Tests extends WP_UnitTestCase {

    public $beyonic_key = 'ab594c14986612f6167a975e1c369e71edab6900';
    public $collection_request = array(
        "phonenumber" => +41421234567,
        "first_name" => 'test',
        "last_name" => 'test',
        "amount" => 10,
        "success_message" => 'Thank you for your payment!',
        "send_instructions" => true,
        "currency" => "BXC",
        "metadata" => array("order_id" => '20')
    );
    
    public $Wrong_collection_request = array(
        "phonenumber" => 2222859129343434341180,
        "first_name" => 'test',
        "last_name" => 'test',
        "amount" => 10,
        "success_message" => 'Thank you for your payment!',
        "send_instructions" => true,
        "currency" => "BXC",
        "metadata" => array("order_id" => '20')
    );
    
    public $webhook_request = array(
        "event" => "collection.received",
        "target" => "https://my.callback.url/"
    );

    public function test_constants() {
        $this->assertSame('beyonic-payment-gateway', BEYONIC_WPSP_NAME);
    }

    function test_beyonic_keys() {
        $key = $this->beyonic_key;
        Beyonic::setApiKey($key);
        $check_key = Beyonic::$apiKey;
        $this->assertEquals($key, $check_key);
    }

    function test_beyonic_version() {
        $version = "v1";
        Beyonic::setApiVersion($version);
        $check_version = Beyonic::$apiVersion;
        $this->assertEquals($version, $check_version);
    }

    function test_create_collection() {
        Beyonic::setApiKey($this->beyonic_key);
        $createCollection = Beyonic_Collection_Request::create($this->collection_request);
        $pos = strpos($createCollection->created, '.'); // ? position
        $createCollection->created = substr($createCollection->created, 0, $pos);
        $pos1 = strpos($createCollection->modified, '.'); // ? position
        $createCollection->modified = substr($createCollection->modified, 0, $pos1);

        $getCollection = Beyonic_Collection_Request::get($createCollection->id);
        $getCollection->created = str_replace('Z', '', $getCollection->created);
        $getCollection->modified = str_replace('Z', '', $getCollection->modified);

        $this->assertEquals($createCollection, $getCollection);
    }

    function test_create_webhook() {
        Beyonic::setApiKey($this->beyonic_key);
        $create = Beyonic_Webhook::create($this->webhook_request);
        $pos = strpos($create->created, '.'); // ? position
        $create->created = substr($create->created, 0, $pos);
        $pos1 = strpos($create->updated, '.'); // ? position
        $create->updated = substr($create->updated, 0, $pos1);

        $retrive = Beyonic_Webhook::get($create->id);
        $retrive->created = str_replace('Z', '', $retrive->created);
        $retrive->updated = str_replace('Z', '', $retrive->updated);

        $this->assertEquals($create, $retrive);
    }

    function test_invalid_phone_number() { 
        Beyonic::setApiKey($this->beyonic_key);
        try {
            $createCollection = Beyonic_Collection_Request::create($this->Wrong_collection_request);
        } catch (Exception $exc) {
            $error = json_decode($exc->responseBody)->phonenumber[0];
            $this->assertEquals("[u'Enter a valid phone number (e.g. +41421234567).']", $error);
        }
    } 

}
