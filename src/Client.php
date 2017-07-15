<?php
namespace Pepijnolivier\Poloniex;

use Illuminate\Support\Facades\Log;

class Client implements ClientContract
{
    /**
     * @var string
     */
    public $tradingUrl;

    /**
     * @var string
     */
    public $publicUrl;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    public static $nonceIteration = 0;

    /**
     * Client constructor.
     *
     * @param array $auth
     * @param array $urls
     */
    public function __construct(array $auth, array $urls)
    {
        $this->tradingUrl = array_get($urls, 'trading');
        $this->publicUrl = array_get($urls, 'public');

        $this->key = array_get($auth, 'key');
        $this->secret = array_get($auth, 'secret');
    }

    /**
     * Get my balances.
     *
     * @return float
     */
    public function getBalanceFor($currency)
    {
        return array_get(
            $this->getBalances(), strtoupper($currency)
        );
    }

    /**
     * Get my open orders.
     *
     * @param string $pair
     * @return array
     */
    public function getOpenOrders($pair)
    {
        return $this->trading([
            'command' => 'returnOpenOrders',
            'currencyPair' => strtoupper($pair)
        ]);
    }

    /**
     * Get my trade history.
     *
     * @param string $pair
     * @return mixed
     */
    public function getMyTradeHistory($pair, $start = null, $end = null)
    {
        return $this->trading(array_merge([
            'command' => 'returnTradeHistory',
            'currencyPair' => strtoupper($pair)
        ], $this->formatDates($start, $end)));
    }

    /**
     * Returns all trades involving a given order, specified by the "orderNumber" parameter.
     * If no trades for the order have occurred or you specify an order that does not belong to you, you will receive an error.
     *
     * @param $orderNumber
     * @return mixed
     */
    public function getOrderTrades($orderNumber) {
        return $this->trading([
            'command' => 'returnOrderTrades',
            'orderNumber' => $orderNumber
        ]);
    }


    public function getAvailableAccountBalances($account=null)
    {
        return $this->trading(array_merge([
            'command' => 'returnAvailableAccountBalances',
        ], [
            'account' => $account,
        ]));
    }

    /**
     * Returns your current tradable balances for each currency in each market for which margin trading is enabled.
     * Please note that these balances may vary continually with market conditions.
     *
     * @return mixed
     */
    public function getTradableBalances() {
        return $this->trading([
            'command' => 'returnTradableBalances',
        ]);
    }




    /**
     * Transfers funds from one account to another (e.g. from your exchange account to your margin account).
     * Required parameters are "currency", "amount", "fromAccount", and "toAccount".
     *
     * @param $currency
     * @param $amount
     * @param $fromAccount
     * @param $toAccount
     * @return mixed
     */
    public function transferBalance($currency, $amount, $fromAccount, $toAccount) {
        return $this->trading([
            'command' => 'transferBalance',
            'currency' => $currency,
            'amount' => $amount,
            'fromAccount' => $fromAccount,
            'toAccount' => $toAccount,
        ]);
    }


    /**
     * Returns a summary of your entire margin account.
     * This is the same information you will find in the Margin Account section of the Margin Trading page, under the Markets list.
     *
     * @return mixed
     */
    public function getMarginAccountSummary() {
        return $this->trading([
            'command' => 'returnMarginAccountSummary',
        ]);
    }

    /**
     * Places a margin buy order in a given market. Required parameters are "currencyPair", "rate", and "amount".
     * You may optionally specify a maximum lending rate using the "lendingRate" parameter.
     * If successful, the method will return the order number and any trades immediately resulting from your order.
     *
     * @param $pair
     * @param $rate
     * @param $amount
     * @param null $lendingRate
     *
     * @return mixed
     */
    public function marginBuy($pair, $rate, $amount, $lendingRate=null)
    {
        $parameters = [
            'command' => 'marginBuy',
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount,
            'lendingRate' => $lendingRate
        ];

        $filteredParameters = array_filter($parameters); // remove null values, like lendingRate
        return $this->trading($filteredParameters);
    }

    /**
     * Places a margin sell order in a given market. Required parameters are "currencyPair", "rate", and "amount".
     * You may optionally specify a maximum lending rate using the "lendingRate" parameter.
     * If successful, the method will return the order number and any trades immediately resulting from your order.
     *
     * @param $pair
     * @param $rate
     * @param $amount
     * @param null $lendingRate
     * @return mixed
     */
    public function marginSell($pair, $rate, $amount, $lendingRate=null) {
        $parameters = [
            'command' => 'marginSell',
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount,
            'lendingRate' => $lendingRate
        ];

        $filteredParameters = array_filter($parameters); // remove null values, like lendingRate
        return $this->trading($filteredParameters);
    }


