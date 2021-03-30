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