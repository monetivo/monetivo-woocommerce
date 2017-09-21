# Monetivo WooCommerce Plugin

## Wstęp

To repozytorium zawiera kod moduły płatności monetivo dla WooCommerce. 
Aby zainstalować moduł skorzystaj z poniższej instrukcji lub zainstaluj plugin z [oficjalnego katalogu WordPress](https://wordpress.org/plugins/monetivo-woocommerce-payment-gateway/).
Jeżeli jestes developerem i chciałbyś pomóc (super!) to serdecznie zapraszamy! 

## Wymagania i zależności

- WordPress w wersji **4.7.1** lub wyższej
- WooCommerce w wersji **2.6.3** lub wyższej
- konto Merchanta w monetivo ([załóż konto](https://merchant.monetivo.com/register))

Moduł korzysta z naszego [klienta PHP](https://github.com/monetivo/monetivo-php) zatem wymagania środowiska są tożsame, tj. PHP w wersji 5.5 lub wyższej.
Dodatkowo potrzebne są moduły PHP:
- [`curl`](https://secure.php.net/manual/en/book.curl.php),
- [`json`](https://secure.php.net/manual/en/book.json.php)

## Instalacja

### Automatyczna
1. Przejdź do menu „Wtyczki” następnie „Dodaj nową” i w miejscu „Szukaj wtyczek”  wyszukaj **monetivo**
2. „Wynikach wyszukiwania” pojawi się moduł płatności **monetivo**, który należy zainstalować.

### Ręczna
1. [Pobierz archiwum ZIP](https://merchant.monetivo.com/download/monetivo-woocommerce.zip) z wtyczką na dysk.
2. Przejdź do menu „Wtyczki" następnie „Dodaj nową" i skorzystaj z przycisku **Wyślij wtyczkę na serwer**
3. Wybierz pobrane archiwum i kliknij **Zainstaluj teraz**

## Konfiguracja
1.	Przejdź do panelu administracyjnego i otwórz zakładkę „Wtyczki”. Kliknij „Włącz” przy pozycji „monetivo”.
2.	Przejdź do ustawień WordPressa, a następnie do WooCommerce -> Ustawienia i wybierz  zakładkę „Zamówienia”
3. Wybierz bramkę **monetivo** by przejść do ustawień bramki.
4.	Skonfiguruj bramkę podając dane uzyskane w Panelu Merchanta:
   - Aktywuj moduł płatności monetivo - pozycję należy pozostawić zaznaczoną,
   - Wpisz pozostałe dane: Login użytkownika, Hasło oraz Token aplikacji.
5.   Kliknij „Zapisz zmiany”. Wtyczka spróbuje nawiązać połączenie z systemem monetivo weryfikując tym samym poprawność wpisanych danych.

## Changelog

1.0.0 2017-01-25

- Wersja stabilna

1.0.1 2017-02-02
- usprawnienia w walidacji ustawień
- usunięcie ustawień POS - nie są wymagane
- poprawione przetwarzanie powiadomienia z Monetivo
- aktualizacja klienta API Monetivo

1.1.0 2017-06-14
- uwzględnienie uwag z [issue #1](https://github.com/monetivo/monetivo-woocommerce/issues/1)
- aktualizacja klienta API Monetivo
- zmiana struktury katalogów repozytorium
- zmiana kolejności pól w ustawieniach
- dodanie wyboru kanału płatności w sklepie

1.1.1 2017-09-21
- ustawianie statusu na on-hold po przejściu do płatności 

## Błędy

Jeżeli znajdziesz jakieś błędy zgłoś je proszę przez GitHub. Zachęcamy również do zgłaszania pomysłów dotyczących ulepszenia naszych rozwiązań.

## Wsparcie
W razie problemów z integracją prosimy o kontakt z naszym supportem. 