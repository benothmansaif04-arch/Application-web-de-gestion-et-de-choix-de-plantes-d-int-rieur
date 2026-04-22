-- ============================================
-- BASE DE DONNEES : PlantApp - VERSION CORRIGEE
-- ENSIT PFA1 2025-2026 -- Sail Ben Ali
-- Encadrante : Yasmine Amor
-- SGBD : MySQL (phpMyAdmin / XAMPP)
-- ============================================

DROP DATABASE IF EXISTS plantes_db;
CREATE DATABASE plantes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE plantes_db;

CREATE TABLE Utilisateur (
    id_utilisateur   INT AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    role             ENUM('particulier','botaniste','vendeur') NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE Particulier (
    id_utilisateur         INT PRIMARY KEY,
    localisation           VARCHAR(150),
    niveau_experience      ENUM('debutant','intermediaire','expert') DEFAULT 'debutant',
    disponibilite_arrosage ENUM('quotidien','hebdomadaire','bimensuel') DEFAULT 'hebdomadaire',
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur) ON DELETE CASCADE
);
CREATE TABLE Botaniste (
    id_utilisateur INT PRIMARY KEY,
    specialite     VARCHAR(150),
    institution    VARCHAR(150),
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur) ON DELETE CASCADE
);
CREATE TABLE Vendeur (
    id_utilisateur   INT PRIMARY KEY,
    nom_boutique     VARCHAR(150),
    adresse_boutique VARCHAR(200),
    telephone        VARCHAR(20),
    solde            DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur(id_utilisateur) ON DELETE CASCADE
);
CREATE TABLE Famille (
    id_famille  INT AUTO_INCREMENT PRIMARY KEY,
    nom_famille VARCHAR(100) NOT NULL,
    description TEXT
);
CREATE TABLE Plante (
    id_plante         INT AUTO_INCREMENT PRIMARY KEY,
    nom_commun        VARCHAR(150) NOT NULL,
    nom_scientifique  VARCHAR(200),
    id_famille        INT,
    description       TEXT,
    toxicite          TINYINT(1) DEFAULT 0,
    niveau_soin       ENUM('facile','moyen','difficile') DEFAULT 'facile',
    besoin_arrosage   ENUM('quotidien','hebdomadaire','bimensuel') DEFAULT 'hebdomadaire',
    besoin_luminosite ENUM('faible','moyenne','forte') DEFAULT 'moyenne',
    besoin_humidite   ENUM('faible','moyenne','elevee') DEFAULT 'moyenne',
    temperature_min   DECIMAL(4,1) DEFAULT 15.0,
    temperature_max   DECIMAL(4,1) DEFAULT 30.0,
    image_url         VARCHAR(500),
    id_botaniste      INT,
    date_ajout        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_famille)   REFERENCES Famille(id_famille),
    FOREIGN KEY (id_botaniste) REFERENCES Botaniste(id_utilisateur)
);
CREATE TABLE EspacePiece (
    id_espace          INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur     INT NOT NULL,
    nom_piece          VARCHAR(100) NOT NULL,
    type_piece         ENUM('salon','chambre','bureau','cuisine','salle_bain','balcon','autre') DEFAULT 'salon',
    luminosite         ENUM('faible','moyenne','forte') DEFAULT 'moyenne',
    taille             ENUM('petite','moyenne','grande') DEFAULT 'moyenne',
    humidite           ENUM('faible','moyenne','elevee') DEFAULT 'moyenne',
    presence_animaux   TINYINT(1) DEFAULT 0,
    temperature_approx DECIMAL(4,1) DEFAULT 20.0,
    description        TEXT,
    date_ajout         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES Particulier(id_utilisateur) ON DELETE CASCADE
);
CREATE TABLE Pathologie (
    id_pathologie  INT AUTO_INCREMENT PRIMARY KEY,
    id_plante      INT NOT NULL,
    nom_pathologie VARCHAR(150) NOT NULL,
    symptomes      TEXT,
    traitement     TEXT,
    FOREIGN KEY (id_plante) REFERENCES Plante(id_plante) ON DELETE CASCADE
);
CREATE TABLE Catalogue (
    id_catalogue INT AUTO_INCREMENT PRIMARY KEY,
    id_vendeur   INT NOT NULL,
    id_plante    INT NOT NULL,
    prix         DECIMAL(10,2) NOT NULL,
    stock        INT DEFAULT 0,
    variete      VARCHAR(100),
    disponible   TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_vendeur) REFERENCES Vendeur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_plante)  REFERENCES Plante(id_plante) ON DELETE CASCADE
);
CREATE TABLE Accessoire (
    id_accessoire  INT AUTO_INCREMENT PRIMARY KEY,
    id_vendeur     INT NOT NULL,
    nom_accessoire VARCHAR(150) NOT NULL,
    description    TEXT,
    prix           DECIMAL(10,2),
    stock          INT DEFAULT 0,
    image_url      VARCHAR(500),
    FOREIGN KEY (id_vendeur) REFERENCES Vendeur(id_utilisateur) ON DELETE CASCADE
);
CREATE TABLE ListeSouhaits (
    id_liste       INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_plante      INT NOT NULL,
    date_ajout     DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (id_utilisateur, id_plante),
    FOREIGN KEY (id_utilisateur) REFERENCES Particulier(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_plante)      REFERENCES Plante(id_plante) ON DELETE CASCADE
);
CREATE TABLE Commande (
    id_commande       INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur    INT NOT NULL,
    id_vendeur        INT NOT NULL,
    id_plante         INT DEFAULT 0,
    id_accessoire     INT DEFAULT 0,
    type_commande     ENUM('plante','accessoire') NOT NULL,
    quantite          INT DEFAULT 1,
    prix_total        DECIMAL(10,2) NOT NULL,
    nom_carte         VARCHAR(150),
    numero_carte      VARCHAR(20),
    date_expiration   VARCHAR(7),
    adresse_livraison VARCHAR(200),
    ville             VARCHAR(100),
    code_postal       VARCHAR(10),
    statut            ENUM('en_attente','confirme','livre') DEFAULT 'en_attente',
    date_commande     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES Particulier(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_vendeur)     REFERENCES Vendeur(id_utilisateur) ON DELETE CASCADE
);

