# Dokument wymagań produktu (PRD) - Help My Dog

## 1. Przegląd produktu
Aplikacja Help My Dog to webowe narzędzie wspierające właścicieli psów w wychowaniu i treningu ich pupili. Jej celem jest dostarczenie spersonalizowanych porad treningowych na podstawie informacji o konkretnym psie (takich jak rasa, wiek, poziom energii) oraz opisu problemu przekazanego przez użytkownika. Rozwiązanie wykorzystuje model sztucznej inteligencji (chatbot oparty o OpenAI GPT), który generuje wskazówki dostosowane do charakterystyki danego psa i zgłaszanego zagadnienia. Aplikacja oferuje dwa tryby uzyskiwania pomocy: natychmiastową szybką poradę dla pojedynczego problemu oraz mini-plan 7-dniowy, czyli tygodniowy plan treningowy do systematycznej pracy nad bardziej złożonym zagadnieniem. Wszystkie interakcje odbywają się w języku polskim, w przyjaznym i edukacyjnym tonie. Interfejs jest nowoczesny, o jasnej kolorystyce i zaprojektowany w podejściu mobile-first, dzięki czemu aplikacja jest wygodna w użyciu zarówno na komputerze, jak i na smartfonie. Aplikacja nie przechowuje historii rozmów – każda sesja dotyczy jednego problemu i jednego psa, a po jej zakończeniu użytkownik otrzymuje podsumowanie ("kartę porady") do ewentualnego zachowania.

## 2. Problem użytkownika
Właściciele psów często napotykają trudności w szkoleniu swoich zwierząt oraz korygowaniu niepożądanych zachowań. Tradycyjne źródła informacji, takie jak poradniki czy fora internetowe, mogą być ogólne i nie uwzględniają unikalnych cech każdego psa. Uzyskanie profesjonalnej porady od tresera lub behawiorysty bywa czasochłonne i kosztowne, a nie zawsze jest dostępne od ręki, gdy pojawia się problem. Brakuje łatwo dostępnego rozwiązania, które natychmiast udzieli wskazówek dopasowanych do konkretnego przypadku – uwzględniających rasę, wiek i temperament psa. Użytkownicy potrzebują interaktywnej pomocy "na żądanie", aby móc szybko reagować na wyzwania treningowe (np. pies ciągnący na smyczy, lęk separacyjny, nauka nowej sztuczki) bez konieczności przeszukiwania wielu źródeł. Aplikacja Help My Dog adresuje te potrzeby, umożliwiając właścicielom uzyskanie spersonalizowanych porad treningowych w dowolnym momencie, w prosty i szybki sposób.

## 3. Wymagania funkcjonalne
- Profil psa: Użytkownik ma możliwość utworzenia profilu każdego ze swoich psów, wprowadzając podstawowe informacje: imię, rasa (lub mieszaniec), wiek, płeć, waga oraz ogólny poziom energii. Dane te są zapisywane na koncie użytkownika i wykorzystywane do personalizacji porad.
- Wybór kategorii problemu: Przed rozpoczęciem sesji użytkownik wybiera zdefiniowaną kategorię problemu lub treningu, którego dotyczy pytanie (np. zachowanie, nauka sztuczek, posłuszeństwo, free-shaping). Następnie wprowadza opis konkretnego problemu lub celu szkoleniowego w formie tekstowej.
- Chatbot AI z doprecyzowaniem kontekstu: System wykorzystuje model AI (OpenAI GPT) do generowania porady. Chatbot analizuje opis problemu oraz dane z profilu psa i w razie potrzeby zadaje dodatkowe pytania (maksymalnie 3), aby uściślić kontekst. Jeśli użytkownik już podał wymagane informacje, chatbot przechodzi od razu do udzielenia porady bez zbędnych pytań.
- Tryb szybkiej porady: Użytkownik może wybrać opcję jednorazowej, szybkiej porady. W tym trybie po zebraniu niezbędnych informacji chatbot przedstawia pojedynczą odpowiedź zawierającą konkretne wskazówki rozwiązania zgłoszonego problemu. Porada uwzględnia charakterystykę wybranego psa i opisany przez użytkownika problem.
- Tryb mini-planu 7 dni: Użytkownik może wybrać opcję wygenerowania tygodniowego planu treningowego. W tym trybie chatbot tworzy plan działań na kolejne 7 dni, dopasowany do problemu. Każdy dzień planu zawiera: określony cel dnia, listę kroków/czynności do wykonania, kryterium sukcesu oraz dodatkowe wskazówki lub uwagi. Cały plan jest prezentowany użytkownikowi od razu w formie czytelnej listy dni (Dzień 1 – Dzień 7).
- Jedna sesja – jeden pies: Przed rozpoczęciem czatu użytkownik wybiera, którego psa (profil) dotyczy problem. W trakcie danej sesji konwersacyjnej nie ma możliwości zmiany wybranego profilu psa – zapewnia to spójność kontekstu porady. Jeśli użytkownik chce uzyskać poradę dla innego pupila, musi rozpocząć nową sesję.
- Generowanie karty porady: Po zakończeniu rozmowy system generuje podsumowanie w formie karty, która zawiera: problem, imię i profil psa, oraz udzieloną poradę lub plan. Karta jest zapisana przy danym psie; użytkownik może skopiować treść do schowka lub pobrać jako plik (np. .txt).
- Informacja zwrotna: Użytkownik może oznaczyć poradę jako pomocną lub niepomocną (np. przyciskiem "👍 Pomogło" / "👎 Nie pomogło"). Ocena jest zapisywana lokalnie lub na koncie użytkownika.
- Wykrywanie tematów ryzykownych: Mechanizm rozpoznaje słowa kluczowe sugerujące niebezpieczny problem (np. agresja, pogryzienie, silny ból). Wykrycie powoduje wyświetlenie ostrzeżenia i zalecenie kontaktu ze specjalistą zamiast standardowej porady.
- Konto użytkownika i profil: Rejestracja i logowanie w celu ochrony danych profili psów i kart porad (alternatywnie przechowywanie lokalne z ograniczeniami).
- UX i wydajność: Responsywny interfejs, jasna kolorystyka, edukacyjny ton, wskaźnik ładowania i czas odpowiedzi AI docelowo poniżej 8 sekund.

