<?php
    include_once("globusClient.php");
    $client=new MinervisAzureClient("https://api-test.globus.de");
    $client->requestTokens();
    //$client->fetch();
    $refresh=$client->getRefreshToken();
    var_dump($client->getUserInfo());
    /*$client->refreshToken($refresh);

    $client->verifyToken();
    $client->authenticate();*/
?>
