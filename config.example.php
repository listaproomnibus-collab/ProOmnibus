<?php
/**
 * ESEMPIO DI CONFIGURAZIONE.
 * Copia questo file come config.php sul server Tophost e inserisci i valori reali.
 * NON caricare mai config.php su GitHub e non condividere i segreti.
 */
return [
    // Telegram: crea/gestisci il bot con BotFather e inserisci l'ID della chat destinataria.
    'telegram_bot_token' => 'INSERISCI_QUI_IL_TOKEN_NUOVO_DEL_BOT',
    'telegram_chat_id' => 'INSERISCI_QUI_IL_CHAT_ID',

    // MySQL: copia i dati mostrati dal pannello Tophost > Gestione Database.
    'db_host' => 'INSERISCI_HOST_DATABASE',
    'db_name' => 'INSERISCI_NOME_DATABASE',
    'db_user' => 'INSERISCI_UTENTE_DATABASE',
    'db_password' => 'INSERISCI_PASSWORD_DATABASE',

    // Crea credenziali amministratore robuste, non riutilizzate altrove.
    'admin_email' => 'admin@TUO-DOMINIO.it',
    'admin_password' => 'INSERISCI_UNA_PASSWORD_LUNGA_E_UNICA',
];
