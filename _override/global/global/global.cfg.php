<?php

//only enable a few languages for default setups
$cfg['setup']['i18n']['langs'] = array('en', 'fr');

//auto-detect setup location
$cfg['setup']['location']['urlroot'] = "http://".AnwEnv::_SERVER("HTTP_HOST").dirname(AnwEnv::_SERVER("PHP_SELF"))."/";

?>