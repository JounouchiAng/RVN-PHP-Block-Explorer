<?php
$version = '0.0.0.7';

define("IN_SCRIPT", true);

require_once 'conf/config.php';
require_once 'libs/functions.php';
require_once 'libs/easybitcoin.php';

$bitcoinrpc = new Bitcoin($config["rpc_user"], $config["rpc_password"], $config["rpc_host"], $config["rpc_port"]);

//init url path prefix
if ($config["url_rewrite"]) {
    $name = isset($_GET['name']) ? $_GET['name'] : "";
    list($url_param_get_action, $url_param_get_value) = explode("/", $name);
    $url_path["addr"] = $config["root_path"] . $config["explorer_path"] . 'addr/';
    $url_path["addrTx"] = $config["root_path"] . $config["explorer_path"] . 'addrTx/';
    $url_path["asset"] = $config["root_path"] . $config["explorer_path"] . 'asset/';
    $url_path["nodelist"] = $config["root_path"] . $config["explorer_path"] . 'nodelist/';
    $url_path["height"] = $config["root_path"] . $config["explorer_path"] . 'height/';
    $url_path["blockhash"] = $config["root_path"] . $config["explorer_path"] . 'blockhash/';
    $url_path["tx"] = $config["root_path"] . $config["explorer_path"] . 'tx/';
    $url_path["block"] = $config["root_path"] . $config["explorer_path"] . 'block/';
    $url_path["search"] = $config["root_path"] . $config["explorer_path"] . 'search/';
} else {
    $url_param_get_action = isset($_GET['action']) ? $_GET['action'] : "";
    $url_param_get_value = isset($_GET['v']) ? $_GET['v'] : "";
    $url_path["addr"] = $config["root_path"] . $config["explorer_path"] . '?action=addr&v=';
    $url_path["addrTx"] = $config["root_path"] . $config["explorer_path"] . '?action=addrTx&v=';
    $url_path["asset"] = $config["root_path"] . $config["explorer_path"] . '?action=asset&v=';
    $url_path["nodelist"] = $config["root_path"] . $config["explorer_path"] . '?action=nodelist&v=';
    $url_path["height"] = $config["root_path"] . $config["explorer_path"] . '?action=height&v=';
    $url_path["blockhash"] = $config["root_path"] . $config["explorer_path"] . '?action=blockhash&v=';
    $url_path["tx"] = $config["root_path"] . $config["explorer_path"] . '?action=tx&v=';
    $url_path["block"] = $config["root_path"] . $config["explorer_path"] . '?action=block&v=';
    $url_path["search"] = $config["root_path"] . $config["explorer_path"] . '?action=search&v=';
}

