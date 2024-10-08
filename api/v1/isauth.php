<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

echo json_encode(["isauth"=>$USER->IsAuthorized()]);