<?php

class Uid {

  private $authUrl = 'https://u.id/oauth/authorize';
  private $restApiUrl = 'https://api.u.id';

  protected $header=[];
  protected $access_token=null;
  protected $refresh_token=null;
  protected $expires_in=null;

  private $client_id;
  private $client_secret;
  private $redirect_uri;
  private $scope;

  public function __construct(string $client_id, string $client_secret, string $redirect_uri)
  {
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
    $this->redirect_uri = $redirect_uri;
  }

  public function getAccessToken() {
    return $this->access_token;
  }

  public function getRefreshToken() {
    return $this->refresh_token;
  }

  public function getExpiresToken() {
    return $this->expires_in;
  }}

  public function setScope(array $scopes) {
    $this->scope = implode(' ', $scopes);
    return $this;
  }

  public function getAuthorizationUri() {
    $get_param = array(
      'client_id' => $this->client_id,
      'redirect_uri' => urldecode($this->redirect_uri),
      'response_type' => 'code',
      'scope' => urldecode($this->scope)
    );
    return $this->authUrl . "?" . http_build_query($get_param);
  }

  private function requestAccessToken(string $code) {
    $endpoint = "/oauth/token";
    $formBody = array(
      'grant_type' => "authorization_code",
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
      'redirect_uri' => $this->redirect_uri,
      'code' => $code
    );
    $request = $this->request($endpoint, 0, $formBody);
    return $request;
  }

  public function refreshAccessToken(string $refresh_token) {
    $endpoint = "/oauth/token";
    $formBody = array(
      'grant_type' => "refresh_token",
      'refresh_token' => $refresh_token,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
      'scope' => $this->scope,
    );
    $request = $this->request($endpoint, 0, $formBody);
    return $request;
  }

  public function handleCallback(array $query) {
    $state = isset($query['state']) ? $query['state'] : null;
    $code = isset($query['code']) ? $query['code'] : null;
    $error = isset($query['error']) ? $query['error'] : null;
    if('access_denied' == $error) {
      //throw new Exception();
    }
    if (null !== $code) {
      $t = $this->requestAccessToken($code);
      $this->access_token = $t['access_token'];
      $this->refresh_token = $t['refresh_token'];
      $this->expires_in = $t['expires_in'];
    }
  }

  public function getSelfInfo(array $fields) {
    $endpoint = "/api/v1.0/user/info/self";
    $field = array('fields' => implode(',', $fields));
    $request = $this->request($endpoint, $field, 0);
    return $request;
  }

  protected function request(string $endpoint, $param=false, $post=false) {
    $curl = curl_init();
    if($this->access_token) {
      array_push($this->header, 'Authorization: Bearer ' . $this->access_token);
      array_push($this->header, 'X-Requested-With: XMLHttpRequest');
      array_push($this->header, 'Accept: application/json');
    }
    curl_setopt_array($curl, array(
			CURLOPT_URL            => $this->restApiUrl . $endpoint . ( $param ? '?'.http_build_query($param) : ''),
			CURLOPT_HTTPHEADER     => $this->header,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_VERBOSE        => 0,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
    ));
    if($post) {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    $data = curl_exec($curl);
    curl_close($curl);
    return json_decode($data, true);
  }

}