switch ($url_param_get_action) {
    case "":
        $getrawmempool = $bitcoinrpc->getrawmempool();
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->error !== '') {
            exit($bitcoinrpc->error);
        }
        foreach ($getrawmempool as $key => $tx) {
            $transaction_detail = array();
            $transaction_detail['tx'] = $tx;
            $rawtransaction = $bitcoinrpc->getrawtransaction($tx, 1);
            if ($rawtransaction === false) {
                continue;
            }

            foreach ($rawtransaction['vout'] as $vout) {
                if ($vout['value'] > 0.0) {
                    $transaction_detail['vout'][$vout['n']]['addresses'] = $vout['scriptPubKey']['addresses'];
                    $transaction_detail['vout'][$vout['n']]['value'] = trim_dotzero($vout['value']);
                }
            }

            $output['transactions'][] = $transaction_detail;
        }

        if (count((is_countable($output['transactions'])?$output['transactions']:[])) > 0) {
            foreach ($output['transactions'] as $value) {
                $output['memory_pool_list_tbody'] .= '<tr><td class="text-start">';
                $output['memory_pool_list_tbody'] .= '<a class="text-info" href="' . $url_path["tx"] . $value["tx"] . '">' . $value["tx"] . '</a>';
                $output['memory_pool_list_tbody'] .= '</td>';

                $sumValueOut = 0;
                foreach ($value["vout"] as $vout) {
                    $sumValueOut = $sumValueOut + $vout["value"];
                }

                $output['memory_pool_list_tbody'] .= '<td>' . $sumValueOut . '</td>';

                //$output['memory_pool_list_tbody'] .= '<td class="text-start" colspan="2">';
                //$output['memory_pool_list_tbody'] .= '<table class="table table-borderless text-white-50 table-sm"><tbody>';
                //foreach ($value["vout"] as $vout) {
                //    $output['memory_pool_list_tbody'] .= '<tr><td class="text-start" style="width:40%">' . $vout["value"] . ' ' . $config["symbol"] . '</td><td class="text-start">';
                //    foreach ($vout["addresses"] as $address) {
                //        $output['memory_pool_list_tbody'] .= $address . '<br>';
                //    }
                //    $output['memory_pool_list_tbody'] .= '</td></tr>';
                //}
                //$output['memory_pool_list_tbody'] .= '</tbody></table></td>';
                $output['memory_pool_list_tbody'] .= '</tr>';
            }
        } else {
            $output['memory_pool_list_tbody'] = '<tr><td class="text-start" colspan="3">Memory pool is currently empty.</td></tr>';
        }
        $output["memory_pool_list_tbody_display"] = "";
        //$output["get_nextdiff_url"] = $config["root_path"] . $config["explorer_path"] . "ajax.php";
        //$output["nextdiff_display"] = "";
    case "height":
        //baseinfo
        $info = $bitcoinrpc->getinfo();
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->error !== '') {
            exit($bitcoinrpc->error);
        }
        $output['blockcount'] = $info['blocks'];
        $output['blockcount_url'] = "<a class=\"btn3\" href=\"" . $url_path["height"] . $info['blocks'] . "\">" . $info['blocks'] . "</a>";
        $output['connections'] = $info['connections'];
        if ($config["proofof"] === "pow") {
            $output['difficulty'] = short_number($info['difficulty'], 1000, 3, "");
        } else {
            $output['difficulty'] = $info['difficulty']['proof-of-work'];
            $output['difficulty_pos'] = $info['difficulty']['proof-of-stake'];
        }

        $mininginfo = $bitcoinrpc->getmininginfo();
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->error !== '') {
            exit($bitcoinrpc->error);
        }
        $hashrate = $mininginfo['networkhashps'];
        if (!$hashrate) {
            $hashrate = $bitcoinrpc->getnetworkhashps();
            if ($bitcoinrpc->status !== 200 && $bitcoinrpc->error !== '') {
                exit($bitcoinrpc->error);
            }
        }
        $output['hashrate'] = short_number($hashrate, 1000, 3, " ") . "H/s";
        $output['chain'] = $mininginfo['chain'];
        $nTarget = $config["nTargetTimespan"] / $config["nTargetSpacing"];
        $output['nextdiff_blocks'] = $nTarget - ($info['blocks'] - $config["retarget_diff_since"]) % $nTarget;
        $output['nextdiff_timeline'] = gmdate($config["date_format"], time() + $output['nextdiff_blocks'] * $config["nTargetSpacing"]);

        //blocklist
        $height = (int) ($url_param_get_value ? $url_param_get_value : $info['blocks']);
        if ($height > $info['blocks']) {
            send404();
        } else if ($height < 0) {
            send404();
        }

        $i = $height; //must convert to number, if string will not get block hash
        $n = $config["blocks_per_page"];
        while ($i >= 0 && $n--) {
            $block_detail = array();
            $blockhash = $bitcoinrpc->getblockhash($i);
            $block = $bitcoinrpc->getblock($blockhash);
            $block_detail['hash'] = $block['hash'];
            $block_detail['height'] = $block['height'];
            $block_detail['difficulty'] = $block['difficulty'];
            // $block_detail['time'] = $block['time'];
            $block_detail['date'] = gmdate($config["date_format"], $block['time']);
            $block_detail['size'] = short_number($block['size'], 1024, 3, " ") . "B";

            $tx_count = 0;
            $value_out = 0;
            if (count($block['tx']) > 0) {
                foreach ($block['tx'] as $tx) {
                    $tx_count++;
                    $rawtransaction = $bitcoinrpc->getrawtransaction($tx, 1);
                    if ($rawtransaction === false) {
                        continue;
                    }

                    foreach ($rawtransaction['vout'] as $vout) {
                        $value_out += $vout['value'];
                    }
                }}
            $block_detail['tx_count'] = $tx_count;
            $block_detail['value_out'] = $value_out;

            $output['blocks'][] = $block_detail;
            $i--;
        }
        // echo json_encode($output);

        if (count($output['blocks']) > 0) {
            foreach ($output['blocks'] as $value) {
                $output['block_list_tbody'] .= "<tr><td><a class=\"text-info\" href=\"" . $url_path["block"] . $value["height"] . "\">" . $value["height"] . "</a></td><td>" . $value["difficulty"] . "</td><td>" . $value["date"] . "</td><td>" . $value["tx_count"] . "</td><td>" . $value["value_out"] . "</td><td>" . $value["size"] . "</td></tr>";
            }
        }

        if ($height < $output['blockcount']) {
            if ($output['blockcount'] - $height >= $config["blocks_per_page"]) {
                $value = $height + $config["blocks_per_page"];
            } else {
                $value = $output['blockcount'];
            }
            $output["newer_page"] = $url_path["height"] . $value;
            $output["newer_page_display"] = "";
        } else {
            $output["newer_page"] = "";
            $output["newer_page_display"] = ' style="display: none;"';
        }
        if ($height - count($output['blocks']) >= 0) {
            $output["older_page"] = $url_path["height"] . ($height - $config["blocks_per_page"]);
            $output["older_page_display"] = "";
        } else {
            $output["older_page"] = "";
            $output["older_page_display"] = ' style="display: none;"';
        }

        if (!$output['memory_pool_list_tbody']) {
            $output["memory_pool_list_tbody"] = "";
            $output["memory_pool_list_tbody_display"] = ' style="display: none;"';
        }
        if (!$output['get_nextdiff_url']) {
            $output["get_nextdiff_url"] = "";
            $output["nextdiff_display"] = ' style="display: none;"';
        }

        if ($url_param_get_action) {
            $output["title"] = "Block list since height " . $height . " - ";
            $output["description"] = $config["explorer_name"] . " block list page. This page shows latest " . $config["blocks_per_page"] . " blocks since height " . $height;
        } else {
            $output["title"] = "";
            $output["description"] = $config["explorer_name"] . " homepage. This page shows latest " . $config["blocks_per_page"] . " blocks.";
        }

        exit(get_html("index-body", $output));
        break;
    case "nodelist":
        $peerInfo = $bitcoinrpc->getpeerinfo();
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }

        $output = get_output_from_getpeerinfo($peerInfo);

        exit(get_html("nodelist-body", $output));
        break;
    case "addr":
        $address = $url_param_get_value;
        $addressInfo = $bitcoinrpc->getaddressbalance($address, true);
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }

        //$obj = (object) [
        //    'addresses' => [ $address ]
        //];
        //$addressTx = $bitcoinrpc->getaddresstxids($obj, true);
        //if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
        //    send404();
        //}

        //$output = get_output_from_addressbalance($address, $addressInfo, $addressTx);
        $output = get_output_from_addressbalance($address, $addressInfo);

        exit(get_html("address-body", $output));
        break;
    case "asset":
        $assetName = $url_param_get_value;
        $assetData = $bitcoinrpc->getassetdata($assetName);
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }

        if ($assetData == null) {
             send404();
        }

        $output = get_output_from_getassetdata($assetName, $assetData);

        exit(get_html("asset-body", $output));
        break;
    case "block":
        $height = (int) $url_param_get_value;
        if ($height < 0) {
            send404();
        }

        $blockhash = $bitcoinrpc->getblockhash($height);
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -8) {
            send404();
        }

        $block = $bitcoinrpc->getblock($blockhash);
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }

        $output = get_output_from_block($block);

        exit(get_html("block-body", $output));
        break;
    case "blockhash":
        $blockhash = $url_param_get_value;
        if (!$blockhash || !preg_match('/^[0-9a-f]{64}$/i', $blockhash)) {
            send404();
        }

        $block = $bitcoinrpc->getblock($blockhash);
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }

        $output = get_output_from_block($block);

        exit(get_html("block-body", $output));
        break;
    case "tx":
        $tx = $url_param_get_value;
        if (!$tx || !preg_match('/^[0-9a-f]{64}$/i', $tx)) {
            send404();
        }

        $rawtransaction = $bitcoinrpc->getrawtransaction($tx, 1);
        // echo json_encode($bitcoinrpc);
        // exit;
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->response['error']['code'] == -5) {
            send404();
        }
        if ($bitcoinrpc->status !== 200 && $bitcoinrpc->error !== '') {
            exit('failed to connect - node not reachable, or user/pass incorrect');
        }

        $vinTotal = 0;
        $voutTotal = 0;
        $notCoinBase = false;

        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\" style=\"width:30%\">txid</th><td class=\"text-start\">" . $rawtransaction["txid"] . "</td></tr>";
        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Block Hash</th><td class=\"text-start\">" . '<a class="text-info" href="' . $url_path["blockhash"] . $rawtransaction["blockhash"] . '">' . $rawtransaction["blockhash"] . "</a></td></tr>";
        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Time</th><td class=\"text-start\">" . gmdate($config["date_format"], $rawtransaction["time"]) . " UTC</td></tr>";
        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Version</th><td class=\"text-start\">" . $rawtransaction["version"] . "</td></tr>";
        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Confirmations</th><td class=\"text-start\">" . $rawtransaction["confirmations"] . "</td></tr>";

        $output['tx_list_tbody'] .= '<tr class="text-start">';
        $output['tx_list_tbody'] .= '<td>';
        if (count($rawtransaction['vin']) > 0) {
            foreach ($rawtransaction['vin'] as $key => $vin) {
                if (isset($vin['coinbase'])) {
                    $output['tx_list_tbody'] .= 'coinbase:<br>' . $vin["coinbase"];
                } else {
                    //if ($vin['vout'] > 0) {
                    //    $output['tx_list_tbody'] .= 'txid: <a class="text-info" href="' . $url_path["tx"] . $vin["txid"] . '">' . $vin["txid"] . '</a><br>';
                    //} else {
                    //    $output['tx_list_tbody'] .= 'txid: ' . $vin["txid"] . '<br>';
                    //}
                    //$output['tx_list_tbody'] .= 'vout: ' . $vin['vout'] . '<br><br>';
                    $output['tx_list_tbody'] .= 'txid: <a class="text-info" href="' . $url_path["tx"] . $vin["txid"] . '">' . $vin["txid"] . '</a><br>';
                    $output['tx_list_tbody'] .= '<a class="text-info" href="' . $url_path["addr"] . $vin["address"] . '">' . $vin["address"] . '</a><br>';
                    $output['tx_list_tbody'] .= trim_dotzero($vin['value']) . ' ' . $config["symbol"] . '<br><br>';

                    $vinTotal = $vinTotal + $vin["valueSat"];
                    $notCoinBase = true;
                }
            }
        }
        $output['tx_list_tbody'] .= '</td>';
        $output['tx_list_tbody'] .= '<td>';
        if (count($rawtransaction['vout']) > 0) {
            foreach ($rawtransaction['vout'] as $vout) {
                if (count((is_countable($vout['scriptPubKey']['addresses'])?$vout['scriptPubKey']['addresses']:[])) > 0) {
                    foreach ($vout['scriptPubKey']["addresses"] as $address) {
                        //$output['tx_list_tbody'] .= $address . '<br>';
                        $output['tx_list_tbody'] .= '<a class="text-info" href="' . $url_path["addr"] . $address . '">' . $address . '</a><br>';
                    }
                }

                if ($vout['value'] > 0) {
                    $output['tx_list_tbody'] .= trim_dotzero($vout['value']) . ' ' . $config["symbol"] . '<br><br>';
                }
                else {
                    $output['tx_list_tbody'] .= $vout['scriptPubKey']['asset']['amount'] . ' ' . $vout['scriptPubKey']['asset']['name'] . '<br><br>';
                }

                $voutTotal = $voutTotal + $vout["value"];
            }
        }
        $output['tx_list_tbody'] .= '</td>';
        $output['tx_list_tbody'] .= '</tr>';

        //Show summary of transaction
        if ($notCoinBase)
            $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Total Input</th><td class=\"text-start\">" . convertToBTCFromSatoshi($vinTotal) . ' ' . $config["symbol"] . "</td></tr>";

        $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Total Output</th><td class=\"text-start\">" . $voutTotal . ' ' . $config["symbol"] . "</td></tr>";

        if ($notCoinBase)
            $output['transaction_detail_tbody'] .= "<tr><th class=\"text-end\">Total Fee</th><td class=\"text-start\">" . trim_dotzero(convertToBTCFromSatoshi($vinTotal) - $voutTotal) . ' ' . $config["symbol"] . "</td></tr>";

        $output["title"] = "Transaction Detail " . $tx . " - ";
        $output["description"] = "This transaction's txid is " . $rawtransaction["txid"] . ". It was made transaction at " . gmdate($config["date_format"], $rawtransaction["time"]) . " UTC. And this transaction belongs to the block hash " . $rawtransaction["blockhash"] . ".";

        // echo json_encode($output);
        // exit;

        exit(get_html("transaction-body", $output));
        break;
    case "search":
        $search = $url_param_get_value;

        if (preg_match('/^[0-9]{1,7}$/i', $search)) {
            $output["search_result"] = 'Search Block with Height<br><a class="text-info" href="' . $url_path["block"] . $search . '">' . $search . '</a>';
        } else if (preg_match('/^[0-9a-f]{64}$/i', $search)) {
            $output["search_result"] = 'Search Block with Hash<br><a class="text-info" href="' . $url_path["blockhash"] . $search . '">' . $search . '</a>';
            $output["search_result"] .= '<br><br>';
            $output["search_result"] .= 'Search txid<br><a class="text-info" href="' . $url_path["tx"] . $search . '">' . $search . '</a>';
        } else if (preg_match('/^(R)\w{33,33}$/i', $search)) {
            $output["search_result"] = 'Search Block with Address<br><a class="text-info" href="' . $url_path["addr"] . $search . '">' . $search . '</a>';
        } else {
            $output["search_result"] = 'Search for some valid data';
        }
        $output["title"] = "Search result for " . $search . " - ";
        $output["description"] = "Search result for " . $search;

        exit(get_html("search-body", $output));
        break;
    default:
        send404();
        break;
}