-- FAMILLE
INSERT INTO Famille (nom_famille, description) VALUES
('Araceae','Famille des arums, pothos et spathiphyllum'),
('Cactaceae','Cactus et plantes succulentes'),
('Asparagaceae','Sansevieria, asparagus et aloes'),
('Moraceae','Famille des ficus et muriers'),
('Orchidaceae','Famille des orchidees'),
('Lamiaceae','Famille des plantes aromatiques'),
('Araliaceae','Famille du schefflera et du lierre'),
('Marantaceae','Famille des calathea et marantas'),
('Arecaceae','Famille des palmiers'),
('Begoniaceae','Famille des begonias'),
('Bromeliaceae','Famille des bromeliaces'),
('Crassulaceae','Famille des plantes grasses'),
('Euphorbiaceae','Famille des euphorbes et crotons'),
('Urticaceae','Famille des ficus pumila et pilea'),
('Acanthaceae','Famille des aphelandra et fittonia');

-- UTILISATEUR
INSERT INTO Utilisateur (nom, prenom, email, mot_de_passe, role) VALUES
('Ben Ali','Sami','sami@email.com',MD5('1234'),'particulier'),
('Amor','Yasmine','yasmine@email.com',MD5('1234'),'botaniste'),
('Trabelsi','Karim','karim@email.com',MD5('1234'),'vendeur'),
('Mabrouk','Leila','leila@email.com',MD5('1234'),'particulier'),
('Chebbi','Amine','amine@email.com',MD5('1234'),'botaniste'),
('Gharbi','Nadia','nadia@email.com',MD5('1234'),'vendeur'),
('Khemiri','Rami','rami@email.com',MD5('1234'),'particulier'),
('Boukadida','Sonia','sonia@email.com',MD5('1234'),'botaniste'),
('Meddeb','Tarek','tarek@email.com',MD5('1234'),'vendeur'),
('Jebali','Hana','hana@email.com',MD5('1234'),'particulier'),
('Ferchichi','Mehdi','mehdi@email.com',MD5('1234'),'particulier'),
('Hammami','Rim','rim@email.com',MD5('1234'),'particulier'),
('Nasri','Walid','walid@email.com',MD5('1234'),'vendeur'),
('Boughanmi','Sara','sara@email.com',MD5('1234'),'botaniste'),
('Tlili','Omar','omar@email.com',MD5('1234'),'particulier'),
('Chaabane','Fatma','fatma@email.com',MD5('1234'),'particulier');

