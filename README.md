# Pro Omnibus — versione per Tophost

Il progetto contiene la pagina principale, il mercatino libri condiviso tramite MySQL e l'invio sicuro delle proposte Telegram tramite PHP. La webroot di Tophost è normalmente la cartella `htdocs`.

## Controlla il piano

Per usare il modulo Telegram e il catalogo libri condiviso serve un piano Tophost con **PHP e MySQL**, ad esempio Topweb, Topweb Plus o Topweb Ultra. Topname supporta soltanto HTML statico e non basta per queste funzioni.

## File presenti nel repository

```text
index.html              Pagina principale della lista
mercatino.html          Mercatino libri, con catalogo condiviso
.htaccess               Impostazioni base e protezione config.php
config.example.php      Modello senza credenziali reali
api/common.php          Funzioni PHP condivise
api/proposte.php        Invio delle proposte a Telegram
api/books.php           API catalogo/libri su MySQL
api/admin.php           Login/logout amministratore
database.sql             Struttura della tabella MySQL
README.md               Questa guida
.gitignore              Evita di caricare config.php su GitHub
```

## 1. Configura il database su Tophost

1. Dal pannello Tophost apri **Gestione Database** e crea un database MySQL.
2. Annota host, nome database, utente e password indicati dal pannello.
3. Apri phpMyAdmin, seleziona il database appena creato e importa il file `database.sql`.

## 2. Configura i segreti

1. Fai una copia di `config.example.php` e chiamala `config.php`.
2. Inserisci in `config.php` i dati MySQL, il token Telegram e il chat ID del destinatario, oltre a un'email e una password amministratore lunghe e uniche.
3. Carica `config.php` su Tophost, nella cartella `htdocs`, accanto a `index.html`. Il file `.htaccess` blocca l'accesso HTTP diretto a questo file.
4. **Non caricare mai `config.php` su GitHub e non mandarlo in chat.** Il repository deve contenere soltanto `config.example.php` con segnaposto.

Se il token Telegram del vecchio HTML è stato condiviso/pubblicato, revocalo con BotFather e crea un token nuovo prima di inserirlo nella configurazione.

## 3. Carica i file su Tophost

Nel pannello Tophost apri **File Manager**: la cartella del sito è `htdocs`. Carica al suo interno:

- `index.html`
- `mercatino.html`
- `.htaccess`
- la cartella `api/` completa con i quattro file PHP
- `config.php` con i valori reali

Il file `database.sql`, il `README.md`, `.gitignore` e `config.example.php` servono per la configurazione e il backup; non sono necessari nella cartella pubblica. Se il File Manager non mostra i file che iniziano con un punto, attiva la visualizzazione dei file nascosti oppure carica `.htaccess` tramite FTP/FileZilla.

Nel pannello Tophost seleziona PHP 8.3, se disponibile per il tuo piano, quindi visita il dominio con HTTPS.

## 4. Quali file caricare su GitHub

Crea un repository GitHub (può essere **Private**) e carica la struttura del progetto esattamente come si trova qui: `index.html`, `mercatino.html`, `.htaccess`, `config.example.php`, `database.sql`, `README.md`, `.gitignore` e la cartella `api/` con `common.php`, `proposte.php`, `books.php` e `admin.php`.

Non caricare `config.php`, token Telegram, password o credenziali del database. GitHub conserva il codice; **è Tophost che rende pubblico il sito**. Modificare GitHub non aggiorna automaticamente Tophost: dopo le modifiche dovrai ricaricare i file aggiornati su Tophost, a meno che tu non configuri in seguito una procedura di deploy.

## 5. Indirizzi del sito

Dopo aver caricato i file e configurato il database:

- Pagina principale: `https://TUO-DOMINIO/`
- Mercatino: `https://TUO-DOMINIO/mercatino.html`

Il catalogo è condiviso tramite MySQL: gli annunci pubblicati da uno studente possono essere visualizzati dagli altri visitatori. Per il pannello amministratore usa il pulsante **Admin** nel menu del mercatino e le credenziali definite in `config.php`.

## Note importanti

- Il mercatino raccoglie numeri WhatsApp e nomi/nickname che vengono mostrati negli annunci: pubblica un'informativa privacy adatta e comunica agli studenti di inserire solo dati che accettano di rendere visibili.
- Il login admin è lato server e non usa una password incorporata nel JavaScript. Imposta una password unica e non condividerla.
- Dopo la prima pubblicazione, prova prima l'invio Telegram, poi la pubblicazione di un annuncio, la visualizzazione da un altro dispositivo e infine il cambio di stato dal pannello admin.
