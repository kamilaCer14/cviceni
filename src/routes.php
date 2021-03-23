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
});