-- PARTICULIER
INSERT INTO Particulier (id_utilisateur, localisation, niveau_experience, disponibilite_arrosage) VALUES
(1,'Tunis','debutant','hebdomadaire'),
(4,'Sfax','intermediaire','quotidien'),
(7,'Sousse','expert','quotidien'),
(10,'Tunis','debutant','bimensuel'),
(11,'Bizerte','intermediaire','hebdomadaire'),
(12,'Nabeul','debutant','hebdomadaire'),
(15,'Monastir','expert','quotidien'),
(16,'Tunis','intermediaire','hebdomadaire');

-- BOTANISTE
INSERT INTO Botaniste (id_utilisateur, specialite, institution) VALUES
(2,'Botanique tropicale','ENSIT Tunis'),
(5,'Plantes medicinales','Universite de Tunis'),
(8,'Ecologie vegetale','INAT Tunis'),
(14,'Horticulture ornementale','Ecole de Sfax');

-- VENDEUR
INSERT INTO Vendeur (id_utilisateur, nom_boutique, adresse_boutique, telephone, solde) VALUES
(3,'Plantes et Co','Avenue Habib Bourguiba, Tunis','+216 71 000 111',245.50),
(6,'Jardin Vert','Rue de Marseille, Sfax','+216 74 000 222',180.00),
(9,'Flora Tunisie','Avenue de la Liberte, Sousse','+216 73 000 333',97.75),
(13,'Le Coin Vert','Rue Ibn Khaldoun, Monastir','+216 73 111 444',320.00);

-- PLANTE  (image_url = noms exacts des fichiers dans images/)
INSERT INTO Plante (nom_commun, nom_scientifique, id_famille, description, toxicite, niveau_soin, besoin_arrosage, besoin_luminosite, besoin_humidite, temperature_min, temperature_max, image_url, id_botaniste) VALUES
('Pothos','Epipremnum aureum',1,'Plante grimpante tres resistante, ideale pour les debutants.',1,'facile','hebdomadaire','faible','moyenne',15,30,'images/pothos.jpg',2),
('Aloe vera','Aloe barbadensis',2,'Plante medicinale aux multiples vertus therapeutiques.',0,'facile','bimensuel','forte','faible',13,35,'images/aloe_vera.jpg',5),
('Sansevieria','Sansevieria trifasciata',3,'Plante quasi indestructible qui purifie l air meme la nuit.',1,'facile','bimensuel','faible','faible',15,35,'images/sansevieria.jpg',2),
('Ficus','Ficus benjamina',4,'Arbre d interieur elegant aux feuilles brillantes retombantes.',0,'moyen','hebdomadaire','forte','moyenne',18,30,'images/ficus.jpg',2),
('Orchidee','Phalaenopsis amabilis',5,'Reine des plantes d interieur aux fleurs spectaculaires.',0,'difficile','hebdomadaire','moyenne','elevee',18,28,'images/orchidee.jpg',2),
('Monstera','Monstera deliciosa',1,'Plante tropicale iconique aux grandes feuilles perforees.',0,'moyen','hebdomadaire','moyenne','elevee',18,30,'images/monstera.jpg',5),
('Lavande','Lavandula angustifolia',6,'Plante aromatique aux fleurs mauves parfumees.',0,'facile','hebdomadaire','forte','faible',10,28,'images/lavande.jpg',5),
('Cactus boule','Echinopsis tubiflora',2,'Cactus spherique resistant pouvant produire de belles fleurs.',0,'facile','bimensuel','forte','faible',10,35,'images/cactus_boule.jpg',2),
('Schefflera','Schefflera arboricola',7,'Arbuste tropical aux feuilles en parapluie d un vert brillant.',0,'facile','hebdomadaire','moyenne','moyenne',15,30,'images/schefflera.jpg',5),
('Calathea','Calathea ornata',8,'Plante aux feuilles ornees de rayures roses et vertes.',0,'difficile','hebdomadaire','faible','elevee',18,28,'images/calathea.jpg',2),
('Palmier Areca','Dypsis lutescens',9,'Palmier d interieur qui humidifie l air naturellement.',0,'moyen','hebdomadaire','forte','elevee',16,32,'images/palmier_areca.jpg',2),
('Begonia','Begonia rex',10,'Plante aux feuilles colorees spectaculaires tres decorative.',0,'moyen','hebdomadaire','moyenne','elevee',15,28,'images/begonia.jpg',5),
('Ficus Lyrata','Ficus lyrata',4,'Ficus aux grandes feuilles en forme de violon tres apprecie.',0,'moyen','hebdomadaire','forte','moyenne',18,30,'images/ficus_lyrata.jpg',8),
('Cactus Opuntia','Opuntia microdasys',2,'Cactus raquette decoratif produisant de belles fleurs jaunes.',0,'facile','bimensuel','forte','faible',8,38,'images/cactus_opuntia.jpg',5),
('Spathiphyllum','Spathiphyllum wallisii',1,'Plante aux fleurs blanches qui purifie l air.',1,'facile','hebdomadaire','faible','elevee',15,30,'images/spathiphyllum.jpg',5),
('Tillandsia','Tillandsia ionantha',11,'Plante aerienne ne necessitant pas de sol.',0,'facile','hebdomadaire','forte','elevee',15,30,'images/spathiphyllum.jpg',8),
('Echeveria','Echeveria elegans',12,'Succulente en forme de rosette aux feuilles bleu-vert.',0,'facile','bimensuel','forte','faible',10,35,'images/aloe_vera.jpg',8),
('Croton','Codiaeum variegatum',13,'Plante aux feuilles multicolores tres decoratives.',1,'moyen','hebdomadaire','forte','elevee',18,30,'images/monstera.jpg',14),
('Pilea','Pilea peperomioides',14,'Plante soleil en forme de pieces rondes, tres tendance.',0,'facile','hebdomadaire','moyenne','moyenne',15,28,'images/pothos.jpg',14),
('Fittonia','Fittonia albivenis',15,'Plante aux feuilles nervurees blanc et rouge.',0,'difficile','quotidien','faible','elevee',18,28,'images/calathea.jpg',14);

