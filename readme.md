# Nette Web Project

## Web Server Setup

The simplest way to get started is to start the built-in PHP server in the root directory of your project:

    npm run start

    docker-compose up

    php -S localhost:8000 -t www

Then visit `http://localhost:8000` in your browser to see the welcome page.

For Apache or Nginx, setup a virtual host to point to the `www/` directory of the project and you
should be ready to go.

**It is CRITICAL that whole `app/`, `config/`, `log/` and `temp/` directories are not accessible directly
via a web browser. See [security warning](https://nette.org/security-warning).**

# Used Libraries

## Node

naja

## CSS
color pallete: https://colorhunt.co/palette/f3f9fb474f8551e3d4f3ecd3

## Admin template

neon-bootstrap-admin

# TODO

## Front


Create Email templates

Design Completation pages

fix that user cant reserve full day as backup

přepsat crony




## Admin
?přidat k rezervacím název služby do hlavičky (předělat)
?upravit zobrazení čísla rezevace
na front udělat grid na služby
při vyplnování údajů zobrazovat info o službě
opravit logo v emailu
vylepšit zadávání volných termínů do služby


----- když bude čas
předělat design pracovní doby
předělat zadávání pracovní doby jako v calendly
předělat jak se ukádájí praconí doby. zrušit přestávky a ukládat jen pracovní dobu
přidat možnost unikátního rozvrhu pro službu.

Odstranit napojení tabulek na uživatele.

Admin by měl mít možnost změnit dobu konání rezevace.







