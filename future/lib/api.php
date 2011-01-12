<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

function apiGetArg($key, $default='') {
  return isset($_REQUEST[$key]) ? stripslashes(trim($_REQUEST[$key])) : $default;
}