-- ESPACE PIECE
INSERT INTO EspacePiece (id_utilisateur, nom_piece, type_piece, luminosite, taille, humidite, presence_animaux, temperature_approx, description) VALUES
(1,'Salon principal','salon','moyenne','grande','moyenne',0,22.0,'Grande piece avec fenetres au sud'),
(1,'Chambre a coucher','chambre','faible','moyenne','faible',0,19.0,'Piece calme et peu lumineuse'),
(1,'Bureau maison','bureau','forte','petite','faible',0,21.0,'Beaucoup de lumiere naturelle'),
(4,'Salon Sfax','salon','forte','grande','faible',1,24.0,'Salon ensoleille avec chat'),
(4,'Cuisine','cuisine','moyenne','petite','elevee',0,23.0,'Cuisine avec evier'),
(7,'Balcon ensoleille','balcon','forte','petite','faible',0,25.0,'Balcon plein sud tres lumineux'),
(7,'Salle de bain','salle_bain','faible','petite','elevee',0,24.0,'Salle de bain avec buee'),
(7,'Salon Sousse','salon','moyenne','grande','moyenne',1,22.0,'Salon avec chien'),
(10,'Chambre enfant','chambre','moyenne','moyenne','moyenne',0,20.0,'Chambre douce et lumineuse'),
(11,'Salon Bizerte','salon','forte','grande','faible',0,23.0,'Vue mer, tres lumineux'),
(11,'Bureau','bureau','faible','moyenne','faible',0,20.0,'Bureau de travail peu eclaire'),
(12,'Salon Nabeul','salon','forte','moyenne','moyenne',1,22.0,'Salon avec perroquet'),
(15,'Terrasse couverte','balcon','forte','grande','faible',0,26.0,'Terrasse couverte tres ensoleilee'),
(15,'Chambre principale','chambre','faible','grande','faible',0,18.0,'Grande chambre peu eclairee'),
(16,'Studio Tunis','salon','moyenne','petite','moyenne',0,21.0,'Petit studio central'),
(16,'Cuisine studio','cuisine','faible','petite','elevee',0,22.0,'Petite cuisine integree');

