<?php

    require_once('./docusign-php-client/autoload.php');

    // DocuSign account credentials & Integrator Key
    $username = "[USERNAME]";
    $password = "[PASSWORD]";
    $integrator_key = "[INTEGRATOR_KEY]";
    $host = "https://demo.docusign.net/restapi";

    // create a new DocuSign configuration and assign host and header(s)
    $config = new DocuSign\eSign\Configuration();
    $config->setHost($host);
    $config->addDefaultHeader("X-DocuSign-Authentication", "{\"Username\":\"" . $username . "\",\"Password\":\"" . $password . "\",\"IntegratorKey\":\"" . $integrator_key . "\"}");

    /////////////////////////////////////////////////////////////////////////
    // STEP 1:  Login() API
    /////////////////////////////////////////////////////////////////////////

    // instantiate a new docusign api client
    $apiClient = new DocuSign\eSign\ApiClient($config);

    // we will first make the Login() call which exists in the AuthenticationApi...
    $authenticationApi = new DocuSign\eSign\Api\AuthenticationApi($apiClient);

    // optional login parameters
    $options = new \DocuSign\eSign\Api\AuthenticationApi\LoginOptions();

    // call the login() API
    $loginInformation = $authenticationApi->login($options);

    // parse the login results
    if(isset($loginInformation) && count($loginInformation) > 0)
    {
        // note: defaulting to first account found, user might be a 
        // member of multiple accounts
        $loginAccount = $loginInformation->getLoginAccounts()[0];
        if(isset($loginInformation))
        {
            $accountId = $loginAccount->getAccountId();
            if(!empty($accountId))
            {
                echo "Account ID = $accountId\n";
            }
        }
    }

    /////////////////////////////////////////////////////////////////////////
    // STEP 2:  Create & Send Envelope (aka Signature Request)
    /////////////////////////////////////////////////////////////////////////

    // set recipient information
    $recipientName = "[RECIPIENT_NAME]";
    $recipientEmail = "[RECIPIENT_EMAIL]";

    // configure the document we want signed
    $documentFileName = "[PATH/TO/DOCUMENT.PDF]";
    $documentName = "SignTest1.pdf";

    // instantiate a new envelopeApi object
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    // Add a document to the envelope
    $document = new DocuSign\eSign\Model\Document();
    $document->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $documentFileName)));
    $document->setName($documentName);
    $document->setDocumentId("1");

    // Create a |SignHere| tab somewhere on the document for the recipient to sign
    $signHere = new \DocuSign\eSign\Model\SignHere();
    $signHere->setXPosition("100");
    $signHere->setYPosition("100");
    $signHere->setDocumentId("1");
    $signHere->setPageNumber("1");
    $signHere->setRecipientId("1");

    // add the signature tab to the envelope's list of tabs
    $tabs = new DocuSign\eSign\Model\Tabs();
    $tabs->setSignHereTabs(array($signHere));

    // add a signer to the envelope
    $signer = new \DocuSign\eSign\Model\Signer();
    $signer->setEmail($recipientEmail);
    $signer->setName($recipientName);
    $signer->setRecipientId("1");
    $signer->setTabs($tabs);

    // Add a recipient to sign the document
    $recipients = new DocuSign\eSign\Model\Recipients();
    $recipients->setSigners(array($signer));
    $envelop_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
    $envelop_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");

    // set envelope status to "sent" to immediately send the signature request
    $envelop_definition->setStatus("sent");
    $envelop_definition->setRecipients($recipients);
    $envelop_definition->setDocuments(array($document));

    // create and send the envelope! (aka signature request)
    $envelop_summary = $envelopeApi->createEnvelope($accountId, $envelop_definition, null);

    echo "$envelop_summary\n";

?>
