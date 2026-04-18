<?php
$categories = [
    [
        'id'            => 'electronice',
        'name'          => 'Electronice & Electrocasnice',
        'icon'          => '📱',
        'subcategories' => ['Telefoane', 'Laptopuri & PC', 'Tablete', 'TV & Audio',
                            'Electrocasnice mari', 'Camere foto', 'Console jocuri', 'Alte electronice'],
    ],
    [
        'id'            => 'auto',
        'name'          => 'Auto, Moto & Ambarcatiuni',
        'icon'          => '🚗',
        'subcategories' => ['Autoturisme', 'Motociclete', 'Camioane & Utilitare',
                            'Rulote', 'Piese auto', 'Barci & Ambarcatiuni'],
    ],
    [
        'id'            => 'imobiliare',
        'name'          => 'Imobiliare',
        'icon'          => '🏠',
        'subcategories' => ['Apartamente vanzare', 'Apartamente inchiriere',
                            'Case vanzare', 'Case inchiriere', 'Terenuri', 'Spatii comerciale'],
    ],
    [
        'id'            => 'fashion',
        'name'          => 'Moda & Frumusete',
        'icon'          => '👗',
        'subcategories' => ['Haine barbati', 'Haine femei', 'Haine copii',
                            'Incaltaminte', 'Accesorii', 'Cosmetice & Parfumuri'],
    ],
    [
        'id'            => 'casa',
        'name'          => 'Casa & Gradina',
        'icon'          => '🛋️',
        'subcategories' => ['Mobila', 'Decoratiuni', 'Scule & Unelte',
                            'Gradina', 'Iluminat', 'Electrocasnice mici'],
    ],
    [
        'id'            => 'sport',
        'name'          => 'Sport & Timp Liber',
        'icon'          => '⚽',
        'subcategories' => ['Biciclete', 'Fitness', 'Camping & Drumetii',
                            'Sporturi de apa', 'Arte martiale', 'Sporturi de iarna'],
    ],
    [
        'id'            => 'animale',
        'name'          => 'Animale de Companie',
        'icon'          => '🐾',
        'subcategories' => ['Caini', 'Pisici', 'Pasari', 'Pesti',
                            'Accesorii animale', 'Hrana animale'],
    ],
    [
        'id'            => 'copii',
        'name'          => 'Mama & Copil',
        'icon'          => '👶',
        'subcategories' => ['Jucarii', 'Imbracaminte copii', 'Carucioare',
                            'Mobilier copii', 'Scoala & Educatie'],
    ],
    [
        'id'            => 'servicii',
        'name'          => 'Servicii & Afaceri',
        'icon'          => '🔧',
        'subcategories' => ['Servicii IT', 'Constructii', 'Transport',
                            'Curatenie', 'Meditatii', 'Echipamente industriale'],
    ],
    [
        'id'            => 'agro',
        'name'          => 'Agro & Industrie',
        'icon'          => '🌾',
        'subcategories' => ['Utilaje agricole', 'Animale de ferma',
                            'Seminte & Plante', 'Lemne & Combustibili'],
    ],
];

jsonSuccess($categories);
