# Trustindex Teszt

Egyszerű Symfony alkalmazás céges vélemények beküldésére és böngészésére. A főoldalon a legfrissebb vélemények, a `/companies` oldalon pedig a cégenként összesített értékelések láthatóak.

## Technikai döntések

Igyekeztem a megoldást a feladat méretéhez igazítani: legyen jól szétválasztott és tesztelhető, de ne kerüljön bele olyan absztrakció, amelyre ebben a méretben még nincs szükség. Nem került a projektbe oda nem illő funkció csak azért, hogy többnek tűnjön.
Amikre figyeltem:
- .env alapértelmezetten része a reponak, nem tartalmaz semmi titkosat, ezért nem raktam gitignore-ba.
- Controller réteg vékony maradjon és csak a HTTP-kérést kezelik, meghívják a szükséges repository- vagy Doctrine-műveletet, majd átadják az eredményt a viewnak. Az üzleti és lekérdezési logika nem került controller actionökbe, ezért azok rövidek, könnyen követhetők és külön-külön tesztelhetők maradtak.
- Külön entityk, mindenhez hamár ORM van. Ez később megkönnyíti a fejlesztést, mert csak ki kell bővíteni.
- Keresésnél fontos, hogy kisbetűs, levágott és egységes whitespace-t használó normalizált nevet készítsem valami. Ez lett a `CompanyResolver`. A normalizált név egyedi indexet kapott, a keresésben pedig a `%` és `_` karakterek nem válhatnak véletlen SQL wildcarddá.
- A `ReviewRepository` végzi az aggregálást, mert ilyen egyszerű műveleteket a DB-ben is meg lehet csinálni. Fölösleges PHP-t zaklatni vele és még gyorsabb is.
- A Doctrine az eredmények egy részét nem a rendes formában adja vissza (pl numerikus sttring), ezért használok egy DTO-t `CompanyStatistics` vagyis most value objectet ebben az esetben, hogy mindent biztosan a jó formában vigyünk tovább.

## Funkciók

- új vélemény beküldése szerveroldali validációval;
- lapozható véleménylista, részletező oldal és cégnév szerinti keresés;
- cégenkénti véleményszám és átlagos értékelés;
- értékeléseloszlás 1–5 csillag között;
- külön cégoldal az összesítéssel és a kapcsolódó véleményekkel;
- normalizált cégazonosítás, amely összevonja a kis- és nagybetűben vagy whitespace-ben eltérő névváltozatokat;
- reszponzív, külső frontend-függőség nélküli felület;
- az e-mail-cím tárolva van, de nyilvános oldalon nem jelenik meg.

## Bónusz

A cégnév szerinti keresés, az 1–5 csillagos értékeléseloszlás, a normalizált cégazonosítás, a lapozás és a külön cégoldal közvetlenül az eredeti feladatot egészíti ki. A felület egyedi, reszponzív és külső frontend-függőség nélkül működik, de a hangsúly továbbra is a véleményeken és a statisztikákon maradt.

## Követelmények

- PHP 8.2 vagy újabb, `pdo_sqlite` kiterjesztéssel
- Composer 2
- opcionálisan Symfony CLI

## Telepítés

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
symfony serve
```

Az alkalmazás ezután alapértelmezetten a Symfony CLI által kiírt helyi címen érhető el. Symfony CLI nélkül a PHP beépített szervere is használható:

```bash
php -S 127.0.0.1:8000 -t public
```

A fejlesztői környezet SQLite-adatbázist használ a `var/app.db` fájlban, ezért külön adatbázis-szerver nem szükséges. A `doctrine:database:create` parancs biztonságosan kihagyja a létrehozást, ha az adatbázis már létezik. Más adatbázishoz a `DATABASE_URL` környezeti változót kell felülírni, majd új migrációt generálni.

## Hasznos parancsok

```bash
# Tesztek
php bin/phpunit

# Kódszabvány ellenőrzése
vendor/bin/php-cs-fixer fix --dry-run --diff

# Kód formázása
vendor/bin/php-cs-fixer fix

# Doctrine mapping és adatbázisséma ellenőrzése
php bin/console doctrine:schema:validate

# Új entitásmódosítás után migráció generálása
php bin/console doctrine:migrations:diff
```

A tesztek külön SQLite-adatbázist használnak a `var/test.db` fájlban. A tesztkód minden futás előtt újraépíti a szükséges sémát, így nem érinti a fejlesztői adatokat.

## Felépítés

- `Company` – a cég egységes, normalizált identitása
- `Review` – Doctrine entitás, amely `ManyToOne` kapcsolatban tartozik egy céghez
- `CompanyRepository` – normalizált név alapján azonosítja a cégeket
- `ReviewRepository` – lapozás, keresés és aggregált cégstatisztika
- `CompanyNameNormalizer` – megjelenítési név tisztítása, stabil cégkulcs és biztonságos keresési minta
- `CompanyResolver` – beküldéskor megkeresi vagy létrehozza a normalizált céget
- `CompanyStatistics` – a lekérdezés eredményének típusos, megjelenítésre kész modellje
- `ReviewPage` – a lapozás típusos eredménye és navigációs állapota
- `ReviewController` – lista, beküldés és részletező oldal
- `CompanyController` – összesített cégstatisztika

### Tesztelési szintek

- A unit teszt a `CompanyStatistics` típuskonverzióját és százalékszámítását ellenőrzi.
- Az integrációs teszt valódi Doctrine-lekérdezéssel vizsgálja az átlagszámítást és a rendezést.
- További unit és integrációs tesztek fedik a cégnév-normalizálást, a keresési wildcardokat és a lapozást.
- A funkcionális tesztek böngészőkliensen keresztül ellenőrzik a beküldést, validációt, átirányítást, flash üzenetet, adatmentést, valamint a company oldal sikeres és 404-es válaszát.

A tesztek a fejlesztői `var/app.db` helyett külön `var/test.db` SQLite-adatbázist használnak. A séma minden teszt előtt újraépül, ezért a tesztek megismételhetők és nem módosítják a fejlesztés közben felvitt adatokat.

### AI használat

A fejlesztés során AI-t használtam kódreviewhoz, a PHP 8.2-kompatibilitás ellenőrzéséhez, tesztesetek ellenőrzéséhez és a dokumentáció pontosításához. A technikai döntéseket, a végleges implementációt és az ellenőrzést én végeztem.

## Munkaidőnapló

| Feladat | Idő |
| --- | ---: |
| Kiírás feldolgozása, adatmodell és útvonalak megtervezése | kb 10 perc |
| Symfony projekt és függőségek összeállítása | kb 15 perc |
| Entitás, repository, űrlap és controllerek | kb 30 perc |
| Twig nézetek, reszponzív CSS, keresés és értékeléseloszlás | kb 40 perc |
| Migráció, unit/integrációs/funkcionális tesztek és hibajavítás | kb 25 perc |
| Dokumentáció és végső ellenőrzés | kb 25 perc |
| **Összesen** | **kb 2 óra 25 perc** |