-- PATHOLOGIE
INSERT INTO Pathologie (id_plante, nom_pathologie, symptomes, traitement) VALUES
(1,'Pourriture des racines','Feuilles jaunissantes, tiges molles','Reduire arrosage, rempoter dans substrat frais'),
(2,'Acariens','Fines toiles sous les feuilles','Vaporiser de l eau, appliquer acaricide naturel'),
(3,'Pourriture basale','Base molle et decoloree','Supprimer parties atteintes, reduire arrosage'),
(4,'Chute des feuilles','Feuilles tombant soudainement','Eviter courants d air, ne pas deplacer la plante'),
(5,'Manque de floraison','Pas de fleurs, feuilles pales','Augmenter luminosite indirecte, apporter engrais'),
(6,'Taches foliaires','Taches brunes ou noires sur les feuilles','Reduire humidite, appliquer fongicide naturel'),
(7,'Cochenilles','Points blancs cotonneux sur les tiges','Alcool isopropylique dilue sur coton'),
(8,'Exces d eau','Racines noires, plante qui s affaisse','Stopper arrosage, laisser secher le substrat'),
(10,'Feuilles enroulees','Feuilles qui s enroulent sur elles-memes','Augmenter humidite, vaporiser regulierement'),
(9,'Pointes brunes','Extremites des feuilles brunes et seches','Augmenter humidite, eviter l air sec'),
(11,'Jaunissement des feuilles','Feuilles qui jaunissent progressivement','Verifier arrosage et luminosite, apporter engrais'),
(12,'Oedeme foliaire','Boursouflures sur les feuilles','Reduire arrosage, ameliorer la ventilation'),
(13,'Brulures foliaires','Taches claires ou brulees sur les feuilles','Eloigner de la lumiere directe'),
(14,'Etiolement','Tiges longues et fines, feuilles petites','Rapprocher de la source de lumiere'),
(15,'Fusariose','Feuilles qui fletrissent malgre arrosage','Traitement fongicide, rempoter en sol sterilise'),
(16,'Dessechement','Feuilles qui se recroquevillent et tombent','Augmenter arrosage et humidite ambiante'),
(17,'Pucerons','Petits insectes verts sur les jeunes pousses','Savon noir dilue, insecticide biologique'),
(18,'Chlorose','Feuilles jaunes avec nervures vertes','Apport de fer chelate, ajuster le pH du sol'),
(19,'Pourriture grise','Moisissure grise sur les feuilles','Supprimer parties atteintes, traitement fongicide'),
(20,'Carence en azote','Feuilles palissant du bas vers le haut','Apporter engrais azote, compost dilue');

-- CATALOGUE
INSERT INTO Catalogue (id_vendeur, id_plante, prix, stock, variete, disponible) VALUES
(3,1,12.99,25,'Golden Pothos',1),(3,2,9.99,30,'Vera',1),(3,3,15.00,20,'Laurentii',1),
(3,4,35.00,10,'Exotica',1),(3,5,22.00,15,'Rose',1),(3,6,28.00,12,'Deliciosa',1),
(3,7,11.50,18,'Angustifolia',1),(3,8,8.50,40,'Standard',1),
(6,9,19.99,14,'Arboricola',1),(6,10,24.99,8,'Ornata',1),(6,11,39.00,7,'Lutescens',1),
(6,12,16.00,20,'Rex',1),(6,13,45.00,6,'Bambino',1),(6,14,12.00,35,'Microdasys',1),
(3,15,14.50,22,'Wallisii',1),(9,16,18.00,10,'Ionantha',1),(9,17,7.50,30,'Elegans',1),
(9,18,32.00,5,'Codiaeum Excellent',1),(9,19,9.99,25,'Chinese Money Plant',1),
(9,20,13.50,15,'Nerve Plant Red',1),(13,1,14.00,20,'Marble Queen',1),
(13,3,16.50,8,'Golden Hahnii',1),(13,5,25.00,12,'White',1),(13,6,30.00,9,'Thai Constellation',1),
(13,11,42.00,4,'Elegance',1),(13,13,48.00,3,'Fiddle Leaf',1),
(6,16,20.00,6,'Brachycaulos',1),(6,17,8.00,20,'Lola',0),
(9,2,10.50,18,'Chinensis',1),(3,19,11.00,22,'Peperomioides',1);

