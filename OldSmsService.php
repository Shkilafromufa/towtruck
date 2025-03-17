
private function sendSms($phone, $message)
{
    $token = 'eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJRV05sMENiTXY1SHZSV29CVUpkWjVNQURXSFVDS0NWODRlNGMzbEQtVHA0In0.eyJleHAiOjIwNDc1MjYwODQsImlhdCI6MTczMjE2NjA4NCwianRpIjoiZDdkYTNhNzktZmIzOS00ZDc5LWJmNTUtYTEzYTcyNWQ2Yzk5IiwiaXNzIjoiaHR0cHM6Ly9zc28uZXhvbHZlLnJ1L3JlYWxtcy9FeG9sdmUiLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiMjAyMjFkZTktMTgxNy00NTFlLWE1ZDEtODdkN2M1ZWZjNmNjIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoiNGI2NzFkZmQtNzJhMi00NGFjLTk0MmItNWYwODU2MWI4MzcyIiwic2Vzc2lvbl9zdGF0ZSI6ImIwZTc1ZWM4LTc1MTQtNDc3Ni1hZGFjLTM5YmNlZDc3MzI0ZSIsImFjciI6IjEiLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy1leG9sdmUiLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsiYWNjb3VudCI6eyJyb2xlcyI6WyJtYW5hZ2UtYWNjb3VudCIsIm1hbmFnZS1hY2NvdW50LWxpbmtzIiwidmlldy1wcm9maWxlIl19fSwic2NvcGUiOiJleG9sdmVfYXBwIHByb2ZpbGUgZW1haWwiLCJzaWQiOiJiMGU3NWVjOC03NTE0LTQ3NzYtYWRhYy0zOWJjZWQ3NzMyNGUiLCJ1c2VyX3V1aWQiOiIyZDdmY2ZlOC01MDczLTRjNWUtYWRhMi05ZGU2YTI3YjU1ZTkiLCJlbWFpbF92ZXJpZmllZCI6ZmFsc2UsImNsaWVudEhvc3QiOiIxNzIuMTYuMTYxLjE5IiwiY2xpZW50SWQiOiI0YjY3MWRmZC03MmEyLTQ0YWMtOTQyYi01ZjA4NTYxYjgzNzIiLCJhcGlfa2V5Ijp0cnVlLCJhcGlmb25pY2Ffc2lkIjoiNGI2NzFkZmQtNzJhMi00NGFjLTk0MmItNWYwODU2MWI4MzcyIiwiYmlsbGluZ19udW1iZXIiOiIxMjUwMDg0IiwiYXBpZm9uaWNhX3Rva2VuIjoiYXV0MDgyYTNmM2EtMGNjNy00YTc2LTk5YWQtMWFlMmQ3MTA0ZTlhIiwicHJlZmVycmVkX3VzZXJuYW1lIjoic2VydmljZS1hY2NvdW50LTRiNjcxZGZkLTcyYTItNDRhYy05NDJiLTVmMDg1NjFiODM3MiIsImN1c3RvbWVyX2lkIjoiNTg4MzAiLCJjbGllbnRBZGRyZXNzIjoiMTcyLjE2LjE2MS4xOSJ9.GuAXwLe5yQuN9RyLBZJriiVXBjMb2WAC4AiZwov9mtk16uewMhmyCXVeg7uT8615t5naJ_2KuGi4mEBTmbuXnSpeULNMROzWZ5vwVEgfPdiziPVkFbpJI5onjDoKmIxe1X0kXUB8nfS8oSp8J2VAuQXk93r6-_kmI3RmWNfpXP30f5Z9jMAPzs-yL_2KXFEvjJ-lCL3p9phyqcYmitzcYO5NsRTAlTvPWlRP7LLRwu7qpHdRCnYa1vUj4TVk_pjsxltSubzeQvNYAYA0N1qF9TTsOqEvOSjM2OKEUU18EGYJ4HQrc2uCgldgsth0zQRWdaVxXxFDm642WY1DO6fk2A';

    $client = new Client([
        'verify' => false, // Отключает проверку SSL
    ]);

    $response = $client->post('https://api.exolve.ru/messaging/v1/SendSMS', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'number' => '79912008329',
            'destination' => $phone,
            'text' => $message,
        ],
    ]);

    // Обработка ответа при необходимости
}


private function sendSms($phone, $message)
{
    $url = 'https://stepan.lego03@mail.ru:piq3n42lqqKnmyXT5HXLsv0kPJda@gate.smsaero.ru/v2/sms/send';
    $params = [
        'number' => $phone,
        'text' => $message,
        'sign' => 'SMS Aero', 
    ];
    $client = new \GuzzleHttp\Client([
        'verify' => false, // Отключает проверку SSL
    ]);

    $response = $client->get($url, [
        'query' => $params, 
    ]);
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    if ($statusCode === 200) {
    } else {
    }
}