## 4. Granice produktu
- Platforma: wyłącznie aplikacja webowa (brak natywnej aplikacji i trybu offline).
- Język: tylko język polski.
- Historia czatu: brak przechowywania historii rozmów (sesje jednorazowe).
- Zakres porad: brak porad medycznych i eskalujących niebezpieczne zachowania – zalecenie konsultacji ze specjalistą w takich przypadkach.
- Brak funkcji społecznościowych i monetyzacji; brak powiadomień push.
- Treści generowane dynamicznie przez AI; brak panelu administracyjnego i zaawansowanej moderacji.
- Telemetria minimalna na potrzeby debugowania.
- Nierozstrzygnięte szczegóły (TBD): lista kategorii problemów, zestaw dopytań, lista fraz ryzyka i treść ostrzeżeń, format karty porady, szczegóły walidacji danych profilu psa.

## 5. Historyjki użytkowników

### US-001: Rejestracja nowego użytkownika
Opis: Jako osoba rozpoczynająca korzystanie z aplikacji, chcę założyć własne konto użytkownika, abym mógł zapisywać profile moich psów i przechowywać otrzymane porady w sposób prywatny.  
Kryteria akceptacji:
- Formularz rejestracji (e-mail, hasło) z walidacją (np. min. 8 znaków hasła).
- Obsługa konfliktu (e-mail już istnieje).
- Po rejestracji konto utworzone i użytkownik zalogowany (lub potwierdzenie i logowanie).
- Dostęp do tworzenia profilu psa po rejestracji.

### US-002: Logowanie istniejącego użytkownika
Opis: Jako zarejestrowany użytkownik, chcę móc zalogować się na swoje konto, abym miał dostęp do zapisanych profili moich psów oraz wcześniejszych kart porad niezależnie od urządzenia.  
Kryteria akceptacji:
- Formularz logowania (e-mail, hasło) z obsługą błędnych danych.
- Po sukcesie dostęp do listy psów i kart porad.
- Bezpieczna sesja i możliwość wylogowania.

### US-003: Wylogowanie użytkownika
Opis: Jako zalogowany użytkownik, chcę mieć możliwość wylogowania się z konta, aby zabezpieczyć dane.  
Kryteria akceptacji:
- Widoczna opcja Wyloguj.
- Zakończenie sesji i powrót do ekranu powitalnego.
- Brak dostępu do danych po wylogowaniu; automatyczne wylogowanie po bezczynności.