-- ACCESSOIRE (avec image_url - noms exacts des fichiers dans images/)
INSERT INTO Accessoire (id_vendeur, nom_accessoire, description, prix, stock, image_url) VALUES
(3,'Pot terre cuite 20cm','Pot classique avec trou de drainage',5.99,50,'images/pot_terre_cuite.jpg'),
(3,'Substrat universel 5L','Terreau enrichi pour plantes d interieur',7.50,30,'images/substrat_universel.jpg'),
(3,'Arrosoir 1L','Arrosoir bec long pour arrosage de precision',12.00,20,'images/arrosoir.jpg'),
(3,'Engrais liquide','Engrais complet NPK pour plantes vertes',6.50,40,'images/engrais_liquide.jpg'),
(3,'Vaporisateur 500ml','Vaporisateur pour humidifier le feuillage',4.99,35,'images/vaporisateur.jpg'),
(6,'Pot ceramique 15cm','Pot decoratif avec soucoupe assortie',8.99,25,'images/pot_ceramique.jpg'),
(6,'Terreau cactus 3L','Substrat drainant specifique cactus',5.50,40,'images/terreau_cactus.jpg'),
(6,'Pot suspendu macrame','Support macrame pour pot suspendu',15.00,15,'images/pot_terre_cuite.jpg'),
(6,'Engrais orchidees','Engrais specifique pour orchidees',9.00,20,'images/engrais_liquide.jpg'),
(9,'Terreau universel 10L','Terreau de qualite superieure polyvalent',12.00,18,'images/substrat_universel.jpg'),
(9,'Pot zinc 18cm','Pot en zinc style industriel',11.50,22,'images/pot_ceramique.jpg'),
(9,'Bac a reserve d eau','Pot avec systeme d irrigation automatique',25.00,10,'images/pot_terre_cuite.jpg'),
(13,'Kit bouturage','Kit complet pour boutures : hormones + pot',18.00,12,'images/arrosoir.jpg'),
(13,'Tuteur bambou lot 5','Lot de 5 tuteurs en bambou 60cm',3.50,60,'images/substrat_universel.jpg'),
(13,'Lampe horticole LED','Lampe de croissance spectre complet 20W',45.00,8,'images/engrais_liquide.jpg');

-- LISTE SOUHAITS
INSERT INTO ListeSouhaits (id_utilisateur, id_plante) VALUES
(1,1),(1,3),(1,6),(1,15),(4,2),(4,7),(4,8),(7,5),(7,11),(7,13),
(10,4),(10,9),(10,19),(11,6),(11,20),(12,2),(15,16),(15,17),(16,1),(16,10);

