# seablast-logger
Logger compliant with PSR-3 http://www.php-fig.org/psr/psr-3/ and Tracy.

By default it logs the following levels of information:
- fatal
- error
- warning
- info
- debug

And ignores
- speed

The logging level cam be however shifted, so that dev environment writes to log much more info than the application in the production environment.

Following things may be configured when instatiating:
```php
$conf = array(
                'logging_level' => 5, //log up to the level set here, default=5 = debug//logovat az do urovne zde uvedene: 0=unknown/default_call 1=fatal 2=error 3=warning 4=info 5=debug/default_setting 6=speed  //aby se zalogovala alespoň missing db musí být logování nejníže defaultně na 1 //1 as default for writing the missing db at least to the standard ErrorLog
                'logging_level_name' => array(0 => 'unknown', 1 => 'fatal', 'error', 'warning', 'info', 'debug', 'speed'),
                'logging_file' => '', //soubor, do kterého má my_error_log() zapisovat
                'logging_level_page_speed' => 5, //úroveň logování, do které má být zapisována rychlost vygenerování stránky
                'error_log_message_type' => 0, //parameter message_type http://cz2.php.net/manual/en/function.error-log.php for my_error_log; default is 0, i.e. to send message to PHP's system logger; recommended is however 3, i.e. append to the file destination set either in field $this->conf['logging_file or in table system
                'mail_for_admin_enabled' => false, //fatal error may just be written in log //$backyardMailForAdminEnabled = "rejthar@gods.cz";//on production, it is however recommended to set an e-mail, where to announce fatal errors
                'log_monthly_rotation' => true, //true, pokud má být přípona .log.Y-m.log (výhodou je měsíční rotace); false, pokud má být jen .log (výhodou je sekvenční zápis chyb přes my_error_log a jiných PHP chyb)
                'log_standard_output' => false, //true, pokud má zároveň vypisovat na obrazovku; false, pokud má vypisovat jen do logu
                'log_profiling_step' => false, //110812, my_error_log neprofiluje rychlost //$PROFILING_STEP = 0.008;//110812, my_error_log profiluje čas mezi dvěma měřenými body vyšší než udaná hodnota sec
);
```
