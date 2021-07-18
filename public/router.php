<?php

define("ROOT_DIR", __DIR__ . "/../");
define("LANG_DIR", ROOT_DIR . "lang/");
define("LOG_DIR", ROOT_DIR . "log/");
define("LOG_FILE", LOG_DIR . "app.log");
define("SRC_DIR", ROOT_DIR . "src/");
define("VENDOR_DIR", ROOT_DIR . "vendor/");

require VENDOR_DIR . "autoload.php";

// Http Headers

header_remove("X-Powered-By");

if (isset($_SERVER["HTTP_ORIGIN"]))
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Content-Type: text/plain");
    exit;
}

// Config

define("ENV", require ROOT_DIR . ".env.php");
define("LANG", require LANG_DIR . (ENV["LANG"] ?? "en") . ".php");

// Helpers

function sanitizeUrl(string $uri): string {
    if (false !== $pos = strpos($uri, "?"))
        $uri = substr($uri, 0, $pos);
    
    return rawurldecode($uri);
}

function makeQueryParams(string $queryString = ""): Array {
    $queryList = explode("&", $queryString);
    $params = [];

    foreach ($queryList as $query) {
        $keyValue = explode("=", $query);
        $params[$keyValue[0]] = $keyValue[1] ?? true;
    }

    return $params;
}

// function getParsedBody(): Array {
//     $body = file_get_contents("php://input");

//     if ($body === false)
//         throw new Exception(LANG["ERROR_CATCHING_BODY"]);

//     return json_decode($body, true) ?? [];
// }

function jsonResponse(Array $response = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    header("Content-Type: application/json");

    echo json_encode($response);
}

function successResponse(Array $data = []): void {
    $response = [ "message" => "Success." ];

    if ($data != [])
        $response = array_merge($response, $data);

    jsonResponse($response);
}

// function imageThumbnail(string $filename): string {
//     list($width, $height) = getimagesize($filename);

//     $newwidth = ;
//     $newheight = ;

//     $thumb = imagecreatetruecolor($newwidth, $newheight);
//     $source = imagecreatefromjpeg($filename);

//     imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
//     return imagejpeg($thumb);
// }

function imageResponse(string $filePath, bool $thumbnail = false): void {
    $filename = explode("/", $filePath);
    $filename = $filename[count($filename) - 1];

    http_response_code(200);
    header("Content-Description: Image");
    header("Content-Type: image/jpeg");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition", 'inline; filename="' . $filename . '"');
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    // if ($thumbnail)
    //     echo imageThumbnail($filePath);
    // else
    echo file_get_contents($filePath);
}

function errorResponse(String $message): void {
    $message = $message ?? LANG["REQUEST_ERROR"];
    jsonResponse([ "message" => $message ], 400);
}

// Initializing

try {
    if (!is_dir(LOG_DIR) && !mkdir(LOG_DIR))
        throw new Exception(LANG["ERROR_NO_LOGDIR"]);
}
catch (Throwable $th) {
    errorResponse($th->getMessage());
    exit;
}

$logger = new Monolog\Logger(ENV["APP_NAME"]);
$logger->pushHandler(new Monolog\Handler\StreamHandler(LOG_FILE, Monolog\Logger::DEBUG));

// Actions

function listFolderContent(Array $param): void {
    $fullFolderPath = ENV["BASE_FOLDER"] . "/" . ($param["folder"] ?? "");

    if (substr($fullFolderPath, -strlen($fullFolderPath)) != "/")
        $fullFolderPath .= "/";

    if (!is_dir($fullFolderPath))
        throw new Exception(LANG["ERROR_PATH_NOT_FOUND"]);

    $folders = [];
    $photos = [];

    $dirContent = dir($fullFolderPath);
    while ($entry = $dirContent->read())
        if (!in_array($entry, [".", ".."])) {
            if (is_dir($fullFolderPath . $entry))
                $folders[] = $entry;
            else
                $photos[] = $entry;
        }

    sort($folders);
    sort($photos);

    successResponse([
        "folders" => $folders,
        "photos" => $photos
    ]);
}

function getPhoto(Array $param): void {
    $fullPhotoPath = ENV["BASE_FOLDER"] . "/" . ($param["photo"] ?? "");

    if (!is_file($fullPhotoPath))
        throw new Exception(LANG["ERROR_PHOTO_NOT_FOUND"]);

    $thumbnail = $param["query"]["thumbnail"] ? true : false;
    imageResponse($fullPhotoPath, $thumbnail);
}

// Routes

$logger->info("Start app.");

$httpMethod = $_SERVER["REQUEST_METHOD"];
$uri = sanitizeUrl($_SERVER["REQUEST_URI"]);
$queryParams = makeQueryParams($_SERVER["QUERY_STRING"] ?? "");

try {
    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
        $r->addGroup(ENV["BASE_ROUTE"], function(FastRoute\RouteCollector $r) {
	        $r->addRoute("OPTIONS", "folder[/[{folder}]]", "success");
    	    $r->addRoute("GET", "folder[/[{folder:[- _a-zA-Z0-9\/\.]*}]]", "listFolderContent");

            $r->addRoute("OPTIONS", "photo/{photo}", "success");
    	    $r->addRoute("GET", "photo/{photo:[- _a-zA-Z0-9\/\.]*}", "getPhoto");
		});
    });

    $logger->info("Get route method. " . $httpMethod . " " . $uri);
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    $logger->info("Run route method.", $routeInfo);
    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::FOUND:
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            $vars["query"] = $queryParams;
            $handler($vars);
            break;
        case FastRoute\Dispatcher::NOT_FOUND:
        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        default:
            throw new Exception(LANG["ERROR_NOT_FOUND"]);
    }
}
catch (Throwable $th) {
    $logger->error("Error: " . $th->getMessage());
    errorResponse($th->getMessage());
}
