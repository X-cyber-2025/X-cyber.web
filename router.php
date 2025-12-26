<?php
$data = json_decode(file_get_contents("php://input"), true);

$router_ip = $data["ip"] ?? "";
$username  = $data["user"] ?? "";
$password  = $data["pass"] ?? "";

if (!$router_ip || !$username || !$password) {
    echo json_encode(["error" => "Missing input"]);
    exit;
}

$cookie = __DIR__ . "/cookie.txt";

/* ---- LOGIN ---- */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://$router_ip/");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    "username" => $username,
    "password" => $password
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
curl_close($ch);

/* ---- DEVICE LIST ---- */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://$router_ip/data/status.device.json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
curl_close($ch);

$json = json_decode($res, true);
if (!$json || !isset($json["dhcpClient"])) {
    echo json_encode(["error" => "Login failed or unsupported router"]);
    exit;
}

$devices = [];
foreach ($json["dhcpClient"] as $d) {
    $devices[] = [
        "ip"   => $d["ip"],
        "mac"  => $d["mac"],
        "name" => $d["name"] ?? "Unknown"
    ];
}

echo json_encode([
    "count" => count($devices),
    "devices" => $devices
]);