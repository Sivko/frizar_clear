<?
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once $_SERVER["DOCUMENT_ROOT"] . "/api/v2/vendor/autoload.php";

require_once "1.php";
// require_once "../api/v2/vendor/bshaffer/oauth2-server-php/src/OAuth2/Storage/Pdo.php";

// require_once "../api/v2/vendor/bshaffer/oauth2-server-php/src/OAuth2/Storage/Pdo.php";

use App\Storage\Bitrix;
use OAuth2\GrantType;
use OAuth2\Server;

$pdo = new \PDO('mysql:host=db;dbname=frizar', 'root', 'root');

$storage = new Bitrix($pdo);

$x = $storage->getUser("");

echo "<pre>";
var_dump($x);
echo "</pre>";