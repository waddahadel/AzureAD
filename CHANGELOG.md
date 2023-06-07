# Changelog

## 2020-09-29  [0.1.0]
- First version

## 2021-03-03 [0.1.1]  
- updated the API endpoints: allow versioning  
- PCR-2 Fixes  
- clean the code
- remove the slot id functionality: mo longer necessary in 6.x
- versioning 

## 2021-03-25 [0.1.2]

## 2022-02-04 [0.1.3]  
- Add the attribute JobTitle
- Add the cronjob to check if users are still active
- Adjust the Client(local code) to the changes made to the API 
- Develop a cronjob plugin and connect it with this plugin(see CronJob plugin)
- Fix Exception handling

## 2023-05-07 [0.2.0] 
- Enable User retrievals using UDFs
- A cron job to update the job title
- Add a status logger and a GUI in the Configs
- Fix the login issues for internal accounts
- reactivate the deactivated accounts if they are reactivated in the azureAD
