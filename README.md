# Pomiary biomedyczne PHP/MySQL

Prosta aplikacja studencka w PHP, HTML i CSS do zapisywania pomiarów biomedycznych zalogowanego użytkownika.

## Funkcje

- rejestracja użytkownika,
- logowanie e-mailem i hasłem zapisanym przez `password_hash()`,
- zapis udanych i nieudanych prób logowania z datą oraz adresem IP,
- zmiana hasła po zalogowaniu,
- reset hasła przez pytanie pomocnicze,
- lista, dodawanie i edycja jednostek biomedycznych,
- katalog mierzonych parametrów z autorem wpisu,
- limit 5 własnych pozycji katalogowych na użytkownika,
- dodawanie pomiarów z ręcznie ustawianą datą i godziną,
- przeglądanie wszystkich zapisów jednego parametru.

## Obsługiwane parametry startowe

- temperatura ciała,
- ciśnienie krwi,
- waga,
- poziom witaminy D3.

## Pliki

- `schema.sql` - tworzy tabele i dodaje podstawowe jednostki oraz parametry,
- `config.php` - połączenie z bazą i funkcje pomocnicze,
- `index.php` - strona startowa,
- `register.php` - rejestracja,
- `login.php` - logowanie,
- `change_password.php` - zmiana hasła,
- `reset_password.php` - reset hasła,
- `dashboard.php` - panel pomiarów,
- `units.php` - zarządzanie jednostkami,
- `measurement_types.php` - zarządzanie katalogiem badań,
- `measurements.php` - zapisy jednego parametru,
- `logout.php` - wylogowanie,
- `style.css` - proste style.

## Uruchomienie

1. Wgraj pliki na serwer z PHP i MySQL.
2. Zaimportuj `schema.sql` w phpMyAdmin albo bezpośrednio w MySQL.
3. Ustaw dane bazy w `config.php`.
4. Otwórz `index.php` w przeglądarce.

Sprawozdanie należy umieścić w głównym folderze projektu, a plik musi się nazywać `Sprawozdanie.pdf`.

Na koniec można usunąć folder .git.