-- COMMANDE
INSERT INTO Commande (id_utilisateur, id_vendeur, id_plante, id_accessoire, type_commande, quantite, prix_total, nom_carte, numero_carte, date_expiration, adresse_livraison, ville, code_postal, statut, date_commande) VALUES
(1,3,1,0,'plante',1,12.99,'Ben Ali Sami','4111111111111111','12/26','Rue de la Republique 12','Tunis','1000','livre','2025-01-15 10:30:00'),
(1,3,3,0,'plante',2,30.00,'Ben Ali Sami','4111111111111111','12/26','Rue de la Republique 12','Tunis','1000','confirme','2025-02-20 14:00:00'),
(4,6,9,0,'plante',1,19.99,'Mabrouk Leila','5500000000000004','08/25','Avenue Farhat Hached 5','Sfax','3000','livre','2025-01-28 09:15:00'),
(4,6,10,0,'plante',1,24.99,'Mabrouk Leila','5500000000000004','08/25','Avenue Farhat Hached 5','Sfax','3000','confirme','2025-03-05 11:00:00'),
(7,9,19,0,'plante',1,9.99,'Khemiri Rami','3714496353984312','05/27','Rue Ibn Sina 33','Sousse','4000','livre','2025-02-10 16:45:00'),
(7,13,13,0,'plante',1,48.00,'Khemiri Rami','3714496353984312','05/27','Rue Ibn Sina 33','Sousse','4000','confirme','2025-04-01 08:00:00'),
(10,3,6,0,'plante',1,28.00,'Jebali Hana','4111222233334444','03/26','Cite Olympique Bat C','Tunis','1003','livre','2025-01-05 13:20:00'),
(11,6,11,0,'plante',1,39.00,'Ferchichi Mehdi','5105105105105100','11/25','Route de la Corniche 7','Bizerte','7000','confirme','2025-03-18 10:10:00'),
(12,6,14,0,'plante',2,24.00,'Hammami Rim','6011000990139424','07/26','Quartier Mrezgua','Nabeul','8000','livre','2025-02-25 15:30:00'),
(15,13,5,0,'plante',1,25.00,'Tlili Omar','4111111111111111','09/27','Avenue Bourguiba 88','Monastir','5000','en_attente','2025-04-10 09:00:00'),
(16,3,1,0,'plante',1,12.99,'Chaabane Fatma','5500000000000004','06/26','Medina Tunis','Tunis','1000','livre','2025-01-22 11:45:00'),
(1,9,20,0,'plante',1,13.50,'Ben Ali Sami','4111111111111111','12/26','Rue de la Republique 12','Tunis','1000','confirme','2025-03-30 14:20:00'),
(7,13,6,0,'plante',1,30.00,'Khemiri Rami','3714496353984312','05/27','Rue Ibn Sina 33','Sousse','4000','livre','2025-02-14 17:00:00'),
(16,6,12,0,'plante',1,16.00,'Chaabane Fatma','5500000000000004','06/26','Medina Tunis','Tunis','1000','en_attente','2025-04-15 08:30:00'),
(15,9,18,0,'plante',1,32.00,'Tlili Omar','4111111111111111','09/27','Avenue Bourguiba 88','Monastir','5000','confirme','2025-03-12 10:00:00'),
(1,3,0,1,'accessoire',2,11.98,'Ben Ali Sami','4111111111111111','12/26','Rue de la Republique 12','Tunis','1000','livre','2025-01-20 10:00:00'),
(4,6,0,7,'accessoire',1,5.50,'Mabrouk Leila','5500000000000004','08/25','Avenue Farhat Hached 5','Sfax','3000','confirme','2025-02-08 14:30:00'),
(7,9,0,11,'accessoire',1,11.50,'Khemiri Rami','3714496353984312','05/27','Rue Ibn Sina 33','Sousse','4000','livre','2025-03-07 09:45:00'),
(10,3,0,2,'accessoire',1,7.50,'Jebali Hana','4111222233334444','03/26','Cite Olympique Bat C','Tunis','1003','livre','2025-01-30 16:00:00'),
(11,6,0,9,'accessoire',2,18.00,'Ferchichi Mehdi','5105105105105100','11/25','Route de la Corniche 7','Bizerte','7000','confirme','2025-03-25 11:15:00'),
(12,3,0,3,'accessoire',1,12.00,'Hammami Rim','6011000990139424','07/26','Quartier Mrezgua','Nabeul','8000','livre','2025-02-18 08:30:00'),
(15,13,0,14,'accessoire',3,10.50,'Tlili Omar','4111111111111111','09/27','Avenue Bourguiba 88','Monastir','5000','confirme','2025-04-05 13:00:00'),
(16,3,0,5,'accessoire',1,4.99,'Chaabane Fatma','5500000000000004','06/26','Medina Tunis','Tunis','1000','livre','2025-01-25 15:45:00'),
(1,13,0,15,'accessoire',1,45.00,'Ben Ali Sami','4111111111111111','12/26','Rue de la Republique 12','Tunis','1000','confirme','2025-04-12 10:30:00'),
(7,9,0,12,'accessoire',1,25.00,'Khemiri Rami','3714496353984312','05/27','Rue Ibn Sina 33','Sousse','4000','livre','2025-03-20 09:00:00'),
(16,6,0,6,'accessoire',2,17.98,'Chaabane Fatma','5500000000000004','06/26','Medina Tunis','Tunis','1000','en_attente','2025-04-18 14:00:00'),
(11,3,0,4,'accessoire',1,6.50,'Ferchichi Mehdi','5105105105105100','11/25','Route de la Corniche 7','Bizerte','7000','livre','2025-02-12 10:00:00'),
(12,6,0,8,'accessoire',1,15.00,'Hammami Rim','6011000990139424','07/26','Quartier Mrezgua','Nabeul','8000','confirme','2025-03-15 14:00:00'),
(4,9,0,10,'accessoire',1,12.00,'Mabrouk Leila','5500000000000004','08/25','Avenue Farhat Hached 5','Sfax','3000','livre','2025-02-28 11:30:00'),
(15,13,0,13,'accessoire',1,18.00,'Tlili Omar','4111111111111111','09/27','Avenue Bourguiba 88','Monastir','5000','confirme','2025-04-08 09:00:00');
