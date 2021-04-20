<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

include 'location.php';

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
	$stmt = $this->db->query('SELECT id_person, first_name, last_name, nickname, height FROM person ORDER BY first_name'); # databazovy objekt, cursor
	$tplVars['persons_list'] = $stmt->fetchall(); # [ ['id_person' => 1, 'firs_name'=> 'johny'...], ['id_person' => 2... ]  ]
	#echo var_dump($persons);
	return $this->view->render($response, 'persons.latte', $tplVars);
})->setName('persons');


/* Vyhladavnie osob */
$app->get('/person/search', function(Request $request, Response $response, $args) {
    // localhost:2000/public/persons?query=carl&height=25 [query => 'carl', height => 25]
    $queryParams = $request->getQueryParams();
    if (! empty($queryParams) && ! empty($queryParams['query'])) {
       $stmt = $this->db->prepare('SELECT id_person, first_name, last_name, nickname, height FROM person WHERE lower(first_name) = lower(:fname) OR lower(last_name) = lower(:lname) ORDER BY first_name');
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
      $this->db->beginTransaction();

      if ( !empty($formData['street_name']) || !empty($formData['street_number']) || !empty($formData['city']) || !empty($formData['zip']) ) {      
          $id_location = newLocation($this, $formData);
        }
      $stmt = $this->db->prepare('INSERT INTO person (first_name, last_name, nickname, gender, height, birth_day, id_location)
                                              VALUES (:first_name, :last_name, :nickname, :gender, :height, :birth_day, :id_location)');
      $stmt->bindValue(':first_name', $formData['first_name']); // ;DROP DATABASE xvalovic; ===> `\;DROP DATABASE xvalovic`
      $stmt->bindValue(':last_name', $formData['last_name']);
      $stmt->bindValue(':nickname', $formData['nickname']);
      $stmt->bindValue(':gender', empty($formData['gender']) ? null : $formData['gender']);
      $stmt->bindValue(':height', empty($formData['height']) ? null : $formData['height']);
      $stmt->bindValue(':birth_day', empty($formData['birth_day']) ? null : $formData['birth_day']);
      $stmt->bindValue(':id_location', $id_location ? $id_location : null); #bacha na poradie!
      $stmt->execute();
      $this->db->commit();
    } catch (PDOexception $e) {
      $tplVars['message'] = 'Error occured, sorry jako';
      $tplVars['formData'] = $formData;
      $this->logger->error($e->getMessage());
      $this->db->rollback();
    }
  }
  $tplVars['header'] = 'New person';
  return $this->view->render($response, 'person-form.latte', $tplVars);
});



/* UPDATE PERSSON form */
$app->get('/person/{id_person}', function (Request $request, Response $response, $args) {
  if (! empty($args['id_person'])) {
    $stmt = $this->db->prepare('SELECT * FROM person 
                                LEFT JOIN location USING (id_location) 
                                WHERE id_person = :id_person');
    $stmt->bindValue(':id_person', $args['id_person']);
    $stmt->execute();
    $tplVars['formData'] = $stmt->fetch();
    if (empty($tplVars['formData'])) {
      exit('person not found');
    } else {
      $tplVars['header'] = 'Edit person';
      return $this->view->render($response, 'person-form.latte', $tplVars);
    }
  }
})->setName('updatePerson');


/* UPDATE OSOBY */
$app->post('/person/{id_person}', function (Request $request, Response $response, $args) {
  $formData = $request->getParsedBody();
  $tplVars = [];
  if ( empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['nickname']) ) {
    $tplVars['message'] = 'Please fill required fields';
  } else {
    try {
      # Kontrolujeme ci bola aspon jedna cast adresy vyplnena
      if ( !empty($formData['street_name']) || !empty($formData['street_number']) || !empty($formData['city']) || !empty($formData['zip']) ) {

        $stmt = $this->db->prepare('SELECT id_location FROM person WHERE id_person = :id_person');
        $stmt->bindValue(':id_person', $args['id_person']);
        $stmt->execute();
        $id_location = $stmt->fetch()['id_location']; # {'id_location' => 123}
        if ($id_location) {
          ## Osoba ma adresu (id_location IS NOT NULL)
          editLocation($this, $id_location, $formData);
        } else {
          ## Osoba nema adresu (id_location NULL)
          $id_location = newLocation($this, $formData);
        }
      }
      $stmt = $this->db->prepare("UPDATE person SET 
                        first_name = :first_name,  
                        last_name = :last_name,
                        nickname = :nickname,
                        birth_day = :birth_day,
                        gender = :gender,
                        height = :height,
                        id_location = :id_location
                    WHERE id_person = :id_person");
      $stmt->bindValue(':nickname', $formData['nickname']);
      $stmt->bindValue(':first_name', $formData['first_name']);
      $stmt->bindValue(':last_name', $formData['last_name']);
      $stmt->bindValue(':id_location',  $id_location ? $id_location : null);
      $stmt->bindValue(':gender', empty($formData['gender']) ? null : $formData['gender'] );
      $stmt->bindValue(':birth_day', empty($formData['birth_day']) ? null : $formData['birth_day']);
      $stmt->bindValue(':height', empty($formData['height']) ? null : $formData['height']);
      $stmt->bindValue(':id_person', $args['id_person']);
      $stmt->execute();
      $tplVars['message'] = 'Person succesfully updated';

    } catch (PDOexception $e) {
      $tplVars['message'] = 'Error occured, sorry jako';
      $this->logger->error($e->getMessage());
    }
  }
  $tplVars['formData'] = $formData;
  $tplVars['header'] = 'Edit person';
  return $this->view->render($response, 'person-form.latte', $tplVars);
});