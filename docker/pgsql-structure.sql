--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

SET search_path = public, pg_catalog;

--
-- Name: gender; Type: TYPE; Schema: public
--

CREATE TYPE gender AS ENUM (
    'male',
    'female'
);


SET default_tablespace = '';

SET default_with_oids = false;

-- Adminer 4.8.1 PostgreSQL 9.6.3 dump

DROP TABLE IF EXISTS "dochadzky";
DROP SEQUENCE IF EXISTS dochadzky_id_doch_seq;
CREATE SEQUENCE dochadzky_id_doch_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."dochadzky" (
                                      "id_doch" integer DEFAULT nextval('dochadzky_id_doch_seq') NOT NULL,
                                      "mesiac" integer NOT NULL,
                                      "prichod" time without time zone,
                                      "potvrdenie" boolean NOT NULL,
                                      "zamestnanciid_zam" integer NOT NULL,
                                      "nepritomnost" boolean,
                                      "odchod" time without time zone,
                                      CONSTRAINT "dochadzky_pkey" PRIMARY KEY ("id_doch")
) WITH (oids = false);


DROP TABLE IF EXISTS "dodavatelia";
DROP SEQUENCE IF EXISTS dodavatelia_id_dod_seq;
CREATE SEQUENCE dodavatelia_id_dod_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."dodavatelia" (
                                        "id_dod" integer DEFAULT nextval('dodavatelia_id_dod_seq') NOT NULL,
                                        "ico" bigint NOT NULL,
                                        "kontakt" character varying(100) NOT NULL,
                                        "nazov" character varying(100) NOT NULL,
                                        CONSTRAINT "dodavatelia_nazov_key" UNIQUE ("nazov"),
                                        CONSTRAINT "dodavatelia_pkey" PRIMARY KEY ("id_dod")
) WITH (oids = false);

INSERT INTO "dodavatelia" ("id_dod", "ico", "kontakt", "nazov") VALUES
(10,  34567654, 'cbrfashion@cbrfashion.com',  'CBR FASHION'),
(2, 25918378, 'kontakt@trendyclothing.sk',  'Trendy Clothing a.s.'),
(3, 65489023, 'objednavky@styleindustries.sk',  'Style Industries s.r.o.'),
(4, 54938761, 'info@fashionworld.sk', 'Fashion World s.r.o.'),
(5, 36854792, 'objednavky@clothingparadise.sk', 'Clothing Paradise a.s.'),
(6, 12983475, 'info@elegancefashion.sk',  'Elegance Fashion s.r.o.'),
(7, 49721563, 'objednavky@glamourwear.sk',  'Glamour Wear a.s.'),
(8, 32179864, 'info@fashionspot.sk',  'Fashion Spot s.r.o.'),
(9, 96847521, 'kontakt@exclusivefashion.sk',  'Exclusive Fashion a.s.'),
(11,  27584913, 'info@fashionempire.sk',  'Fashion Empire a.s.'),
(12,  63849521, 'objednavky@chicclothing.sk', 'Chic Clothing s.r.o.'),
(13,  15432976, 'info@glamorouswear.sk',  'Glamorous Wear a.s.'),
(14,  95843216, 'objednavky@fashionista.sk',  'Fashionista s.r.o.'),
(15,  73214589, 'kontakt@trendyapparel.sk', 'Trendy Apparel a.s.'),
(16,  41678923, 'info@fashionhub.sk', 'Fashion Hub s.r.o.'),
(17,  68947231, 'objednavky@elegantclothing.sk',  'Elegant Clothing a.s.'),
(18,  32569714, 'info@styleavenue.sk',  'Style Avenue s.r.o.'),
(19,  14783629, 'objednavky@fashionplanet.sk',  'Fashion Planet a.s.'),
(20,  56983124, 'info@clothingdeluxe.sk', 'Clothing Deluxe s.r.o.'),
(1, 47385429, 'info@fashioncompany.sk', 'Fashion Company s.r.o.');

