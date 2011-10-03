<?php

require_once "util.php";

function show_header($page, $is_logged_in, $base = false)
{
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>World Bitcoin Exchange</title>
    <script type='text/javascript' src='js/util.js'></script>
<?php if ($page == 'trade') { ?>
    <script type='text/javascript' src='js/jquery-1.4.4.min.js'></script>
    <script type='text/javascript' src='js/exchanger.js'></script>
<?php 
        echo "    <script type='text/javascript'>\n";
        $fiat_currency = strtolower(CURRENCY);
        echo "        fiat_currency = '$fiat_currency';\n";
        echo "        fiat_currency_full = '" . CURRENCY_FULL . "';\n";
        if (isset($_GET['rate'])) {
            echo "        typed_price = true;\n";
        } else {
            $currencies = array('BTC', CURRENCY);
            $rates = array();
            $list = calc_exchange_rate('btc', $fiat_currency, BASE_CURRENCY::A);
            $rates[$fiat_currency] = $list[2];
            $list = calc_exchange_rate($fiat_currency, 'btc', BASE_CURRENCY::B);
            $rates['btc'] = $list[2];
            echo "        typed_price = false;\n";
            echo "        exchange_rates = ".json_encode($rates).";\n";
        }
        echo "    </script>\n";
    }
    if ($base) echo "    <base href=\"$base\" />\n"; ?>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="icon" type="image/png" href="favicon.png" />
</head>

<?php
if ($page == 'trade') {
    if (isset($_GET['in'])) {
        if (get('in') == 'BTC')
            $in = 'btc';
        else
            $in = $fiat_currency;
    } else if (isset($_SESSION['currency_in']) && $_SESSION['currency_in'] == 'BTC')
        $in = 'btc';
    else
        $in = $fiat_currency;

    if ($in == 'btc')
        echo "<body onload='set_currency_in(\"btc\"); set_currency_out(\"$fiat_currency\");'>\n";
    else
        echo "<body onload='set_currency_in(\"$fiat_currency\"); set_currency_out(\"btc\");'>\n";
} else
    echo "<body>\n"; ?>
    <img id='flower' src='images/flower.png' />
    <a href="."><img id='header' src='images/header.png' /></a>
    <img id='skyline' src='images/skyline.png' />
    <div id='main_pane'>
        <div id='links_bg'>
            <div id='content'>
                <div id='content_sideshadow'>
<?php
    show_content_header($is_logged_in);
}

define('SPACE', '&nbsp;&nbsp;&nbsp;&nbsp;');
function show_content_header_balances($uid)
{
    $balances = fetch_balances($uid);
    $fiat = internal_to_numstr($balances[CURRENCY], FIAT_PRECISION, false);
    $btc  = internal_to_numstr($balances['BTC'],     BTC_PRECISION, false);

    $c_balances = fetch_committed_balances($uid);
    $c_fiat = internal_to_numstr($c_balances[CURRENCY], FIAT_PRECISION);
    $c_btc  = internal_to_numstr($c_balances['BTC'],     BTC_PRECISION);

    echo "    <div class='content_header_box'>\n";
    echo "        ", SPACE, _("balances"), ":", SPACE, "$fiat ";
    if ($c_fiat > 0) echo "(+$c_fiat) ";
    echo CURRENCY, SPACE, "$btc ";
    if ($c_btc > 0) echo "(+$c_btc) ";
    echo "BTC\n";
    echo "    </div>\n";
}

$buy = $sell = false;

function show_content_header_ticker()
{
    global $buy, $sell;

    $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';
    list($high, $low, $avg, $vwap, $vol, $last, $buy, $sell) = get_ticker_data();
    if ($buy > $sell && $buy != 0 && $sell != 0)
        $style = " style='color:#af0;'";
    else
        $style = '';

    // include prices up to 0.001% worse than the best
    $include_very_close_prices = '0.99999';

    // ask for 0.001% less than we need to match the worst price we want
    // $request_less_for_match = '0.99999';
    $request_less_for_match    = '1';

    if ($buy) {
        list ($buy_have, $buy_want, $worst_price) = find_total_trades_available_at_rate(bcmul($buy, $include_very_close_prices, 8), CURRENCY);
        $buy_have = bcmul(bcmul($buy_want, $worst_price), $request_less_for_match);;
        $worst_price = clean_sql_numstr($worst_price);
        $buy_link = "<a $style href=\"?page=trade&in=BTC&have=$buy_want&want=$buy_have&rate=$worst_price\">$buy</a>";
    } else
        $buy_link = "none";

    if ($sell) {
        list ($sell_have, $sell_want, $worst_price) = find_total_trades_available_at_rate(bcdiv($sell, $include_very_close_prices, 8), 'BTC');
        $sell_have = bcmul(bcdiv($sell_want, $worst_price), $request_less_for_match);
        $worst_price = clean_sql_numstr($worst_price);
        $sell_link = "<a $style href=\"?page=trade&in=" . CURRENCY . "&have=$sell_want&want=$sell_have&rate=$worst_price\">$sell</a>";
    } else
        $sell_link = "none";

    echo "    <div class='content_header_box'>\n";
    echo "    ", SPACE, _("24 hour volume"), ": <a href=\"?page=view_trades\">$vol BTC</a></div>\n";
    echo "    <div class='content_header_box'>\n";
    echo "        ", SPACE;
    echo _("buy") . ": $buy_link${spaces}" . _("sell") . ": $sell_link";
    echo SPACE, _("last") . ": $last", SPACE, _("high") . ": $high", SPACE, _("low") . ": $low", SPACE, _("avg") . ": $vwap\n";
    echo "    </div>\n";
}

function show_content_header_time()
{
    $help_link = "<a target=\"_blank\" href=\"?page=help#ticker\">" . _("help") . "</a>";
    $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';

    echo "    <div class='content_header_box' style='float: right;'>\n";
    echo "        $help_link", SPACE;
    echo "        ", date('g:i:sa j-M', time()), SPACE, "\n";
    echo "    </div>\n";
}

function show_content_header_frozen()
{
    if (is_frozen()) {
        echo "    <div class='content_header_box'>\n";
        echo "        <span style='color: #fff;'>" . _("trading on the exchange is currently frozen; no orders will be matched") . "</span>\n";
        global $is_admin;
        if ($is_admin) echo "&nbsp;&nbsp;&nbsp;&nbsp;<a style='color: red;' href=\"?page=freeze\">" . _("unfreeze") . "</a>\n";
        echo "    </div>\n";
    }
}

function show_content_header($is_logged_in)
{
    echo "<div class='content_header'>\n";

    try {
        show_content_header_time();
        show_content_header_ticker();

        if ($is_logged_in)
            show_content_header_balances($is_logged_in);

        show_content_header_frozen();
    } catch (Error $a) {
        echo "</div>\n";
        throw $a;
    }

    echo "</div>\n";
}

 ?>
