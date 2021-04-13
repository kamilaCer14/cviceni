<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');


$app->post('/test', function (Request $request, Response $response, $args) {
    //read POST data
    $input = $request->getParsedBody();
    //log
    $this->logger->info('Your name: ' . $input['person']);
    return $response->withHeader('Location', $this->router->pathFor('index'));
})->setName('redir');



/* Vypis osob */
$app->get('/persons', function (Request $request, Response $response, $args) {
	$stmt = $this->db->query('SELECT first_name, last_name, nickname, height FROM person ORDER BY first_name'); # databazovy objekt, cursor
	$tplVars['persons_list'] = $stmt->fetchall(); # [ ['id_person' => 1, 'firs_name'=> 'johny'...], ['id_person' => 2... ]  ]
	#echo var_dump($persons);
	return $this->view->render($response, 'persons.latte', $tplVars);
})->setName('persons');


/* Vyhladavnie osob */
$app->get('/person/search', function(Request $request, Response $response, $args) {
    // localhost:2000/public/persons?query=carl&height=25 [query => 'carl', height => 25]
    $queryParams = $request->getQueryParams();
    if (! empty($queryParams) && ! empty($queryParams['query'])) {
       $stmt = $this->db->prepare('SELECT first_name, last_name, nickname, height FROM person WHERE lower(first_name) = lower(:fname) OR lower(last_name) = lower(:lname) ORDER BY first_name');
       $stmt->bindParam(':fname', $queryParams['query']);
       $stmt->bindParam(':lname', $queryParams['query']);
       $stmt->execute();
       $tplVars['persons_list'] = $stmt->fetchall(); 
       return $this->view->render($response, 'persons.latte', $tplVars);
    }
})->setName('search');


/* Nacitanie formularu pre novu osobu */
$app->get('/person', function(Request $request, Response $response, $args) {
  $tplVars['header'] = 'New person';
  $tplVars['formData'] = [
    'first_name' => '',
    'last_name' => '',
    'nickname' => '',
    'gender' => '',
    'height' => '',
    'birth_day' => '',
    'street_name' => '',
    'street_number' => '',
    'city' => '',
    'zip' => ''
  ];
  return $this->view->render($response, 'person-form.latte', $tplVars);
})->setName('newPerson');


/* Post nova osoba */
$app->post('/person', function(Request $request, Response $response, $args) {
  $formData = $request->getParsedBody();
  if ( empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['nickname']) ) {
    $tplVars['message'] = 'Please fill required fields';
  } else {
    try {
      $stmt = $this->db->prepare('INSERT INTO person (first_name, last_name, nickname, gender, height, birth_day)
                                              VALUES (:first_name, :last_name, :nickname, :gender, :height, :birth_day)');
      $stmt->bindValue(':first_name', $formData['first_name']); // ;DROP DATABASE xvalovic; ===> `\;DROP DATABASE xvalovic`
      $stmt->bindValue(':last_name', $formData['last_name']);
      $stmt->bindValue(':nickname', $formData['nickname']);
      $stmt->bindValue(':gender', empty($formData['gender']) ? null : $formData['gender']);
      $stmt->bindValue(':height', empty($formData['height']) ? null : $formData['height']);
      $stmt->bindValue(':birth_day', empty($formData['birth_day']) ? null : $formData['birth_day']);
      $stmt->execute();
    } catch (PDOexception $e) {
      $tplVars['message'] = 'Error occured, sorry jako';
      $tplVars['formData'] = $formData;
      $this->logger->error($e->getMessage());
    }
  }
  $tplVars['header'] = 'New person';
  return $this->view->render($response, 'person-form.latte', $tplVars);
});


