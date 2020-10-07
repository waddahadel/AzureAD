



## CLIENT  
The client  has 3 endpoints: refresh, request and verify token. 
main functions: **todo**
content extraction: **todo**


## PLUGIN

To enable the plugin's auth mechanism:  
`Administration>Users and Roles > Authentication and Registration>Global roles available on registration form` and choose -auth-azure- for Guest

#### Main Structure: TODO
##### Usersync
##### Provider
##### Authcredentials
##### Session management


### Description
See in [doc/DESCRIPTION.md](./doc/DESCRIPTION.md)

### Documentation
See in [doc/DOCUMENTATION.md](./doc/DOCUMENTATION.md)

### Installation

#### Install AzureAD-Plugin
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/Authentication/AuthenticationHook
cd Customizing/global/plugins/Services/Authentication/AuthenticationHook
git clone http://{{USER}}@gitblit.minervis.com/r/GZ/authentication-plugin.git AzureAD
```
Update, activate and config the plugin in the ILIAS Plugin Administration

#### Requirements
* ILIAS 5.4
* PHP >=7.0