DROP TABLE IF EXISTS "faktury";
DROP SEQUENCE IF EXISTS faktury_cislo_fakt_seq;
CREATE SEQUENCE faktury_cislo_fakt_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."faktury" (
                                    "cislo_fakt" integer DEFAULT nextval('faktury_cislo_fakt_seq') NOT NULL,
                                    "nazov_fakt" character varying(100) NOT NULL,
                                    "datum_splatnosti" date NOT NULL,
                                    "datum_evidencie" date NOT NULL,
                                    "suma" double precision NOT NULL,
                                    "bez_dph" double precision NOT NULL,
                                    "dph" double precision NOT NULL,
                                    "vyplatene" double precision NOT NULL,
                                    "rozdiel" double precision NOT NULL,
                                    "datum_dodania" date NOT NULL,
                                    "poznamka" character varying(100) NOT NULL,
                                    "dodavateliaid_dod" integer NOT NULL,
                                    CONSTRAINT "faktury_pkey" PRIMARY KEY ("cislo_fakt")
) WITH (oids = false);

INSERT INTO "faktury" ("cislo_fakt", "nazov_fakt", "datum_splatnosti", "datum_evidencie", "suma", "bez_dph", "dph", "vyplatene", "rozdiel", "datum_dodania", "poznamka", "dodavateliaid_dod") VALUES
(1, 'Faktúra 1',  '2023-02-19', '2023-02-19', 1200, 960,  240,  700,  500,  '2023-02-18', 'Ukážková faktúra 1', 1),
(2, 'Faktúra 2',  '2023-02-20', '2023-02-19', 2000, 1700.86,  299.14, 0,  2000, '2023-02-17', 'Ukážková faktúra 2', 2),
(3, 'Faktúra 3',  '2023-02-21', '2023-02-20', 1500, 1275.65,  224.35, 0,  1500, '2023-02-16', 'Ukážková faktúra 3', 3),
(4, 'Faktúra 4',  '2023-02-22', '2023-02-21', 3000, 2551.3, 448.7,  0,  3000, '2023-02-15', 'Ukážková faktúra 4', 4),
(5, 'Faktúra 5',  '2023-02-23', '2023-02-22', 2500, 2126.08,  373.92, 0,  2500, '2023-02-14', 'Ukážková faktúra 5', 5),
(6, 'Faktúra 6',  '2023-02-24', '2023-02-23', 1800, 1530.39,  269.61, 0,  1800, '2023-02-13', 'Ukážková faktúra 6', 6),
(7, 'Faktúra 7',  '2023-02-25', '2023-02-24', 2200, 1870.24,  329.76, 0,  2200, '2023-02-12', 'Ukážková faktúra 7', 7),
(8, 'Faktúra 8',  '2023-02-26', '2023-02-25', 2700, 2293.44,  329.56, 0,  2700, '2023-02-11', 'Ukážková faktúra 8', 14),
(9, 'Faktúra 9',  '2023-02-27', '2023-02-26', 3200, 2720.34,  479.66, 0,  3200, '2023-02-10', 'Ukážková faktúra 9', 18),
(11,  'Faktúra 11', '2023-03-01', '2023-02-28', 2500, 2126.08,  373.92, 0,  2500, '2023-02-08', 'Ukážková faktúra 11',  2),
(12,  'Faktúra 12', '2023-03-02', '2023-03-01', 3000, 2551.3, 448.7,  0,  3000, '2023-02-07', 'Ukážková faktúra 12',  3),
(13,  'Faktúra 13', '2023-03-03', '2023-03-02', 2200, 1870.24,  329.76, 0,  2200, '2023-02-06', 'Ukážková faktúra 13',  4),
(14,  'Faktúra 14', '2023-03-04', '2023-03-03', 1800, 1530.39,  269.61, 0,  1800, '2023-02-05', 'Ukážková faktúra 14',  5),
(15,  'Faktúra 15', '2023-03-05', '2023-03-04', 2700, 2293.44,  406.56, 0,  2700, '2023-02-04', 'Ukážková faktúra 15',  6);

DROP TABLE IF EXISTS "objednavky";
DROP SEQUENCE IF EXISTS objednavky_cislo_id_seq;
CREATE SEQUENCE objednavky_cislo_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."objednavky" (
                                       "cislo_id" integer DEFAULT nextval('objednavky_cislo_id_seq') NOT NULL,
                                       "pocet_kusov" integer NOT NULL,
                                       "pocet_artiklov" integer NOT NULL,
                                       "suma" double precision NOT NULL,
                                       "datum_vystavenia" date NOT NULL,
                                       "popis" character varying(100),
                                       "zaplatene" boolean NOT NULL,
                                       "prijate" boolean NOT NULL,
                                       "dodavatelianazov_dod" character varying(100) NOT NULL,
                                       CONSTRAINT "objednavky_pkey" PRIMARY KEY ("cislo_id")
) WITH (oids = false);

