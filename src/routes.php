<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


include 'location.php';

define('PASSWORD_SALT', 'xlahi2afk');



function validateToken($token, $db) {
    $stmt = $db->prepare('SELECT * FROM uzivatelia WHERE token = :token');
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $auth = $stmt->fetch();
    # Vráti mi to true ak je tokan platný (ak existuje v DB)
    return !empty($auth['token']);
}


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

# Nacitanie registracneho formulara
$app->get('/register', function (Request $request, Response $response, $args){
    $tplVars = [
        'formData' => [
            'email' => '',
            'password' => ''
        ],
        'title' => 'Register'
    ];
    return $this->view->render($response, 'register.latte', $tplVars);
})->setName('register');

# Registracia uzivatela
$app->post('/register', function (Request $request, Response $response, $args){
    $formData = $request->getParsedBody();
    # Overtime ci uzivatel už nahodou neexistuje
    $stmt = $this->db->prepare('SELECT * FROM uzivatelia WHERE email = :email');
    $stmt->bindValue(':email', $formData['email']);
    $stmt->execute();
    $user = $stmt->fetch();

    if(!empty($user['email']) ) {
        $tplVars['message'] = 'Sorry, this email is already used';
        return $this->view->render($response, 'register.latte', $tplVars);
    } else {
        $tplVars = [];
        try {
            $this->db->beginTransaction();
            $password = md5(PASSWORD_SALT . $formData['password']);
            $token = bin2hex(openssl_random_pseudo_bytes(20));

            $stmt = $this->db->prepare('INSERT INTO uzivatelia (email, password, token) VALUES (:email, :password, :token)');
            $stmt->bindValue(':email', $formData['email']);
            $stmt->bindValue(':password', $password );
            $stmt->bindValue(':token', $token);
            $stmt->execute();
            $this->db->commit();
            setcookie('token', $token, 0);  # 0->životnosť cookie (nekonečnosť)
            return $response->withHeader('Location', $this->router->pathFor('index'));
        } catch (PDOexception $e) {
            $this->logger->error($e);
            $this->db->rollback();
            return $this->view->render($response, 'register.latte', $tplVars);

        }
    }

});

/* login */
$app->get('/login', function(Request $request, Response $response, $args) {
    $tplVars = [
        'formData' => [
            'email' => '',
            'password' => ''
        ],
        'title' => 'Login'
    ];
    return $this->view->render($response, 'register.latte', $tplVars);
})->setName('login');

/* login */
$app->post('/login', function(Request $request, Response $response, $args) {
    $formData = $request->getParsedBody();
    $tplVars = [
        'formData' => $formData,
        'title' => 'Login'
    ];

    # Overime si existenciu uzivatela
    $stmt = $this->db->prepare('SELECT * FROM uzivatelia WHERE email = :email AND password = :password');
    $stmt->bindValue(':email', $formData['email']);
    $stmt->bindValue('password', md5(PASSWORD_SALT . $formData['password']));
    $stmt->execute();
    $user = $stmt->fetch();
    if(empty($user['email'])) {
        $tplVars['message'] = 'Email or password is incorrect';
        return $this->view->render($response, 'register.latte', $tplVars);
    } else {
        $token = bin2hex(openssl_random_pseudo_bytes(20));
        $stmt = $this->db->prepare('UPDATE uzivatelia SET token = :token WHERE id_uziv = :id_uziv');
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':id_uziv', $user['id_uziv']);
        $stmt->execute();

        setcookie('token', $token, 0);
        return $response->withHeader('Location', $this->router->pathFor('index'));

    }
});


/* logout */
$app->get('/logout', function(Request $request, Response $response, $args) {
    setcookie('token', '', time() - 3600);  # nastaviť cookie do minulosti, aby bola neplatná a už sa odosielať nebude
    return $response->withHeader('Location', $this->router->pathFor('index'));
})->setName('logout');

/* Vsetky routy v tejto skupine budu dostupne LEN s platnym tokenom */

