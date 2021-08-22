# Photo Nav API

Api to browse, list and display photos in a folder structure.

Created to be used on my raspberrypi and be available on my home network.

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

2. Get the folders and photos:

    ```shell
    curl --request GET http://localhost:8080/folder/
    ```

    ```json
    {
    "message": "Success.",
    "folders": [
        "Weekend",
        "At road"
    ],
    "photos": [
        "IMG_20190106_143358_BURST001.jpg",
        "IMG_20190106_162834.jpg",
        "IMG_20190106_162837.jpg"
    ]
    }
    ```

3. Get the photo:

    ```shell
    curl --request GET http://localhost:8080/photo/IMG_20190106_162837.jpg
    ```

If you found an error, have any questions or can improve something, please call me.