INSERT INTO "objednavky" ("cislo_id", "pocet_kusov", "pocet_artiklov", "suma", "datum_vystavenia", "popis", "zaplatene", "prijate", "dodavatelianazov_dod") VALUES
(1, 46, 10, 149.75, '2023-02-18', 'Obj 1',  't',  't',  'Fashion Company s.r.o.'),
(2, 24, 6,  126.1,  '2023-02-18', 'Obj 2',  't',  't',  'Style Industries s.r.o.'),
(3, 41, 8,  122.8,  '2023-02-18', 'Obj 3',  't',  't',  'Glamorous Wear a.s.'),
(4, 16, 4,  75.05,  '2023-02-18', 'Obj 4',  't',  't',  'Fashionista s.r.o.'),
(5, 4,  2,  56, '2023-03-04', 'ahoj', 'f',  'f',  'Fashion Company s.r.o.');

DROP TABLE IF EXISTS "predajne";
DROP SEQUENCE IF EXISTS predajne_cislo_predajne_seq;
CREATE SEQUENCE predajne_cislo_predajne_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."predajne" (
                                     "cislo_predajne" integer DEFAULT nextval('predajne_cislo_predajne_seq') NOT NULL,
                                     "nazov" character varying(100) NOT NULL,
                                     CONSTRAINT "predajne_pkey" PRIMARY KEY ("cislo_predajne")
) WITH (oids = false);

INSERT INTO "predajne" ("cislo_predajne", "nazov") VALUES
(1, 'namestie'),
(2, 'max'),
(3, 'tom tailor');

DROP TABLE IF EXISTS "produkty";
DROP SEQUENCE IF EXISTS produkty_cislo_artikla_seq;
CREATE SEQUENCE produkty_cislo_artikla_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."produkty" (
                                     "cislo_artikla" integer DEFAULT nextval('produkty_cislo_artikla_seq') NOT NULL,
                                     "nazov" character varying(100) NOT NULL,
                                     "sezona" character varying(100) NOT NULL,
                                     "pohlavie" character varying(100) NOT NULL,
                                     "druh" character varying(100) NOT NULL,
                                     "farba" character varying(100) NOT NULL,
                                     "stav" integer NOT NULL,
                                     "znacka" character varying(100) NOT NULL,
                                     "dodavatel" character varying(100) NOT NULL,
                                     "predajna_cena" double precision NOT NULL,
                                     "nakupna_cena" double precision NOT NULL,
                                     "ean" bigint NOT NULL,
                                     "predajnecislo_predajne" integer NOT NULL,
                                     "objednavkycislo_obj" integer NOT NULL,
                                     CONSTRAINT "produkty_pkey" PRIMARY KEY ("cislo_artikla")
) WITH (oids = false);