$app->group('/auth', function() use($app) {



    /* Vypis produktov */
    $app->get('/produkty', function (Request $request, Response $response, $args) {
        $params = $request->getQueryParams();
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        };

        if (empty($params['page'])) {
            $params['page'] = 0;
        };

        $stmt = $this->db->query('SELECT count(*) pocet FROM produkty');
        $total_pages = $stmt->fetch()['pocet'] / $params['limit'];


        $stmt = $this->db->prepare('SELECT cislo_artikla, nazov, sezona, pohlavie, druh, farba, stav, znacka, predajna_cena, nakupna_cena, dodavatel, ean, predajnecislo_predajne 
                                    FROM produkty ORDER BY cislo_artikla LIMIT :limit OFFSET :offset'); # databazovy objekt, cursor
        $stmt->bindValue(':limit', $params['limit']);
        $stmt->bindValue(':offset', $params['page'] * $params['limit']);
        $stmt->execute();
        $produkty = $stmt->fetchall();

        $tplVars = [
            'produkty_list' => $produkty,
            'total_pages' => $total_pages,
            'page' => $params['page'],
            'limit' => $params['limit']
        ];
        return $this->view->render($response, 'produkty.latte', $tplVars);
    })->setName('produkty');


    /* Vyhladavnie produktov */
    $app->get('/produkty/search', function (Request $request, Response $response, $args) {
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams) ){
            $stmt = $this->db->prepare('SELECT * FROM produkty WHERE 
                                        cislo_artikla = :cislo_artikla OR
                                        lower(nazov) = lower(:nazov) OR 
                                        lower(znacka) = lower(:znacka) OR 
                                        lower(druh) = lower(:druh)   OR 
                                        lower(pohlavie) = lower(:pohlavie)   OR 
                                        lower(farba) = lower(:farba)   OR 
                                        lower(dodavatel) = lower(:dodavatel)   OR
                                        lower(sezona) = lower(:sezona)');
            $stmt->bindParam(':cislo_artikla', $queryParams['q']);
            $stmt->bindParam(':nazov', $queryParams['q']);
            $stmt->bindParam(':znacka', $queryParams['q']);
            $stmt->bindParam(':druh', $queryParams['q']);
            $stmt->bindParam(':pohlavie', $queryParams['q']);
            $stmt->bindParam(':farba', $queryParams['q']);
            $stmt->bindParam(':dodavatel', $queryParams['q']);
            $stmt->bindParam(':sezona', $queryParams['q']);
            $stmt->execute();
            $tplVars['produkty_list'] = $stmt->fetchall();
            return $this->view->render($response, 'produkty.latte', $tplVars);
        }
    })->setName('search');


    /* Nacitanie formularu pre nový produkt */
    $app->get('/produkt', function (Request $request, Response $response, $args) {
        $tplVars['header'] = 'Nový produkt';
        $tplVars['formData'] = [
            'nazov' => '',
            'sezona' => '',
            'pohlavie' => '',
            'druh' => '',
            'farba' => '',
            'stav' => '',
            'znacka' => '',
            'dodavatel' => '',
            'predajna_cena' => '',
            'nakupna_cena' => '',
            'ean' => '',
            'predajnecislo_predajne' => ''
        ];
        $stmt = $this->db->query('SELECT d.id_dod, d.nazov FROM dodavatelia d ORDER BY nazov ASC');
        $dodavatelia_list = $stmt->fetchAll();
        $tplVars['dodavatelia_list'] = $dodavatelia_list;

        return $this->view->render($response, 'produkt-formular.latte', $tplVars);
    })->setName('novyProdukt');

    /* Post novy produkt */
    $app->post('/produkt', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (empty($formData['nazov']) || empty($formData['sezona']) ||
            empty($formData['pohlavie']) || empty($formData['druh']) || empty($formData['farba']) ||
            empty($formData['stav']) || empty($formData['znacka'])  ||
            empty($formData['dodavatel']) || empty($formData['predajna_cena']) ||
            empty($formData['nakupna_cena']) || empty($formData['ean']) || empty($formData['predajnecislo_predajne'])) {
            $tplVars['message'] = 'Please fill required fields';
        } else {
            try {
                $this->db->beginTransaction();

                // Fetch last used cislo_artikla value
                $stmt = $this->db->query('SELECT MAX(cislo_artikla) as max_cislo FROM produkty');
                $result = $stmt->fetch();
                $lastId = $result['max_cislo'];
                $newId = $lastId + 1;

                $stmt = $this->db->prepare('INSERT INTO produkty (cislo_artikla, nazov, sezona, pohlavie, druh, farba, stav,
                                                                znacka, dodavatel, nakupna_cena, predajna_cena, ean, predajnecislo_predajne)
                                              VALUES (:cislo_artikla, :nazov, :sezona, :pohlavie, :druh, :farba, :stav,
                                                      :znacka, :dodavatel, :nakupna_cena, :predajna_cena, :ean, :predajna)');
                $stmt->bindValue(':cislo_artikla', $newId);
                $stmt->bindValue(':nazov', $formData['nazov']);
                $stmt->bindValue(':sezona', $formData['sezona']);
                $stmt->bindValue(':pohlavie', empty($formData['pohlavie']) ? null : $formData['pohlavie']);
                $stmt->bindValue(':druh', $formData['druh']);
                $stmt->bindValue(':farba', $formData['farba']);
                $stmt->bindValue(':stav', $formData['stav']);
                $stmt->bindValue(':znacka', $formData['znacka']);
                $stmt->bindValue(':dodavatel', $formData['dodavatel']);
                $stmt->bindValue(':nakupna_cena', $formData['nakupna_cena']);
                $stmt->bindValue(':predajna_cena', $formData['predajna_cena']);
                $stmt->bindValue(':ean', $formData['ean']);
                $stmt->bindValue(':predajna', $formData['predajnecislo_predajne']);

                $stmt->execute();
                $this->db->commit();
                $tplVars['message'] = 'Product added successfully';

            } catch (PDOexception $e) {
                $tplVars['message'] = 'Error occured :(';
                $tplVars['formData'] = $formData;
                $this->logger->error($e->getMessage());
                $this->db->rollback();
            }
        }
        return $this->view->render($response, 'produkt-formular.latte', $tplVars);
    });


    /* UPDATE PRODUKT*/

    $app->get('/produkt/{cislo_artikla}/edit', function (Request $request, Response $response, $args) {
        if (!empty($args['cislo_artikla'])) {
            $stmt = $this->db->prepare('SELECT * FROM produkty 
                                        WHERE cislo_artikla = :cislo_artikla');
            $stmt->bindValue(':cislo_artikla', $args['cislo_artikla']);
            $stmt->execute();
            $tplVars['formData'] = $stmt->fetch();
            if (empty($tplVars['formData'])) {
                exit('produkt sa nenašiel');
            } else {
                $tplVars['header'] = 'Edit produktu';
                return $this->view->render($response, 'produkt-formular.latte', $tplVars);
            }
        }
        $stmt = $this->db->query('SELECT d.id_dod, d.nazov FROM dodavatelia d ORDER BY nazov ASC');
        $dodavatelia_list = $stmt->fetchAll();
        $tplVars['dodavatelia_list'] = $dodavatelia_list;

    })->setName('updateProdukt');


    /* UPDATE PRODUKT */
    $app->post('/produkt/{cislo_artikla}/edit', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (empty($formData['cislo_artikla']) || empty($formData['nazov']) || empty($formData['sezona']) ||
            empty($formData['pohlavie']) || empty($formData['druh']) || empty($formData['farba']) ||
            empty($formData['stav']) || empty($formData['znacka']) ||
            empty($formData['dodavatel']) || empty($formData['predajna_cena']) ||
            empty($formData['nakupna_cena']) || empty($formData['ean']) || empty($formData['predajnecislo_predajne'])){
            $tplVars['message'] = 'Please fill required fields';
        }
        try {
            $stmt = $this->db->prepare("UPDATE produkty SET 
                        cislo_artikla = :cislo_artikla,  
                        nazov = :nazov,
                        sezona = :sezona,
                        pohlavie = :pohlavie,
                        druh = :druh,
                        farba = :farba,
                        stav = :stav,
                        znacka = :znacka,
                        dodavatel = :dodavatel,
                        predajna_cena = :predajna_cena,
                        nakupna_cena = :nakupna_cena,
                        ean = :ean,
                        predajnecislo_predajne = :predajna
                    WHERE cislo_artikla = :cislo_artikla");
            $stmt->bindValue(':cislo_artikla', $args['cislo_artikla']);
            $stmt->bindValue(':nazov', $formData['nazov']);
            $stmt->bindValue(':sezona', $formData['sezona']);
            $stmt->bindValue(':pohlavie', empty($formData['pohlavie']) ? null : $formData['pohlavie']);
            $stmt->bindValue(':druh', $formData['druh']);
            $stmt->bindValue(':farba', $formData['farba']);
            $stmt->bindValue(':stav', $formData['stav']);
            $stmt->bindValue(':znacka', $formData['znacka']);
            $stmt->bindValue(':dodavatel', $formData['dodavatel']);
            $stmt->bindValue(':nakupna_cena', $formData['nakupna_cena']);
            $stmt->bindValue(':predajna_cena', $formData['predajna_cena']);
            $stmt->bindValue(':ean', $formData['ean']);
            $stmt->bindValue(':predajna', $formData['predajnecislo_predajne']);
            $stmt->execute();
            $tplVars['message'] = 'Produkt úspešne upravený!';

        } catch (PDOexception $e) {
            $tplVars['message'] = 'Error occured, sorry jako';
            $this->logger->error($e->getMessage());
        }
        $tplVars['formData'] = $formData;
        $tplVars['header'] = 'Uprav produkt';
        return $this->view->render($response, 'produkt-formular.latte', $tplVars);
    });

    /* DELETE PRODUKTU */
    $app->get('/produkt/{cislo_artikla}/delete', function (Request $request, Response $response, $args) {
        if (!empty($args['cislo_artikla'])) {
            try {
                $stmt = $this->db->prepare('DELETE FROM produkty WHERE cislo_artikla = :cislo_artikla');
                $stmt->bindValue(':cislo_artikla', $args['cislo_artikla']);
                $stmt->execute();

            } catch (PDOexception $e) {
                $this->logger->error($e->getMessage());
            }
        } else {
            exit('cislo artikla nenájdené');
        }

        return $response->withHeader('Location', $this->router->pathFor('produkty'));
    })->setName('deleteProdukt');

/* Vypis predajni */
$app->get('/predajne', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    $stmt = $this->db->prepare('SELECT p.cislo_predajne, p.nazov, string_agg(z.meno, \', \') AS zamestnanci 
                                FROM predajne p 
                                LEFT JOIN zamestnanci z ON p.cislo_predajne = z.predajnecislo_predajne 
                                GROUP BY p.cislo_predajne, p.nazov 
                                ORDER BY p.cislo_predajne'); # databazovy objekt, cursor
    $stmt->execute();
    $tplVars = [
        'predajne_list' => $stmt->fetchAll(),
    ];
    return $this->view->render($response, 'predajne.latte', $tplVars);
})->setName('predajne');

/* produkt pre predajnu ... */
    $app->get('/produkty/{cislo_predajne}', function (Request $request, Response $response, $args) {
        $cislo_predajne = $args['cislo_predajne'];

        // Fetch products for the given sales point
        $stmt = $this->db->prepare('SELECT * FROM produkty WHERE predajnecislo_predajne = :cislo_predajne');
        $stmt->bindValue(':cislo_predajne', $cislo_predajne);
        $stmt->execute();
        $produkty = $stmt->fetchAll();

        $tplVars['header'] = 'Produkty na predajni ' . $cislo_predajne;
        $tplVars['produkty'] = $produkty;

        return $this->view->render($response, 'produkty_predajna.latte', $tplVars);
    })->setName('priradProdukty');


$app->get('/zamestnanci', function (Request $request, Response $response, $args) {
    $stmt = $this->db->prepare('SELECT * FROM zamestnanci ORDER BY id_zam'); // Fetch all columns from zamestnanci table
    $stmt->execute();
    $zamestnanci_list = $stmt->fetchAll(); // Fetch all rows as an array

    $tplVars = [
        'zamestnanci_list' => $zamestnanci_list, // Pass the fetched data to the view
    ];
    return $this->view->render($response, 'zamestnanci.latte', $tplVars); // Render the view with the fetched data
})->setName('zamestnanci');


$app->get('/dodavatelia', function (Request $request, Response $response, $args) {
    $stmt = $this->db->prepare('SELECT id_dod, nazov, ico, kontakt FROM dodavatelia ORDER BY id_dod ASC');
    $stmt->execute();
    $tplVars['dodavatelia_list'] = $stmt->fetchAll();
    return $this->view->render($response, 'dodavatelia.latte', $tplVars);
})->setName('dodavatelia');

    /* fakturu pre dodavatela ... */
    $app->get('/faktury/{nazov}', function (Request $request, Response $response, $args) {
        $nazov = rawurldecode($args['nazov']);

        // Fetch products for the given sales point
        $stmt = $this->db->prepare('SELECT * FROM faktury f JOIN dodavatelia d 
                                    ON f.dodavateliaid_dod = d.id_dod  WHERE d.nazov = :nazov');
        $stmt->bindValue(':nazov', $nazov, PDO::PARAM_STR);
        $stmt->execute();
        $faktury = $stmt->fetchAll();

        if (!empty($faktury)) {
            $tplVars['faktury'] = $faktury;
            $tplVars['header'] = 'Faktúry pre dodavateľa ' . $nazov;
        } else {
            $tplVars['noFaktury'] = true;
            $tplVars['header'] = 'Žiadne faktúry pre dodávateľa ' . $nazov;
        }


        return $this->view->render($response, 'faktury-dodavatel.latte', $tplVars);
    })->setName('priradFaktury');

$app->get('/zakaznici', function (Request $request, Response $response, $args) {
    $stmt = $this->db->prepare('SELECT id_zak, meno, priezvisko, tel_cislo, email FROM zakaznici');
    $stmt->execute();
    $tplVars['zakaznici_list'] = $stmt->fetchAll();
    return $this->view->render($response, 'zakaznici.latte', $tplVars);
})->setName('zakaznici');


    /* Nacitanie formularu pre noveho zakaznika */
    $app->get('/zakaznik', function (Request $request, Response $response, $args) {
        $tplVars['header'] = 'Pridať zákazníka';
        $tplVars['formData'] = [
            'meno' => '',
            'priezvisko' => '',
            'tel_cislo' => '',
            'email' => ''
        ];
        return $this->view->render($response, 'zakaznik-formular.latte', $tplVars);
    })->setName('novyZakaznik');

    /* Post novy zakaznik */
    $app->post('/zakaznik', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (empty($formData['meno']) || empty($formData['priezvisko']) ||
            empty($formData['tel_cislo']) || empty($formData['email'])) {
            $tplVars['message'] = 'Please fill required fields';
        } else {
            try {
                $this->db->beginTransaction();

                // Fetch last used id_zak value
                $stmt = $this->db->query('SELECT MAX(id_zak) as max_id FROM zakaznici');
                $result = $stmt->fetch();
                $lastId = $result['max_id'];
                $newId = $lastId + 1;

                $stmt = $this->db->prepare('INSERT INTO zakaznici (id_zak, meno, priezvisko, tel_cislo, email)
                                              VALUES (:id_zak, :meno, :priezvisko, :tel_cislo, :email)');
                $stmt->bindValue(':id_zak', $newId);
                $stmt->bindValue(':meno', $formData['meno']);
                $stmt->bindValue(':priezvisko', $formData['priezvisko']);
                $stmt->bindValue(':tel_cislo', $formData['tel_cislo']);
                $stmt->bindValue(':email', $formData['email']);

                $stmt->execute();
                $this->db->commit();
                $tplVars['message'] = 'Zakaznik úspešne pridaný!';

            } catch (PDOexception $e) {
                $tplVars['message'] = 'Error occured :(';
                $tplVars['formData'] = $formData;
                $this->logger->error($e->getMessage());
                $this->db->rollback();
            }
        }
        return $this->view->render($response, 'zakaznik-formular.latte', $tplVars);
    });

    /* Výpis faktur */
    $app->get('/faktury', function (Request $request, Response $response, $args) {
        $stmt = $this->db->prepare('SELECT faktury.*, dodavatelia.nazov 
                                FROM faktury 
                                LEFT JOIN dodavatelia ON faktury.dodavateliaid_dod = id_dod');
        $stmt->execute();
        $tplVars['faktury_list'] = $stmt->fetchAll();
        return $this->view->render($response, 'faktury.latte', $tplVars);
    })->setName('faktury');

    /* NOVÁ FAKTURA */
    $app->get('/faktura', function (Request $request, Response $response, $args) {
        $tplVars['header'] = 'Nová faktúra';
        $tplVars['formData'] = [
            'nazov_fakt' => '',
            'datum_splatnosti' => '',
            'datum_evidencie' => '',
            'suma' => '',
            'vyplatene' => '',
            'datum_dodania' => '',
            'poznamka' => '',
            'dodavateliaid_dod' => ''
        ];
        $stmt = $this->db->query('SELECT d.id_dod, d.nazov FROM dodavatelia d ORDER BY nazov ASC');
        $dodavatelia_list = $stmt->fetchAll();
        $tplVars['dodavatelia_list'] = $dodavatelia_list;

        return $this->view->render($response, 'faktura-formular.latte', $tplVars);
    })->setName('novaFaktura');

    /* Post nova faktura */
    $app->post('/faktura', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (
            empty($formData['nazov_fakt']) ||
            empty($formData['datum_splatnosti']) ||
            empty($formData['datum_evidencie']) ||
            empty($formData['suma']) ||
            empty($formData['vyplatene']) ||
            empty($formData['datum_dodania']) ||
            empty($formData['poznamka']) ||
            empty($formData['dodavateliaid_dod'])) {
            $tplVars['message'] = 'Please fill required fields';
        } else {
            try {
                $this->db->beginTransaction();

                $stmt = $this->db->query('SELECT MAX(cislo_fakt) as max_cislo FROM faktury');
                $result = $stmt->fetch();
                $lastId = $result['max_cislo'];
                $newId = $lastId + 1;

                $dph = $formData['suma'] * 0.2;
                $bez_dph = $formData['suma'] - $dph;
                $rozdiel = $formData['suma'] - $formData['vyplatene'];

                $stmt = $this->db->prepare('INSERT INTO faktury (cislo_fakt, nazov_fakt, datum_splatnosti, datum_evidencie, suma, bez_dph, dph, vyplatene, rozdiel, datum_dodania, poznamka, dodavateliaid_dod)
                                      VALUES (:cislo_fakt, :nazov_fakt, :datum_splatnosti, :datum_evidencie, :suma, :bez_dph, :dph, :vyplatene, :rozdiel, :datum_dodania, :poznamka, :dodavateliaid_dod');
                $stmt->bindValue(':cislo_fakt', $newId);
                $stmt->bindValue(':nazov_fakt', $formData['nazov_fakt']);
                $stmt->bindValue(':datum_splatnosti', $formData['datum_splatnosti']);
                $stmt->bindValue(':datum_evidencie', $formData['datum_evidencie']);
                $stmt->bindValue(':suma', $formData['suma']);
                $stmt->bindValue(':bez_dph', $bez_dph );
                $stmt->bindValue(':dph', $dph);
                $stmt->bindValue(':vyplatene', $formData['vyplatene']);
                $stmt->bindValue(':rozdiel', $rozdiel);
                $stmt->bindValue(':datum_dodania', $formData['datum_dodania']);
                $stmt->bindValue(':poznamka', $formData['poznamka']);
                $stmt->bindValue(':dodavateliaid_dod', $formData['dodavateliaid_dod']);

                $stmt->execute();
                $this->db->commit();
                $tplVars['message'] = 'Faktúra úspešne pridaná!';

            } catch (PDOexception $e) {
                $tplVars['message'] = 'Error occured :(';
                $tplVars['formData'] = $formData;
                $this->logger->error($e->getMessage());
                $this->db->rollback();
            }
        }

        return $this->view->render($response, 'faktura-formular.latte', $tplVars);
    });


    /* UPDATE FAKTURA*/
    $app->get('/faktury/{cislo_fakt}/edit', function (Request $request, Response $response, $args) {
        if (!empty($args['cislo_fakt'])) {
            $stmt = $this->db->prepare('SELECT * FROM faktury 
                                        WHERE cislo_fakt = :cislo_fakt');
            $stmt->bindValue(':cislo_fakt', $args['cislo_fakt']);
            $stmt->execute();
            $tplVars['formData'] = $stmt->fetch();

            if (empty($tplVars['formData'])) {
                exit('faktúra sa nenašla');
            } else {
                $tplVars['header'] = 'Edit faktúry';

                $stmt = $this->db->query('SELECT d.id_dod, d.nazov FROM dodavatelia d ORDER BY id_dod');
                $dodavatelia_list = $stmt->fetchAll();
                $tplVars['dodavatelia_list'] = $dodavatelia_list;

                return $this->view->render($response, 'faktura-edit.latte', $tplVars);
            }
        }
    })->setName('updateFaktura');


    /* UPDATE FAKTURA */
    $app->post('/faktury/{cislo_fakt}/edit', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (empty($formData['cislo_fakt']) ||
            empty($formData['nazov_fakt']) ||
            empty($formData['datum_splatnosti']) ||
            empty($formData['datum_evidencie']) ||
            empty($formData['suma']) ||
            empty($formData['bez_dph']) ||
            empty($formData['dph']) ||
            empty($formData['vyplatene']) ||
            empty($formData['rozdiel']) ||
            empty($formData['datum_dodania']) ||
            empty($formData['poznamka']) ||
            empty($formData['dodavateliaid_dod'])) {
            $tplVars['message'] = 'Please fill required fields';
        }
        try {
            $dph = $formData['suma'] * 0.2;
            $bez_dph = $formData['suma'] - $dph;
            $rozdiel = $formData['suma'] - $formData['vyplatene'];

            $stmt = $this->db->prepare("UPDATE faktury SET 
                        cislo_fakt = :cislo_fakt,  
                        nazov_fakt = :nazov_fakt,
                        datum_splatnosti = :datum_splatnosti,
                        datum_evidencie = :datum_evidencie,
                        suma = :suma,
                        bez_dph = :bez_dph,
                        dph = :dph,
                        vyplatene = :vyplatene,
                        rozdiel = :rozdiel,
                        datum_dodania = :datum_dodania,
                        poznamka = :poznamka,
                        dodavateliaid_dod = :dodavateliaid_dod
                    WHERE cislo_fakt = :cislo_fakt");

            $stmt->bindValue(':cislo_fakt', $formData['cislo_fakt']);
            $stmt->bindValue(':nazov_fakt', $formData['nazov_fakt']);
            $stmt->bindValue(':datum_splatnosti', $formData['datum_splatnosti']);
            $stmt->bindValue(':datum_evidencie', $formData['datum_evidencie']);
            $stmt->bindValue(':suma', $formData['suma']);
            $stmt->bindValue(':bez_dph', $bez_dph );
            $stmt->bindValue(':dph', $dph);
            $stmt->bindValue(':vyplatene', $formData['vyplatene']);
            $stmt->bindValue(':rozdiel', $rozdiel);
            $stmt->bindValue(':datum_dodania', $formData['datum_dodania']);
            $stmt->bindValue(':poznamka', $formData['poznamka']);
            $stmt->bindValue(':dodavateliaid_dod', $formData['dodavateliaid_dod']);
            $stmt->execute();
            $tplVars['message'] = 'Faktúra úspešne upravená!';

        } catch (PDOexception $e) {
            $tplVars['message'] = 'Error occured, sorry jako';
            $this->logger->error($e->getMessage());
        }
        $tplVars['formData'] = $formData;
        $tplVars['header'] = 'Uprav faktúru';
        return $this->view->render($response, 'faktura-edit.latte', $tplVars);

    });

    /* Výpis objednavok */
    $app->get('/objednavky', function (Request $request, Response $response, $args) {
        $stmt = $this->db->prepare('SELECT * FROM objednavky');
        $stmt->execute();
        $tplVars['objednavky_list'] = $stmt->fetchAll();
        return $this->view->render($response, 'objednavky.latte', $tplVars);
    })->setName('objednavky');

    /* NOVÁ Objednavka */
    $app->get('/objednavka', function (Request $request, Response $response, $args) {
        $tplVars['header'] = 'Nová objednávka';
        $tplVars['formData'] = [
            'pocet_kusov' => '',
            'pocet_artiklov' => '',
            'suma' => '',
            'datum_vystavenia' => '',
            'popis' => '',
            'zaplatene' => '',
            'prijate' => '',
            'dodavatelianazov_dod' => ''
        ];
        $stmt = $this->db->query('SELECT d.id_dod, d.nazov FROM dodavatelia d ORDER BY d.nazov');
        $dodavatelia_list = $stmt->fetchAll();
        $tplVars['dodavatelia_list'] = $dodavatelia_list;

        return $this->view->render($response, 'objednavka-formular.latte', $tplVars);
    })->setName('novaObjednavka');

    /* Post nova objednavka */
    $app->post('/objednavka', function (Request $request, Response $response, $args) {
        $formData = $request->getParsedBody();
        $tplVars = [];
        if (
            empty($formData['pocet_kusov']) ||
            empty($formData['pocet_artiklov']) ||
            empty($formData['suma']) ||
            empty($formData['datum_vystavenia']) ||
            empty($formData['popis']) ||
            empty($formData['zaplatene']) ||
            empty($formData['prijate']) ||
            empty($formData['dodavatelianazov_dod'])) {
            $tplVars['message'] = 'Please fill required fields';
        } else {
            try {
                $this->db->beginTransaction();

                $stmt = $this->db->query('SELECT MAX(cislo_id) as max_cislo FROM objednavky');
                $result = $stmt->fetch();
                $lastId = $result['max_cislo'];
                $newId = $lastId + 1;

                $stmt = $this->db->prepare('SELECT id_dod FROM dodavatelia WHERE nazov = :nazov');
                $stmt->bindValue(':nazov', $formData['dodavatelianazov_dod']);
                $stmt->execute();
                $result = $stmt->fetch();
                $dodavatelId = $result['id_dod'];

                $stmt = $this->db->prepare('INSERT INTO objednavky (cislo_id, pocet_kusov, pocet_artiklov, suma, datum_vystavenia, popis, zaplatene, prijate, dodavatelianazov_dod)
                                      VALUES (:cislo_id, :pocet_kusov, :pocet_artiklov, :suma, :datum_vystavenia, :popis, :zaplatene, :prijate, :dodavatelianazov_dod)');
                $stmt->bindValue(':cislo_id', $newId);
                $stmt->bindValue(':pocet_kusov', $formData['pocet_kusov']);
                $stmt->bindValue(':pocet_artiklov', $formData['pocet_artiklov']);
                $stmt->bindValue(':suma', $formData['suma']);
                $stmt->bindValue(':datum_vystavenia', $formData['datum_vystavenia']);
                $stmt->bindValue(':popis', $formData['popis']);
                $stmt->bindValue(':zaplatene', $formData['zaplatene']);
                $stmt->bindValue(':prijate', $formData['prijate']);
                $stmt->bindValue(':dodavatelianazov_dod', $formData['dodavatelianazov_dod']);

                $stmt->execute();
                $this->db->commit();
                $tplVars['message'] = 'Objednávka úspešne pridaná!';

            } catch (PDOexception $e) {
                $tplVars['message'] = 'Error occured :(';
                $tplVars['formData'] = $formData;
                $this->logger->error($e->getMessage());
                $this->db->rollback();
            }
        }
        return $this->view->render($response, 'objednavka-formular.latte', $tplVars);
    });



    /* Výpis objednavok */
    $app->get('/objednavky/nezaplatene', function (Request $request, Response $response, $args) {
        $stmt = $this->db->prepare('SELECT * FROM objednavky WHERE zaplatene=false ');
        $stmt->execute();
        $tplVars['objednavky_list'] = $stmt->fetchAll();
        return $this->view->render($response, 'objednavky.latte', $tplVars);
    })->setName('nezaplateneObjednavky');



    /* produkt pre objednavku ... */
    $app->get('/objednavky/produkty/{cislo_id}', function (Request $request, Response $response, $args) {
        $cislo_id = $args['cislo_id'];

        // Fetch products for the given sales point
        $stmt = $this->db->prepare('SELECT * FROM produkty WHERE objednavkycislo_obj = :cislo_id');
        $stmt->bindValue(':cislo_id', $cislo_id);
        $stmt->execute();
        $produkty = $stmt->fetchAll();

        $tplVars['header'] = 'Produkty z objednávky ' . $cislo_id;
        $tplVars['produkty'] = $produkty;

        return $this->view->render($response, 'produkty_predajna.latte', $tplVars);
    })->setName('priradenieProduktov');




# Kontrolovanie cookie
})->add(function($request, $response, $next) {
    # Vynutime si autentizaciu
    if (empty($_COOKIE['token']) || !validateToken($_COOKIE['token'], $this->db) ) {
        return $response->withHeader('Location', $this->router->pathFor('login'));
    } else {
        return $next($request, $response);
    }
});