<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['dds'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'ddsnack',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);

$db['tsc'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => 'Cnc@2023',
    'database' => 'Multitenant_ts',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);

$db['kar'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => 'Cnc@2023',
    'database' => 'Multitenant_kar',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);
$db['ebu'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username'     => 'root',
    'password'     => 'Cnc@2023',
    'database'     => 'Multitenant_ebu',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);
$db['bu'] = array(
    'dsn'      => '',
    'hostname' => 'localhost',
    'username'     => 'root',
    'password'     => 'Cnc@2023',
    'database'     => 'Multitenant_ebu',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);
$db['dem'] = array(
    'dsn'      => '',
    'hostname' => '78.140.140.200',
    'username'     => 'peoplehrm_devhrms',
    'password'     => 'ukY-ne0.Z2lf',
    'database'     => 'peoplehrm_devhrms',
    'dbdriver' => 'mysqli',
    // Other database configuration options...
);
?>
