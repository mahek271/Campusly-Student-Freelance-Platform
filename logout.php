<?php
session_start();
$_SESSION = [];
if(ini_get("session.use_cookies")){
  $p = session_get_cookie_params();
  setcookie(session_name(),'',time()-42000,$p["path"],$p["domain"],$p["secure"],$p["httponly"]);
}
session_destroy();

// Prevent browser from caching protected pages — clears back-button after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

header("Location: login.php");
exit();