INSERT INTO "produkty" ("cislo_artikla", "nazov", "sezona", "pohlavie", "druh", "farba", "stav", "znacka", "dodavatel", "predajna_cena", "nakupna_cena", "ean", "predajnecislo_predajne", "objednavkycislo_obj") VALUES
(1000064762,  'SO TRIČKO  QR-Palmira 1/2',  'SS-23',  'dámsky', 'tričko-KR',  'biela',  2,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002036944,  1,  1),
(1000064763,  'SO TRIČKO  QR-Palmira 1/2',  'SS-23',  'dámsky', 'tričko-KR',  'modrá',  6,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002036982,  1,  1),
(1000064764,  'SO BLÚZKA  QR-Cotton 1/1', 'SS-23',  'dámsky', 'blúzka-DR',  'biela',  5,  'STREET ONE', 'CBR FASHION',  49.99,  20.4, 2000002037026,  1,  1),
(1000064765,  'SO TRIČKO  Palmira 1/2', 'SS-23',  'dámsky', 'tričko-KR',  'zelená', 6,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002037064,  1,  1),
(1000064805,  'SO NOHAVICE  Emee',  'SS-23',  'dámsky', 'nohavice', 'modrá',  4,  'STREET ONE', 'CBR FASHION',  69.99,  28.55,  2000002038269,  1,  1),
(1000064881,  'SO ŠÁL  OP Madras',  'SS-23',  'dámsky', 'šál',  'multi',  5,  'STREET ONE', 'CBR FASHION',  35.99,  14.7, 2000002040750,  1,  1),
(1000064882,  'SO TRIČKO  3/4', 'SS-23',  'dámsky', 'tričko-KR',  'offwhite', 3,  'STREET ONE', 'CBR FASHION',  29.99,  12.25,  2000002040798,  1,  1),
(1000064883,  'SO TRIČKO  3/4', 'SS-23',  'dámsky', 'tričko-KR',  'modrá',  5,  'STREET ONE', 'CBR FASHION',  29.99,  12.25,  2000002040835,  1,  1),
(1000064885,  'SO BLÚZKA  Checkblouse 1/1', 'SS-23',  'dámsky', 'blúzka-DR',  'modrá',  5,  'STREET ONE', 'CBR FASHION',  49.99,  20.4, 2000002040880,  1,  1),
(1000065064,  'SO TRIČKO QR-New Palmira 1/2', 'SS-23',  'dámsky', 'tričko-KR',  'oranžová', 6,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002045649,  2,  3),
(1000064886,  'SO ŠÁL  Printed',  'SS-23',  'dámsky', 'šál',  'modrá',  5,  'STREET ONE', 'CBR FASHION',  22.99,  9.4,  2000002040897,  1,  1),
(1000064887,  'SO ŠATKA', 'SS-23',  'dámsky', 'šatka',  'zelená', 2,  'STREET ONE', 'CBR FASHION',  22.99,  9.4,  2000002040903,  2,  2),
(1000064978,  'SO BLÚZKA  LTD QR-Bamilka 1/1',  'SS-23',  'dámsky', 'blúzka-DR',  'zelená', 5,  'STREET ONE', 'CBR FASHION',  39.99,  16.3, 2000002042044,  2,  2),
(1000064986,  'SO SAKO  LTD QR-Hanni',  'SS-23',  'dámsky', 'sako', 'zelená', 3,  'STREET ONE', 'CBR FASHION',  79.99,  32.65,  2000002042419,  2,  2),
(1000064987,  'SO BLÚZKA  Bamika 1/1',  'SS-23',  'dámsky', 'blúzka-DR',  'modrá',  4,  'STREET ONE', 'CBR FASHION',  35.99,  14.7, 2000002042457,  2,  2),
(1000064994,  'SO VESTA', 'SS-23',  'dámsky', 'vesta',  'ružová', 5,  'STREET ONE', 'CBR FASHION',  49.99,  20.4, 2000002042778,  3,  2),
(1000065005,  'SO NOHAVICE  Bonny', 'SS-23',  'dámsky', 'nohavice', 'čierna', 5,  'STREET ONE', 'CBR FASHION',  79.99,  32.65,  2000002043195,  3,  2),
(1000065006,  'SO PULOVER', 'SS-23',  'dámsky', 'pulover',  'modrá',  4,  'STREET ONE', 'CBR FASHION',  59.99,  24.5, 2000002043232,  3,  3),
(1000065063,  'SO TOP QR-Anni', 'SS-23',  'dámsky', 'top',  'oranžová', 4,  'STREET ONE', 'CBR FASHION',  12.99,  5.3,  2000002045601,  2,  3),
(1000065065,  'SO TRIČKO 1/1',  'SS-23',  'dámsky', 'tričko-DR',  'offwhite', 6,  'STREET ONE', 'CBR FASHION',  29.99,  12.25,  2000002045687,  2,  3),
(1000065066,  'SO TRIČKO 1/2',  'SS-23',  'dámsky', 'tričko-KR',  'offwhite', 6,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002045724,  2,  3),
(1000065067,  'SO TRIČKO 1/2',  'SS-23',  'dámsky', 'tričko-KR',  'oranžová', 6,  'STREET ONE', 'CBR FASHION',  25.99,  10.6, 2000002045762,  2,  3),
(1000065068,  'SO BLÚZKA Printed 1/1',  'SS-23',  'dámsky', 'blúzka-DR',  'modrá',  6,  'STREET ONE', 'CBR FASHION',  49.99,  20.4, 2000002045809,  2,  3),
(1000065069,  'SO NOHAVICE Bonny',  'SS-23',  'dámsky', 'nohavice', 'modrá',  3,  'STREET ONE', 'CBR FASHION',  69.99,  28.55,  2000002045847,  2,  3),
(1000065070,  'SO NOHAVICE Bonny',  'SS-23',  'dámsky', 'nohavice', 'čierna', 2,  'STREET ONE', 'CBR FASHION',  59.99,  24.5, 2000002045908,  2,  4),
(1000065071,  'SO ŠATKA', 'SS-23',  'dámsky', 'šatka',  'oranžová', 6,  'STREET ONE', 'CBR FASHION',  27.99,  11.4, 2000002045915,  2,  4),
(1000065113,  'SO ŠATY Mat Mix',  'SS-23',  'dámsky', 'šaty', 'modrá',  3,  'STREET ONE', 'CBR FASHION',  69.99,  28.55,  2000002047414,  2,  4),
(1000064761,  'SO TRIČKO  QR-Pania 3/4',  'SS-23',  'dámsky', 'tričko-KR',  'ružová', 5,  'STREET ONE', 'CBR FASHION',  23.99,  10.6, 2000002036906,  1,  4);

