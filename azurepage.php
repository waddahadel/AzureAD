<?php
include_once './Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_SHIBBOLETH);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();
ilLoggerFactory::getLogger('ilAzureADPlugin')->info("azurepage_init");
// authentication is done here ->
$ilCtrl->initBaseClass("ilStartUpGUI");
$ilCtrl->setCmd('doAzureAuthentication');
$ilCtrl->setTargetScript("ilias.php");
$ilCtrl->callBaseClass();