function get_output_from_block($block)
{
    global $config, $url_path, $bitcoinrpc;
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\" style=\"width:30%\">Height</th><td class=\"text-start\">" . $block["height"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Hash</th><td class=\"text-start\">" . $block["hash"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Time</th><td class=\"text-start\">" . gmdate($config["date_format"], $block["time"]) . " UTC</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Version</th><td class=\"text-start\">" . $block["version"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Version Hex</th><td class=\"text-start\">" . $block["versionHex"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Weight</th><td class=\"text-start\">" . short_number($block["weight"], 1024, 3, " ") . "B" . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Size</th><td class=\"text-start\">" . short_number($block["size"], 1024, 3, " ") . "B" . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Confirmations</th><td class=\"text-start\">" . $block["confirmations"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Difficulty</th><td class=\"text-start\">" . $block["difficulty"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Bits</th><td class=\"text-start\">" . $block["bits"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Nonce</th><td class=\"text-start\">" . $block["nonce"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Chainwork</th><td class=\"text-start\">" . $block["chainwork"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Merkleroot</th><td class=\"text-start\">" . $block["merkleroot"] . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Previous block</th><td class=\"text-start\">" . ($block["previousblockhash"] ? "<a class=\"text-info\" href=\"" . $url_path["blockhash"] . $block["previousblockhash"] . "\">" . $block["previousblockhash"] . "</a>" : "") . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Next block</th><td class=\"text-start\">" . ($block["nextblockhash"] ? "<a class=\"text-info\" href=\"" . $url_path["blockhash"] . $block["nextblockhash"] . "\">" . $block["nextblockhash"] . "</a>" : "") . "</td></tr>";
    $output['block_detail_tbody'] .= "<tr><th class=\"text-end\">Transactions</th><td class=\"text-start\">" . count($block["tx"]) . "</td></tr>";

    if (count($block["tx"]) > 0) {
        foreach ($block["tx"] as $tx) {
            $transaction_detail = array();
            $transaction_detail['tx'] = $tx;
            $rawtransaction = $bitcoinrpc->getrawtransaction($tx, 1);
            if ($rawtransaction === false) {
                continue;
            }
            $transaction_detail['time'] = $rawtransaction["time"];
            if (isset($rawtransaction['vin'][0]['coinbase'])) {
                $transaction_detail['coinbase'] = $rawtransaction['vin'][0]['coinbase'];
            } else {
                $transaction_detail['coinbase'] = "";
                $transaction_detail['vin_count'] = count($rawtransaction['vin']);
            }

             if (count($rawtransaction['vin']) > 0) {
                foreach ($rawtransaction['vin'] as $key => $vin) {
                    if (isset($vin['coinbase'])) {
                        $output['tx_list_tbody'] .= 'coinbase:<br>' . $vin["coinbase"];
                    } else {
                        $output['tx_list_tbody'] .= 'txid: <a class="text-info" href="' . $url_path["tx"] . $vin["txid"] . '">' . $vin["txid"] . '</a><br>';
                        $output['tx_list_tbody'] .= '<a class="text-info" href="' . $url_path["addr"] . $vin["address"] . '">' . $vin["address"] . '</a><br>';
                        $output['tx_list_tbody'] .= trim_dotzero($vin['value']) . ' ' . $config["symbol"] . '<br><br>';
                        $transaction_detail['vin'][$vin['txid'] . $vin['vout']]['address'] = $vin["address"];
                        $transaction_detail['vin'][$vin['txid'] . $vin['vout']]['valueSat'] = $vin['valueSat'];
                    }
                }
            }

            foreach ($rawtransaction['vout'] as $vout) {        
                $transaction_detail['vout'][$vout['n']]['addresses'] = $vout['scriptPubKey']['addresses'];
                $transaction_detail['vout'][$vout['n']]['value'] = trim_dotzero($vout['value']);
                $transaction_detail['vout'][$vout['n']]['type'] = $vout['scriptPubKey']['type'];
                $transaction_detail['vout'][$vout['n']]['assetName'] = $vout['scriptPubKey']['asset']['name'];
                $transaction_detail['vout'][$vout['n']]['assetAmount'] = $vout['scriptPubKey']['asset']['amount'];  
            }

            $output['transactions'][] = $transaction_detail;
        }
    }

    // echo json_encode($output);
    // exit;
    if (count((is_countable($output['transactions'])?$output['transactions']:[])) > 0) {
        foreach ($output['transactions'] as $value) {
            $output['block_detail_tbody'] .= '<tr><th class="text-end">tx</th><td class="text-start">';
            $output['block_detail_tbody'] .= '<a class="text-info" href="' . $url_path["tx"] . $value["tx"] . '">' . $value["tx"] . '</a>';
            $output['block_detail_tbody'] .= '</td></tr>';
            $output['block_detail_tbody'] .= '<tr><th class="text-end"></th><td class="text-start">';
            $output['block_detail_tbody'] .= '<table class="table table-borderless text-white-50 table-sm w-75"><tbody>';

            $vinTotal = 0;
            $voutTotal = 0;

            if (count((is_countable($value["vin"])?$value["vin"]:[])) > 0) {
                $output['block_detail_tbody'] .= '<tr><td class="text-start" colspan="2"><b>Input</b></td>';
                foreach ($value["vin"] as $vin) {
                    $output['block_detail_tbody'] .= '<tr><td class="text-start">' . convertToBTCFromSatoshi($vin["valueSat"]) . ' ' . $config["symbol"] . '</td>';
                    $output['block_detail_tbody'] .= '<td><a class="text-info" href="' . $url_path["addr"] . $vin["address"] . '">' . $vin["address"] . '</a><br>';
                    $output['block_detail_tbody'] .= '</td></tr>';

                    $vinTotal = $vinTotal + $vin["valueSat"];
                }
            }

            if ($value["coinbase"]) {
                $reward = " <span class=\"text-muted\">*</span>";
            } else {
                $reward = "";
            }
            
            $output['block_detail_tbody'] .= '<tr><td class="text-start" colspan="2"><b>Output</b></td>';

            foreach ($value["vout"] as $vout) {
                if ($vout["value"] > 0) {
                    $output['block_detail_tbody'] .= '<tr><td class="text-start">' . $reward . $vout["value"] . ' ' . $config["symbol"] . '</td><td class="text-start">';
                }
                else {
                    $output['block_detail_tbody'] .= '<tr><td class="text-start">' . $vout["assetAmount"] . ' ' . $vout["assetName"] . '</td><td class="text-start">';
                }
                
                if (count((is_countable($vout["addresses"])?$vout["addresses"]:[])) > 0) {
                    foreach ($vout["addresses"] as $address) {
                        $output['block_detail_tbody'] .= '<a class="text-info" href="' . $url_path["addr"] . $address . '">' . $address . '</a><br>';
                    }
                }

                $output['block_detail_tbody'] .= '</td></tr>';

                $voutTotal = $voutTotal + $vout["value"];
            }

            if (!$value["coinbase"]) {
                $output['block_detail_tbody'] .= '<tr><td class="text-start" colspan="2"><b>Total Fee:</b> ' . trim_dotzero(convertToBTCFromSatoshi($vinTotal) - $voutTotal) . ' ' . $config["symbol"] . '</td></tr>';
            }
            $output['block_detail_tbody'] .= '</tbody></table>';
        }
    }

    $output["height"] = $block["height"];
    $output["title"] = $block["height"] . " Block Detail - ";
    $output["description"] = "This block's height is " . $block["height"] . ", and the block hash is " . $block["hash"] . ". It was mined at " . gmdate($config["date_format"], $block["time"]) . " UTC.";

    return $output;
}

function get_output_from_getpeerinfo($nodeInfo)
{
    global $config, $url_path, $bitcoinrpc;
    
    $totalNodeCount = 0;

    foreach ($nodeInfo as $vout) {
        $ip = $vout["addr"];
        if ( strpos( $ip, ':') !== false ) {
            // ipv6
            if( count( explode(':', $ip ) ) > 2 && strpos( $ip, '[') !== false ) 
                $ip = parse_url('http://'.$ip, PHP_URL_HOST);
            elseif( count( explode(':', $ip ) ) === 2 ) 
                $ip = strstr( $ip, ':', true );
        }

        $connTime = gmdate("Y-m-d H:i:s", $vout["conntime"]);
        $connTimeDate = new DateTime($connTime, new DateTimeZone('UTC'));
        $currTime = new DateTime('now', new DateTimeZone('UTC'));
        $connDuration =  $currTime->diff($connTimeDate);
        $connDurationText = "";
        if ($connDuration->y) { $connDurationText .= $connDuration->format("%y years "); }
        if ($connDuration->m) { $connDurationText .= $connDuration->format("%m months "); }
        if ($connDuration->d) { $connDurationText .= $connDuration->format("%d days "); }
        if ($connDuration->h) { $connDurationText .= $connDuration->format("%h hours "); }
        if ($connDuration->i) { $connDurationText .= $connDuration->format("%i minutes "); }
        if ($connDuration->s) { $connDurationText .= $connDuration->format("%s seconds "); }

        $totalNodeCount = $totalNodeCount + 1;

        $output["node_list_tbody"] .= "<tr><td class=\"text-start\">" . $ip . "</td><td class=\"text-start\">" . $vout["version"] . "</td><td class=\"text-start\">" . $vout["subver"] . "</td><td class=\"text-start\">" . $connTime . "</td><td class=\"text-start\">" . $connDurationText . "</td></tr>";
    }

    
    $output["title"] = "Node List - ";
    $output["description"] = "This is the connected node where Ravencoin Network being link around the world.";
    $output["node_list_totalCount"] = $totalNodeCount;

    return $output;
}

//function get_output_from_addressbalance($addr, $addrInfo, $addrTx)
function get_output_from_addressbalance($addr, $addrInfo)
{
    global $config, $url_path, $bitcoinrpc;
    
    $foundRvn = false;
    $foundAsset = false;

    if (count((is_countable($addrInfo)?$addrInfo:[])) > 0) {
         foreach ($addrInfo as $vout) {
            $assetName = $vout["assetName"];
            $balance = convertToBTCFromSatoshi($vout["balance"]);
            $received = convertToBTCFromSatoshi($vout["received"]);

            if ($assetName == 'RVN') {
                $foundRvn = true;
                $output["balance_summary_body"] .= "<tr><td class=\"text-start\">" . $received . "</td><td class=\"text-start\">" . ($balance - $received) . "</td><td class=\"text-start\">" . $balance . "</td></tr>";
            }
            else {
                if ($balance != 0) {
                    $foundAsset = true;
                    $output["balance_asset_summary_body"] .= "<tr><td class=\"text-start\"><a class=\"text-info\" href=\"" . $url_path["asset"] . urlencode($assetName) . "\">" . $assetName . "</a></td><td class=\"text-start\">" . trim_dotzero($balance) . "</td></tr>";
                }
            }
       }
    }

    if ($foundRvn == false) {
        $output["balance_summary_body"] = '<tr><td class="text-start" colspan="4">No balance found.</td></tr>';
    }

    if ($foundAsset == false) {
        $output["balance_asset_summary_body"] = '<tr><td class="text-start" colspan="2">No balance found.</td></tr>';
    }

    //if (count((is_countable($addrTx)?$addrTx:[])) > 0) {
    //     foreach ($addrTx as $tx) {
    //        $output['address_transactions'] .= '<tr><td class="text-start">';
    //        $output['address_transactions'] .= '<a class="text-info" href="' . $url_path["tx"] . $tx . '">' . $tx . '</a>';
    //        $output['address_transactions'] .= '</td>';
    //   }
    //}

    $output["title"] = $addr . " - ";
    $output["description"] = "RVN Address " . $addr . " - coins, assets, balance, transaction";
    $output["address"] = $addr;

    return $output;
}


function get_output_from_getassetdata($assetName, $assetData)
{
    //global $config, $url_path, $bitcoinrpc;
    //echo json_encode($assetData);
    //exit;
    global $config;

    $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\" style=\"width:30%\">Amount</th><td class=\"text-start\">" . $assetData["amount"] . "</td></tr>";
    $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\">Units</th><td class=\"text-start\">" . $assetData["units"] . "</td></tr>";
    $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\">Reissueable</th><td class=\"text-start\">" . convertToYesNo($assetData["reissuable"]) . "</td></tr>";
    $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\">Has IPFS?</th><td class=\"text-start\">" . convertToYesNo($assetData["has_ipfs"]) . "</td></tr>";

    if ($assetData["has_ipfs"]) {
        $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\">IPFS Link</th><td class=\"text-start\"><a class='text-info' href='". $config["ipfsUrl"] . $assetData["ipfs_hash"] . "' target='_blank'>" . $assetData["ipfs_hash"] . "</a></td></tr>";
        $output['asset_detail_tbody'] .= "<tr><th class=\"text-end\">IPFS Preview</th><td class=\"text-start\"><iframe id='ipfsIFrame' width='100%' height='100%' frameborder='0' src='" . $config["ipfsUrl"] . $assetData["ipfs_hash"] . "'></iframe></td></tr>";
    }

    $output["title"] = $assetName . " - ";
    $output["description"] = "RVN Asset " . $assetName . " - Asset Details";
    $output["assetName"] = $assetName;

    return $output;
}

function send404()
{
    global $config;
    // header('HTTP/1.1 404 Not Found');
    // header("status: 404 Not Found");
    http_response_code(404);
    $output["title"] = "Oops! 404 Not Found - ";
    $output["description"] = "Oops! 404 Not Found";

    exit(get_html("404", $output));
}

function html_replace_common($html)
{
    global $config, $version, $url_path;
    $common["name"] = $config["name"];
    $common["currency"] = $config["name"];
    $common["symbol"] = $config["symbol"];
    $common["explorer_name"] = $config["explorer_name"];
    $common["explorer_path"] = $config["explorer_path"];
    $common["theme_path"] = $config["root_path"] . $config["explorer_path"] . "themes/" . $config["theme"] . "/";
    $common["homepage"] = $config["homepage"];
    $common["root_path"] = $config["root_path"];
    $common["copy_name"] = $config["copy_name"];
    $common["start_year"] = $config["start_year"];
    $common["refresh_interval"] = $config["refresh_interval"];
    $common["year"] = date("Y", time());
    $common["version"] = $version;
    $common["currentDateTime"] = gmdate("Y-m-d H:i:s");
    $common["search_url"] = $url_path["search"];

    return html_replace($html, $common);
}

function html_replace($html, $output)
{
    $keys = array();
    foreach ($output as $key => $value) {
        $keys[] = '{$' . $key . '}';
    }
    return str_replace(
        $keys,
        array_values($output),
        $html);
}

function get_html($filename, $output)
{
    global $config;
    $header = loadfile("themes/" . $config["theme"] . "/tpl/header.html");
    $body = loadfile("themes/" . $config["theme"] . "/tpl/" . $filename . ".html");
    $footer = loadfile("themes/" . $config["theme"] . "/tpl/footer.html");
    $html = $header . $body . $footer;
    $html = html_replace_common($html);
    $html = html_replace($html, $output);
    return clean_html($html);
}
exit;