DROP TABLE IF EXISTS "uzivatelia";
DROP SEQUENCE IF EXISTS uzivatelia_id_seq;
CREATE SEQUENCE uzivatelia_id_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."uzivatelia" (
                                       "email" character varying(100) NOT NULL,
                                       "password" character varying(100) NOT NULL,
                                       "token" character varying(100) NOT NULL,
                                       "id_uziv" integer DEFAULT nextval('uzivatelia_id_seq') NOT NULL,
                                       CONSTRAINT "uzivatelia_id" PRIMARY KEY ("id_uziv")
) WITH (oids = false);

INSERT INTO "uzivatelia" ("email", "password", "token", "id_uziv") VALUES
('skuska@skuska.sk',  '5428deff0d86c413c2ffc902ffef71bf', '06dae72b679739fc316ffa341b1c0a8b11293144', 1),
('kamila@valentin.com', '453f117411c9d6e96a6d328048f78cef', '15515885482c1f217be3e6127ce659fcefae129d', 2),
('ahoj@ahoj.com', '6baa4c7b927ce96f6638e58189a21de4', '5a58c06afafa1d6fdfa492952b3e467495656ac1', 3);

DROP TABLE IF EXISTS "zakaznici";
DROP SEQUENCE IF EXISTS zakaznici_id_zak_seq;
CREATE SEQUENCE zakaznici_id_zak_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."zakaznici" (
                                      "id_zak" integer DEFAULT nextval('zakaznici_id_zak_seq') NOT NULL,
                                      "meno" character varying(100) NOT NULL,
                                      "priezvisko" character varying(100) NOT NULL,
                                      "tel_cislo" integer NOT NULL,
                                      "email" character varying(100) NOT NULL,
                                      CONSTRAINT "zakaznici_pkey" PRIMARY KEY ("id_zak")
) WITH (oids = false);

