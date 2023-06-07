<?php
require_once "Customizing/global/plugins/Services/Authentication/AuthenticationHook/AzureAD/classes/class.ilAzureADSettings.php";
/**
 * Class ilAzureADUserSync
 *
 * @author Jephte Abijuru <jephte.abijuru@minervis.com>
 *
 *
 */
class ilAzureADUserSync
{
    const AUTH_MODE = 'azure';

    /**
     * @var \ilAzureADSettings
     */
    private $settings = null;

    /**
     * @var \ilLogger
     */
    protected $logger;

    /**
     * @var \ilXmlWriter
     */
    private $writer;
    /**
     * @var array
     */
    private $user_info = [];

    /**
     * @var string
     */
    private $ext_account = '';


    /**
     * @var string
     */
    private $int_account = '';

    /**
     * @var int
     */
    private $usr_id = 0;

    private $db;
    private $migrate=false;



    public function __construct($settings, $user_info)
    {
        global $DIC;
        $this->settings = ilAzureADSettings::getInstance();

       
        $this->logger = $DIC->logger()->auth();
        $ilDB=$DIC['ilDB'];
        $this->db=&$ilDB;

        $this->writer = new ilXmlWriter();

        $this->user_info = $user_info;
    }

    /**
     * @param string $ext_account
     */
    public function setExternalAccount(string $ext_account)
    {
        $this->ext_account = $ext_account;
    }

    /**
     * @param string $int_account
     */
    public function setInternalAccount(string $int_account)
    {
        $this->int_account = $int_account;
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->usr_id;
    }

    /**
     * @param int $usr_id
     */
    public function setUserId(int $usr_id): void
    {
        $this->usr_id = $usr_id;
    }

    public function updateLogin($new_login)
    {
        $user=new ilObjUser($this->usr_id);
        $user->updateLogin($new_login);
    }

    /**
     * @return bool
     */
    public function needsCreation() : bool
    {
        $this->logger->dump($this->int_account, \ilLogLevel::DEBUG);
        return strlen($this->int_account) == 0;
    }
    
    /**
     * setMigrationState
     *
     * @param  mixed $migrate
     * @return void
     */
    public function setMigrationState(bool $migrate=false)
    {
        $this->migrate=$migrate;
    }
    
    /**
     * getMigrationState
     *
     * @return bool
     */
    public function getMigrationState() : bool
    {
        return $this->migrate;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function updateUser()
    {


        $this->transformToXml();

        $importParser = new ilUserImportParser();
        $importParser->setXMLContent($this->writer->xmlDumpMem(false));

        if ($this->needsCreation()) {
            $roles = [$this->settings->getRole() => $this->settings->getRole() ];
            $importParser->setRoleAssignment($roles);
        }

        $importParser->setFolderId(USER_FOLDER_ID);
        $importParser->startParsing();
        $debug = $importParser->getProtocol();


        // lookup internal account
        $int_account = ilObjUser::_checkExternalAuthAccount(
            self::AUTH_MODE,
            $this->ext_account
        );
        $this->setInternalAccount($int_account);
        $this->setUserId(ilObjUser::_lookupId($int_account));
        return true;
    }



    /**
     * transform user data to xml
     */
    protected function transformToXml()
    {
        $login= $this->ext_account;
        if ($this->getMigrationState()) {
            $login=$this->user_info->unique_name;
        }
        $this->writer->xmlStartTag('Users');
        
        if ($this->needsCreation()) {
            $this->writer->xmlStartTag('User', ['Action' => 'Insert']);
            $this->writer->xmlElement('Login', [], ilAuthUtils::_generateLogin($this->ext_account));
        } else {
            $this->writer->xmlStartTag(
                'User',
                [
                    'Id' => $this->getUserId(),
                    'Action' => 'Update'
                ]
            );
            $this->writer->xmlElement('Login', [], $login);
        }

        $this->writer->xmlElement('ExternalAccount', array(), $this->ext_account);
        $this->writer->xmlElement('AuthMode', array('type' => self::AUTH_MODE), null);

        
        if ($this->needsCreation()) {
            $this->writer->xmlElement('Active', array(), "true");
            $this->writer->xmlElement('TimeLimitOwner', array(), 7);
            $this->writer->xmlElement('TimeLimitUnlimited', array(), 1);
            $this->writer->xmlElement('TimeLimitFrom', array(), time());
            $this->writer->xmlElement('TimeLimitUntil', array(), time());
        }

        $user_email = "";
        foreach ($this->user_info as $field => $value) {
            //	    $this->logger->info("transformToXml_field:".$field ." _value:". $value);
            if (!$value) {
                $this->logger->info('Ignoring unconfigured field: ' . $field);
                continue;
            }
            if (!$this->needsCreation()) {
                $this->logger->debug('Ignoring ' . $field . ' for update.');
                //continue;
            }

            
            if (!is_array($value) && !strlen($value)) {
                $this->logger->info('Cannot find user data in ' . $field);
                continue;
            }
            

            switch ($field) {
                case 'given_name':
                    $this->writer->xmlElement('Firstname', [], $value);
                    break;

                case 'family_name':
                    $this->writer->xmlElement('Lastname', [], $value);
                    break;

                case 'mail':
                    $user_email = $value;
                    break;
                case 'department':
                    $this->writer->xmlElement('Department', [], $value);
                    break;

                case 'companyName':
                    $this->writer->xmlElement('Institution', [], $value);
                    break;
                case 'jobTitle': 
                    $this->writer->xmlElement(
                        'UserDefinedField',
                        [
                            'Name' => "JobTitle"
                        ],
                        $value
                    );
                    break;

                case 'employeeId':
                    $this->writer->xmlElement(
                        'UserDefinedField',
                        [
                            'Name' => "PERNR"
                        ],
                        $value
                    );
                    break;
                case 'unique_name':
                    if (strlen($user_email) === 0&& strpos($this->user_info->unique_name, '@') !==0) {
                        $user_email = $value;
                    }
                    
                    break;
                default:
                    //Do nothing
                    
                    
            }
        }
       
        $this->writer->xmlElement('Email', [], $user_email);

        
        if ($this->needsCreation()) {
            $long_role_id = ('il_' . IL_INST_ID . '_role_' . $this->settings->getRole());
            $this->writer->xmlElement(
                'Role',
                [
                    'Id' => $long_role_id,
                    'Type' => 'Global',
                    'Action' => 'Assign'
                ],
                null
            );
        }
        $this->writer->xmlEndTag('User');
        $this->writer->xmlEndTag('Users');

        $this->logger->debug("User XML: ".$this->writer->xmlDumpMem());
    }
}
