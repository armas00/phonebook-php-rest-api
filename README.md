# phonebook-php-rest-api
A not so simple plain PHP rest api to manage a phone book

# How to use
1. Create a user (phonebook) with a password (phonebook) in your MySQL Server instance.
2. Create a database (phonebook) and grant access to phonebook user to it.
3. Restore the database structure (and the content if you want some initial data) from the dump provided (phonebook.sql)
4. Point the web root directory of your web server instance to directory `phonebook-php-rest-api/api` inside this project.
5. Make sure your web server uses the `.htacces` file inside the `phonebook-php-rest-api/api/phone` directory. This file makes the api work with pretty urls.
6. Browse the endpoints!

# TODO
The api specification (or documentation) still needs to be created. If you want you can sniff into the code to find out the actual urls, parameters and payloads.
