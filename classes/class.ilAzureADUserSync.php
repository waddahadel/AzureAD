<?php
require_once __DIR__ . "/../vendor/autoload.php";

use srag\Plugins\__AzureAD__\Config\Config;
use srag\DIC\AzureAD\DICTrait;

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


    protected $settings;

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



    public function __construct( $settings, $user_info)
    {
        global $DIC;
        $this->settings=Config::getInstance();

       
        $this->logger = $DIC->logger()->auth();
        $ilDB=$DIC['ilDB'];
        $this->db=&$ilDB;
	$this->logger->info("__construct_usersync");

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
        $this->usr_id = ilObjUser::_lookupId($this->int_account);
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->usr_id;
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
     * @return bool
     * @throws Exception
     */
    public function updateUser()
    {
        $this->logger->info("Session duration ".$this->settings->getValue("session_duration"));
        // if ($this->needsCreation() && !$this->settings->getValue("allow_sync")) {
        //     throw new /*AzureADSyncForbidden*/ Exception('No internal account given.');
        // }

        $this->transformToXml();

        $importParser = new ilUserImportParser();
        $importParser->setXMLContent($this->writer->xmlDumpMem(false));

        /*$roles = $this->parseRoleAssignments();
        $importParser->setRoleAssignment($roles);*/

        $importParser->setFolderId(USER_FOLDER_ID);
        $importParser->startParsing();
        $debug = $importParser->getProtocol();


        // lookup internal account
        $int_account = ilObjUser::_checkExternalAuthAccount(
            self::AUTH_MODE,
            $this->ext_account
        );
        $this->setInternalAccount($int_account);
        return true;
    }



    /**
     * transform user data to xml
     */
    protected function transformToXml()
    {
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
            $this->writer->xmlElement('Login', [], $this->int_account);
        }

        $this->writer->xmlElement('ExternalAccount', array(), $this->ext_account);
        $this->writer->xmlElement('AuthMode', array('type' => self::AUTH_MODE), null);

        //$this->parseRoleAssignments();

        if ($this->needsCreation()) {
            $this->writer->xmlElement('Active', array(), "true");
            $this->writer->xmlElement('TimeLimitOwner', array(), 7);
            $this->writer->xmlElement('TimeLimitUnlimited', array(), 1);
            $this->writer->xmlElement('TimeLimitFrom', array(), time());
            $this->writer->xmlElement('TimeLimitUntil', array(), time());
        }

        foreach ($this->user_info as $field => $value) {
            
            if (!$value) {
                $this->logger->debug('Ignoring unconfigured field: ' . $field);
                continue;
            }
            if (!$this->needsCreation()) {
                $this->logger->debug('Ignoring ' . $field . ' for update.');
                continue;
            }

            //$value = $this->valueFrom($connect_name);
            if (!strlen($value)) {
                $this->logger->debug('Cannot find user data in ' . $field);
                continue;
            }

            switch ($field) {
                case 'firstname':
                    $this->writer->xmlElement('Firstname', [], $value);
                    break;
                case 'displayName':
                    $names=$this->split_name($value);
                    $this->writer->xmlElement('Firstname', [], $names[0]);
                    $this->writer->xmlElement('Firstname', [], $names[1]);
                    break;

                case 'lastname':
                    $this->writer->xmlElement('Lastname', [], $value);
                    break;

                case 'mail':
                    $this->writer->xmlElement('Email', [], $value);
                    break;
                case 'department':
                    $this->writer->xmlElement('Department', [], $value);                    

                case 'companyName':
                    $this->writer->xmlElement('Institution', [], $value);
                    break;

                case 'employeedId':
                    $defs=$this->getDefinitions("PERNR");
                    if(isset($defs)){
                        $pernr=$defs[0];
                    }
                    $attributes=array(
                        "Id"=>$pernr['il_id'],
                        "Name"=>"PERNR"
                    );
                    $this->writer->xmlElement('UserDefinedField',$attributes, $value);
                    break;
                case 'mailNickName':
                    $this->writer->xmlElement('login',[], $value);
                    break;
                    
                    
            }
        }
        $long_role_id = ('il_' . IL_INST_ID . '_role_' . $this->settings->getValue("role"));
        $this->writer->xmlElement(
            'Role',
            [
                'Id' => $long_role_id,
                'Type' => 'Global',
                'Action' => 'Assign'
            ],
            "User"
        );
        $this->writer->xmlEndTag('User');
        $this->writer->xmlEndTag('Users');

        $this->logger->debug($this->writer->xmlDumpMem());
    }


    function getDefinitions($element=null){
        $definitions=array();
        $query = $element==null?"SELECT * FROM udf_definition ": "SELECT * FROM udf_definition where field_name=$element";
        $res = $this->db->query($query); 
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $definitions[$row->field_id]['field_id'] = $row->field_id;
            $definitions[$row->field_id]['field_name'] = $row->field_name;
            $definitions[$row->field_id]['field_type'] = $row->field_type;
            $definitions[$row->field_id]['il_id'] = 'il_' . $ilSetting->get('inst_id', 0) . '_udf_' . $row->field_id;

            // #16953
            $tmp = $sort = array();
            $is_numeric = true;
            foreach ((array) unserialize($row->field_values) as $item) {
                if (!is_numeric($item)) {
                    $is_numeric = false;
                }
                $sort[] = array("value" => $item);
            }
            foreach (ilUtil::sortArray($sort, "value", "asc", $is_numeric) as $item) {
                $tmp[] = $item["value"];
            }
                        
            $definitions[$row->field_id]['field_values'] = $tmp;
            $definitions[$row->field_id]['visible'] = $row->visible;
            $definitions[$row->field_id]['changeable'] = $row->changeable;
            $definitions[$row->field_id]['required'] = $row->required;
            $definitions[$row->field_id]['searchable'] = $row->searchable;
            $definitions[$row->field_id]['export'] = $row->export;
            $definitions[$row->field_id]['course_export'] = $row->course_export;
            $definitions[$row->field_id]['visib_reg'] = $row->registration_visible;
            $definitions[$row->field_id]['visib_lua'] = $row->visible_lua;
            $definitions[$row->field_id]['changeable_lua'] = $row->changeable_lua;
            $definitions[$row->field_id]['group_export'] = $row->group_export;
            // fraunhpatch start
            $definitions[$row->field_id]['certificate'] = $row->certificate;
            // fraunhpatch end
        }
        return $definitions;

    }
   
    private function split_name($name) {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
        return array($first_name, $last_name);
    }
    
    /**
     * @param string $connect_name
     */
    protected function valueFrom(string $connect_name) : string
    {
        if (!$connect_name) {
            return '';
        }
        if (!property_exists($this->user_info, $connect_name)) {
            $this->logger->debug('Cannot find property ' . $connect_name . ' in user info ');
            return '';
        }
        $val = $this->user_info->$connect_name;
        return $val;
    }
}
