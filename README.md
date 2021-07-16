# Photo Nav API

Api to browse, list and display photos in a folder structure.

To work together with [photonav-frontend](https://github.com/pauloklaus/photonav-frontend).

## Installation

* You need [composer.phar](https://getcomposer.org).

1. Clone the project:

`git clone https://github.com/pauloklaus/photonav-backend`

2. Run composer update:

`php composer.phar update`

3. Configure the .env.php file.

## Test

1. At the root of project, run:

`php -S localhost:8080 -t public`

2. Send a request with the data:

`curl --request GET http://localhost:8080/folder/`
`curl --request GET http://localhost:8080/photo/some_file.jpg`

If you found an error, have any questions or can improve something, please call me.