    /**
     * Returns information about your margin position in a given market, specified by the "currencyPair" parameter.
     * You may set "currencyPair" to "all" if you wish to fetch all of your margin positions at once.
     * If you have no margin position in the specified market, "type" will be set to "none".
     * "liquidationPrice" is an estimate, and does not necessarily represent the price at which an actual forced liquidation will occur.
     * If you have no liquidation price, the value will be -1.
     *
     * @param string $pair
     * @return mixed
     */
    public function getMarginPosition($pair='all') {
        return $this->trading([
            'command' => 'getMarginPosition',
            'currencyPair' => $pair,
        ]);
    }

    /**
     * Closes your margin position in a given market (specified by the "currencyPair" POST parameter) using a market order.
     * This call will also return success if you do not have an open position in the specified market.
     * @param $pair
     * @return mixed
     */
    public function closeMarginPosition($pair) {
        return $this->trading([
            'command' => 'closeMarginPosition',
            'currencyPair' => $pair,
        ]);
    }

    /**
     * Creates a loan offer for a given currency.
     *
     * @param $currency
     * @param $amount
     * @param $duration
     * @param $lendingRate
     * @param bool $autoRenew
     */
    public function createLoanOffer($currency, $amount, $duration, $lendingRate, $autoRenew=false) {
        return $this->trading([
            'command' => 'createLoanOffer',
            'currency' => $currency,
            'amount' => $amount,
            'duration' => $duration,
            'lendingRate' => $lendingRate,
            'autoRenew' => (int) ((bool) $autoRenew),
        ]);
    }


    /**
     * Cancels a loan offer specified by the "orderNumber" parameter.
     *
     * @param $orderNumber
     * @return mixed
     */
    public function cancelLoanOffer($orderNumber) {
        return $this->trading([
            'command' => 'cancelLoanOffer',
            'orderNumber' => $orderNumber,
        ]);
    }

    /**
     * Returns your open loan offers for each currency.
     * @return mixed
     */
    public function getOpenLoanOffers() {
        return $this->trading([
            'command' => 'returnOpenLoanOffers',
        ]);
    }



    /**
     * Generates a new address
     *
     * @param string      $currency
     * @return array
     */
    public function generateNewAddress($currency)
    {
        return $this->trading([
            'command' => 'generateNewAddress',
            'currency' => $currency,
        ]);
    }

    /**
     * Buy pair at rate.
     *
     * @param string      $pair
     * @param float       $rate
     * @param float       $amount
     * @param string|null $type
     * @return array
     */
    public function buy($pair, $rate, $amount, $type = null)
    {
        return $this->buyOrSell('buy', $pair, $rate, $amount, $type);
    }

    /**
     * Sell pair at rate.
     *
     * @param string      $pair
     * @param float       $rate
     * @param float       $amount
     * @param string|null $type
     * @return array
     */
    public function sell($pair, $rate, $amount, $type = null)
    {
        return $this->buyOrSell('sell', $pair, $rate, $amount, $type);
    }

    /**
     * Cancel order on a pair by its id.
     *
     * @param string $pair
     * @param int    $id
     * @return mixed
     */
    public function cancelOrder($pair, $id)
    {
        return $this->trading([
            'command' => 'cancelOrder',
            'currencyPair' => strtoupper($pair),
            'orderNumber' => $id
        ]);
    }

    /**
     * Withdraw the currency amount to address.
     *
     * @param string $currency
     * @param string $amount
     * @param string $address
     * @return mixed
     */
    public function withdraw($currency, $amount, $address)
    {
        return $this->trading([
            'command' => 'withdraw',
            'currency' => strtoupper($currency),
            'amount' => $amount,
            'address' => $address
        ]);
    }


    /**
     * Returns information about currencies
     *
     * @return array|mixed
     */
    public function getCurrencies() {
        return $this->public([
            'command' => 'returnCurrencies',
        ]);
    }

    /**
     * Returns the list of loan offers and demands for a given currency, specified by "currency"
     * @param string $currency
     *
     * @return array|mixed
     */
    public function getLoanOrders($currency)
    {
        return $this->public([
            'command' => 'returnLoanOrders',
            'currency' => $currency,
        ]);
    }

    /**
     * Chart data for given currency pair.
     *
     * @param string      $pair
     * @param string|null $start
     * @param string|null $end
     * @param string|null $period
     * @return array
     */
    public function getChartData($pair, $start = null, $end = null, $period = null)
    {
        return $this->public(array_merge([
            'command' => 'returnChartData',
            'currencyPair' => strtoupper($pair),
            'period' => $period
        ], $this->formatDates($start, $end)));
    }

    public function getDepositsWithdrawals($start=null, $end=null) {
        return $this->trading(array_merge([
            'command' => 'returnDepositsWithdrawals',
        ], $this->formatDates($start, $end)));
    }

    /**
     * Trade history for given currency pair.
     *
     * @param string      $pair
     * @param string|null $start
     * @param string|null $end
     * @param string|null $period
     * @return array
     */
    public function getTradeHistory($pair, $start = null, $end = null, $period = null)
    {
        return $this->public(array_merge([
            'command' => 'returnTradeHistory',
            'currencyPair' => strtoupper($pair),
            'period' => $period
        ], $this->formatDates($start, $end)));
    }