### US-004: Dodanie profilu psa
Opis: Jako zalogowany użytkownik, chcę dodać nowy profil mojego psa, aby porady były spersonalizowane.  
Kryteria akceptacji:
- Formularz dodawania (imię, rasa/mix, wiek, płeć, waga, energia).
- Walidacje pól i zakresów.
- Profil zapisany i widoczny na liście.
- Profil dostępny do wyboru przy starcie sesji.

### US-005: Edycja profilu psa
Opis: Jako użytkownik, chcę edytować dane psa, aby utrzymywać ich aktualność.  
Kryteria akceptacji:
- Formularz edycji z prefill.
- Walidacje jak przy dodawaniu.
- Zmiany zapisane i widoczne w profilu; kolejne porady używają nowych danych.

### US-006: Usunięcie profilu psa
Opis: Jako użytkownik, chcę usunąć profil psa, aby utrzymać porządek w danych.  
Kryteria akceptacji:
- Opcja Usuń z potwierdzeniem.
- Usunięcie profilu i powiązanych kart porad.
- Anulowanie operacji nie usuwa profilu.

### US-007: Wybór profilu psa przed sesją
Opis: Jako użytkownik z kilkoma psami, chcę wybrać psa przed rozmową z chatbotem, aby porada była właściwie dopasowana.  
Kryteria akceptacji:
- Lista psów lub automatyczny wybór, gdy jest jeden.
- Wyraźne wskazanie wybranego psa w UI.
- Brak możliwości zmiany psa w trakcie jednej sesji.

### US-008: Uzyskanie szybkiej porady
Opis: Jako użytkownik, chcę otrzymać jednorazową poradę na podstawie opisu problemu mojego psa.  
Kryteria akceptacji:
- Wybór trybu Szybka porada i kategorii problemu.
- Do 3 pytań doprecyzowujących (pomijane, gdy zbędne).
- Odpowiedź zawiera konkretne kroki; czas odpowiedzi docelowo < 8 s.
- Możliwość oceny i zapisania karty porady.

### US-009: Uzyskanie 7‑dniowego mini‑planu
Opis: Jako użytkownik, chcę otrzymać plan treningowy na 7 dni, aby systematycznie rozwiązać problem.  
Kryteria akceptacji:
- Wybór trybu Mini‑plan 7 dni i kategorii.
- Do 3 pytań uzupełniających.
- Plan zawiera: cel dnia, kroki, kryterium sukcesu, wskazówki (Dzień 1–7).
- Możliwość zapisania karty i oceny.

### US-010: Wyświetlenie i zapisanie karty porady
Opis: Jako użytkownik, chcę otrzymać kartę podsumowującą poradę/plan i móc ją zachować.  
Kryteria akceptacji:
- Automatyczne wygenerowanie karty po sesji.
- Kopiowanie do schowka i pobranie jako plik.
- Zapis karty przy danym psie; struktura zachowana dla planu 7 dni.

### US-011: Ocena otrzymanej porady
Opis: Jako użytkownik, chcę ocenić skuteczność porady (👍/👎).  
Kryteria akceptacji:
- Jednorazowa ocena przypisana do sesji.
- Zapis lokalny lub przy koncie; potwierdzenie w UI.

### US-012: Ostrzeżenie przy ryzykownym problemie
Opis: Jako użytkownik zgłaszający poważny problem (np. agresję/ból), chcę otrzymać ostrzeżenie zamiast standardowej porady.  
Kryteria akceptacji:
- Wykrywanie słów kluczowych (np. pogryzienie, atak, krew, ból).
- Wyświetlenie komunikatu i zalecenia kontaktu ze specjalistą.
- Brak standardowych wskazówek w takich przypadkach; minimalizacja false‑positive.

### US-013: Wygodne korzystanie na urządzeniach mobilnych
Opis: Jako użytkownik mobilny, chcę w pełni czytelnego i dostępnego interfejsu.  
Kryteria akceptacji:
- Responsywny układ bez przewijania w poziomie.
- Czytelne elementy dotykowe.
- Akceptowalne czasy ładowania na sieci mobilnej.
- Testy na popularnych rozdzielczościach (np. 360×640).

## 6. Metryki sukcesu
- Skuteczność porad: > 80% ocen pozytywnych (pomogło).
- Szybkość działania: średnia odpowiedź AI < 8 s (95. percentyl ≤ 8 s).
- UX: > 95% ukończonych sesji bez błędów UI.
- Poprawność techniczna: poprawne zapisy profili i kart; skuteczne wykrywanie przypadków ryzyka.
- Adopcja i zaangażowanie: wzrost liczby aktywnych użytkowników i profili psów; powroty użytkowników.
