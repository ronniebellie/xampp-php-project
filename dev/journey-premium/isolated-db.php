<?php
// These mutating legacy fixtures must never use a real application schema.
require_once dirname(__DIR__,2).'/includes/config_bootstrap.php';
$testConfig=rb_config();
if(PHP_SAPI!=='cli'||!preg_match('/^rb_journey_fixture_[a-f0-9]{16}$/D',$testConfig['db']['name']??''))throw new RuntimeException('Run through dev/phase-journey-legacy-database-fixture.php in an isolated schema.');
require_once dirname(__DIR__,2).'/includes/db_config.php';
