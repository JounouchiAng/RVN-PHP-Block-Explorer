<?php
if (!defined('IN_SCRIPT')) {die('Invalid attempt!');}
$config = array(
    "name" => "Ravencoin", // Coin name/title
    "symbol" => "RVN", // Coin symbol
    "description" => "Ravencoin Explorer which include blocks, nodes and many more.",
    "homepage" => "https://rvnexplorer.just-a-web.com/",
    "root_path" => "/", //start with '/', end with '/'
    "copy_name" => "Ravencoin (RVN) Explorer",
    "start_year" => 2018,
    "explorer_name" => "Ravencoin (RVN) Block Explorer",
    "explorer_path" => "", //do not start with '/',  but end with '/', if root write ""
    "theme" => "rvn",
    "url_rewrite" => false,
    "rpc_host" => "127.0.0.1", // Host/IP for the daemon
    "rpc_port" => 8766, // RPC port for the daemon
    "rpc_user" => "rpcuser", // 'rpcuser' from the coin's .conf
    "rpc_password" => "rpcpassword", // 'rpcpassword' from the coin's .conf
    "proofof" => "pow", //pow,pos
    "nTargetTimespan" => 1209600, //14 * 24 * 60 * 60
    "nTargetSpacing" => 180, //3 * 60
    "blocks_per_page" => 6,
    "date_format" => "Y-m-d H:i:s",
    "refresh_interval" => 180, //seconds
    "retarget_diff_since" => 0,
    "ipfsUrl" => "https://ipfs.io/ipfs/",
);