    /**
     * Order book for given currency pair.
     *
     * @param string $pair
     * @param int    $depth
     * @return array
     */
    public function getOrderBook($pair, $depth = 10)
    {
        return $this->public([
            'command' => 'returnOrderBook',
            'currencyPair' => strtoupper($pair),
            'depth' => $depth
        ]);
    }

    /**
     * Returns the trading volume.
     *
     * @param string $pair
     * @return array|null
     */
    public function getVolumeFor($pair)
    {
        $pair = strtoupper($pair);

        return array_get($this->getVolume(), $pair);
    }

    /**
     * Get trading pairs.
     *
     * @return array
     */
    public function getTradingPairs()
    {
        return array_keys($this->public([
            'command' => 'returnTicker'
        ]));
    }

    /**
     * Get trading pairs.
     *
     * @param string $pair
     * @return array|null
     */
    public function getTicker($pair)
    {
        $pair = strtoupper($pair);

        return array_get($this->getTickers(), $pair);
    }

    /**
     * @inheritdoc
     */
    public function getBalances()
    {
        return array_map(function ($balance) {
            return (float) $balance;
        }, $this->trading([
            'command' => 'returnBalances'
        ]));
    }

    /**
     * Returns all of your balances, including available balance, balance on orders, and the estimated BTC value of your balance.
     *
     * By default, this call is limited to your exchange account;
     * set the "account" POST parameter to "all" to include your margin and lending accounts.
     *
     * @inheritdoc
     */
    public function getCompleteBalances($account='all')
    {
        return $this->trading([
            'command' => 'returnCompleteBalances',
            'account' => $account,
        ]);
    }

    public function getDeposit

    /**
     * @inheritdoc
     */
    public function getVolume()
    {
        return $this->public([
            'command' => 'return24hVolume'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTickers()
    {
        return $this->public([
            'command' => 'returnTicker'
        ]);
    }

    public function getActiveLoans() {
        return $this->trading([
            'command' => 'returnActiveLoans',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buyOrSell($command, $pair, $rate, $amount, $type = null)
    {
        $parameters = [
            'command' => $command,
            'currencyPair' => strtoupper($pair),
            'rate' => $rate,
            'amount' => $amount
        ];

        if ($type == 'fillOrKill') {
            $parameters['fillOrKill'] = 1;
        }
        else if ($type == 'immediateOrCancel') {
            $parameters['immediateOrCancel'] = 1;
        }
        else if ($type == 'postOnly') {
            $parameters['postOnly'] = 1;
        }

        return $this->trading($parameters);
    }

    public function getValidChartDataTickIntervals() {

        $validTickIntervals = [
            '300' => '300', // 5 min
            '900' => '900', // 15 min
            '1800' => '1800', // 30 min
            '7200' => '7200', // 2 hours
            '14400' => '14400', // 4 hours
            '86400' => '86400', // 1 day
        ];

        return $validTickIntervals;
    }



    /**
     * @inheritdoc
     */
    public function formatDates($start = null, $end = null)
    {
        if (is_object($start) && property_exists($start, 'timestamp')) {
            $start = $start->timestamp;
        }
        else if (! is_numeric($start) && ! is_null($start)) {
            $start = strtotime($start);
        }

        if (is_object($end) && property_exists($end, 'timestamp')) {
            $end = $end->timestamp;
        }
        else if (! is_numeric($end) && ! is_null($end)) {
            $end = strtotime($end);
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * @inheritdoc
     */
    public function public(array $parameters)
    {
        $options = [
            'http' => [
                'method'  => 'GET',
                'timeout' => 10
            ]
        ];

        $url = $this->publicUrl . '?' . http_build_query(array_filter($parameters));

        $feed = file_get_contents(
            $url, false, stream_context_create($options)
        );

        $response = json_decode($feed, true);
        if(isset($response['error'])) {
            Log::error($response['error']);
        }
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function trading(array $parameters = [])
    {
        if(empty($this->key) || empty($this->secret)) {
            throw new \Exception("Cannot call Poloniex trading API, invalid key/secret in config");
        }


        $mt = (int) microtime(true) * 1000;
        $mt += self::$nonceIteration;

        self::$nonceIteration++;

        $parameters['nonce'] = $mt;

        $post = http_build_query(array_filter($parameters), '', '&');
        $sign = hash_hmac('sha512', $post, $this->secret);

        $headers = [
            'Key: '.$this->key,
            'Sign: '.$sign,
        ];

        static $ch = null;

        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT,
                'Mozilla/4.0 (compatible; Poloniex PHP-Laravel Client; '.php_uname('a').'; PHP/'.phpversion().')'
            );
        }

        curl_setopt($ch, CURLOPT_URL, $this->tradingUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('Curl error: '.curl_error($ch));
        }

        $response = json_decode($response, true);

        if(isset($response['error'])) {
            Log::error($response['error']);
        }

        return $response;
    }
}
