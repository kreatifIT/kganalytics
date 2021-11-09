- in den Einstellungen alle benötigten Informationen eingeben
- im Google Tag Manager einen neuen Tag erstellen
    - Benutzerdefiniertes HTML
    ```
    <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('get', '{{Google Analytics G4 Settings}}', 'client_id', Kganayltics.setClientId);
    </script>
    ```
- `redaxo/src/addons/kganalytics/assets/js/kganayltics.js` Datei in Gruntfile einbinden 
    