INSERT INTO "zakaznici" ("id_zak", "meno", "priezvisko", "tel_cislo", "email") VALUES
(1, 'Matej',  'Novák',  123456789,  'matej.novak@example.com'),
(2, 'Adam', 'Hríbik', 987654321,  'adam.hribik@example.com'),
(3, 'Eva',  'Varga',  456789123,  'eva.varga@example.com'),
(4, 'Tomas',  'Kovac',  741852963,  'tomas.kovac@example.com'),
(5, 'Petra',  'Horvathova', 369258147,  'petra.horvathova@example.com'),
(6, 'Marek',  'Farkas', 258741369,  'marek.farkas@example.com'),
(7, 'Lucia',  'Balazova', 951357864,  'lucia.balazova@example.com'),
(8, 'Jakub',  'Nemec',  753951486,  'jakub.nemec@example.com'),
(9, 'Katarina', 'Havranova',  123987456,  'katarina.havranova@example.com'),
(10,  'Jozef',  'Benes',  456321789,  'jozef.benes@example.com'),
(11,  'Viktor', 'Polakovic',  789654123,  'viktor.polakovic@example.com'),
(12,  'Natalia',  'Mihalova', 369852147,  'natalia.mihalova@example.com'),
(13,  'Dominik',  'Tomcik', 258963147,  'dominik.tomcik@example.com'),
(14,  'Martina',  'Kozakova', 951753684,  'martina.kozakova@example.com'),
(15,  'Milan',  'Jurica', 753159486,  'milan.jurica@example.com'),
(16,  'Simona', 'Kovacova', 123456987,  'simona.kovacova@example.com'),
(17,  'Miroslav', 'Novotny',  789654321,  'miroslav.novotny@example.com'),
(18,  'Janka',  'Malinova', 456789321,  'janka.malinova@example.com'),
(19,  'Peter',  'Cernak', 369258147,  'peter.cernak@example.com'),
(20,  'Dana', 'Sedlackova', 258741369,  'dana.sedlackova@example.com'),
(23,  'Andrej', 'Šťastný',  2345678,  'andrej@stastny.sk'),
(24,  'Jaromír',  'Drahý',  4567898,  'jaromir.drahy@example.com');

DROP TABLE IF EXISTS "zamestnanci";
DROP SEQUENCE IF EXISTS zamestnanci_id_zam_seq;
CREATE SEQUENCE zamestnanci_id_zam_seq INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 CACHE 1;

CREATE TABLE "public"."zamestnanci" (
                                        "id_zam" integer DEFAULT nextval('zamestnanci_id_zam_seq') NOT NULL,
                                        "cislo_predajne" integer NOT NULL,
                                        "meno" character varying(100) NOT NULL,
                                        "priezvisko" character varying(100) NOT NULL,
                                        "predajnecislo_predajne" integer NOT NULL,
                                        CONSTRAINT "zamestnanci_pkey" PRIMARY KEY ("id_zam")
) WITH (oids = false);

INSERT INTO "zamestnanci" ("id_zam", "cislo_predajne", "meno", "priezvisko", "predajnecislo_predajne") VALUES
(1, 1,  'Ivetka', 'Machanová',  1),
(2, 1,  'Zdenka', 'Trošká', 1),
(3, 1,  'Maketa', 'Klasková', 1),
(4, 2,  'Janka',  'Hruškova', 2),
(5, 2,  'Zuzana', 'Bedná',  2),
(6, 2,  'Ivona',  'Masková',  2),
(7, 3,  'Filip',  'Tonda',  3),
(8, 3,  'Hanka',  'Bolova', 3),
(9, 3,  'Dagmar', 'Tropová',  3);

ALTER TABLE ONLY "public"."dochadzky" ADD CONSTRAINT "dochadzky_zamestnanciid_zam_fkey" FOREIGN KEY (zamestnanciid_zam) REFERENCES zamestnanci(id_zam) NOT DEFERRABLE;

ALTER TABLE ONLY "public"."faktury" ADD CONSTRAINT "faktury_dodavateliaid_dod_fkey" FOREIGN KEY (dodavateliaid_dod) REFERENCES dodavatelia(id_dod) NOT DEFERRABLE;

ALTER TABLE ONLY "public"."objednavky" ADD CONSTRAINT "objednavky_dodavatelianazov_dod_fkey" FOREIGN KEY (dodavatelianazov_dod) REFERENCES dodavatelia(nazov) NOT DEFERRABLE;

ALTER TABLE ONLY "public"."produkty" ADD CONSTRAINT "produkty_objednavkycislo_obj_fkey" FOREIGN KEY (objednavkycislo_obj) REFERENCES objednavky(cislo_id) NOT DEFERRABLE;
ALTER TABLE ONLY "public"."produkty" ADD CONSTRAINT "produkty_predajnecislo_predajne_fkey" FOREIGN KEY (predajnecislo_predajne) REFERENCES predajne(cislo_predajne) NOT DEFERRABLE;

ALTER TABLE ONLY "public"."zamestnanci" ADD CONSTRAINT "zamestnanci_predajnecislo_predajne_fkey" FOREIGN KEY (predajnecislo_predajne) REFERENCES predajne(cislo_predajne) NOT DEFERRABLE;

-- 2023-04-20 13:51:21.253612+00
