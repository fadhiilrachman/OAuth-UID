<?php

include './uid.class.php';
include 'config.sample.php';

$uid = new Uid(UID_CLIENT_ID, UID_CLIENT_SECRET, UID_REDIRECT_URI);

header('Location: ' . $uid->getAuthorizationUri(UID_SCOPES) );
