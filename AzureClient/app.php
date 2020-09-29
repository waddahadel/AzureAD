<?php
    include_once("client.php");
    $client=new MinervisAzureClient("http://testvm2.vpn.minervis.com:443");
    $client->requestTokens();
    //$client->fetch();
    $refresh=$client->getRefreshToken();
   // var_dump($refresh);
    $client->refreshToken($refresh);

    $client->verifyToken();
    $client->authenticate();
?>
