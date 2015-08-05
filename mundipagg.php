<?php

try
{
    // Carrega dependências
    require_once(dirname(__FILE__) . '/vendor/autoload.php');

    // Extrai somente números do valor
    $amountInCents = preg_replace("/[^0-9a-zA-Z ]/", "", $_POST['amount']);

    // Cria um objeto de cartão de crédito a partir dos dados recebidos
    $creditCard = \MundiPagg\One\Helper\CreditCardHelper::createCreditCard($_POST['number'], $_POST['name'], $_POST['expiry'], $_POST['cvc']);

    // Define o ambiente utilizado (produção ou homologação)
    \MundiPagg\ApiClient::setEnvironment(\MundiPagg\One\DataContract\Enum\ApiEnvironmentEnum::PRODUCTION);

    // Define a chave da loja
    \MundiPagg\ApiClient::setMerchantKey("0CF45D90-81DF-4778-9034-5ABA338587D4");

    // Cria objeto requisição
    $createSaleRequest = new \MundiPagg\One\DataContract\Request\CreateSaleRequest();

    // Define dados do pedido
    $createSaleRequest->addCreditCardTransaction()
        ->setPaymentMethodCode(\MundiPagg\One\DataContract\Enum\PaymentMethodEnum::SIMULATOR)
        ->setAmountInCents($amountInCents)
        ->setCreditCard($creditCard)
        ;

    // Cria um objeto ApiClient
    $apiClient = new MundiPagg\ApiClient();

    // Faz a chamada para criação
    $createSaleResponse = $apiClient->createSale($createSaleRequest);

    // Mapeia resposta
    $httpStatusCode = $createSaleResponse->isSuccess() ? 201 : 401;
    $response = array("message" => $createSaleResponse->getData()->CreditCardTransactionResultCollection[0]->AcquirerMessage);
}
catch (\MundiPagg\One\DataContract\Report\CreditCardError $error)
{
    $httpStatusCode = 400;
    $response = array("message" => $error->getMessage());
}
catch (\MundiPagg\One\DataContract\Report\ApiError $error)
{
    $httpStatusCode = @$error->errorCollection->ErrorItemCollection[0]->ErrorCode;
    $response = array("message" => @$error->errorCollection->ErrorItemCollection[0]->Description);
}
catch (\Exception $ex)
{
    $httpStatusCode = 500;
    $response = array("message" => "Ocorreu um erro inesperado.");
}
finally
{
    // Devolve resposta
    header('Content-Type: application/json');
    http_response_code($httpStatusCode);
    print json_encode($response);
}