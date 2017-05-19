# Fork

This is a fork of  of https://github.com/htunlogic/laravel-poloniex

- I am not versioning my fork (for now). 

  If you want to use it, you'll have to use `composer require pepijnolivier/laravel-poloniex:dev-master`
- I added a few extra methods to the Client, see commit history


(original readme below this line)


---



### Installation

Require this package in your project with `composer require pepijnolivier/laravel-poloniex`.

Add the service provider to your `config/app.php`:
 
 ``` 
 'providers' => [
 
     Pepijnolivier\Poloniex\PoloniexServiceProvider::class,
     
 ],
 ```
 
...run `php artisan vendor:publish` to copy the config file.

Edit the `config/poloniex.php` or add Poloniex api and secret in your `.env` file

```
POLONIEX_KEY={YOUR_API_KEY}
POLONIEX_SECRET={YOUR_API_SECRET}

```

Optionally you can add alias to your `config/app.php`:

```    
'aliases' => [
           
    'Poloniex' => Pepijnolivier\Poloniex\Poloniex::class,
           
],
```

Usage examples: 
``` 
use Pepijnolivier\Poloniex\Poloniex;
```
``` 
Poloniex::getBalanceFor('BTC');
Poloniex::getOpenOrders('BTC_XMR');
Poloniex::getMyTradeHistory('BTC_XMR');
Poloniex::buy('BTC_XMR', 0.013, 1, 'postOnly');
Poloniex::sell('BTC_XMR', 0.013, 1, 'postOnly');
Poloniex::cancelOrder('BTC_XMR', 123);
Poloniex::withdraw('BTC', 1, '14PJdqimDkCqWCH1oXy4sVV6nwweqXYDjt');
Poloniex::getTradeHistory('BTC_XMR');
Poloniex::getOrderBook('BTC_XMR');
Poloniex::getVolumeFor('BTC_XMR');
Poloniex::getTradingPairs();
Poloniex::getTicker("BTC_XMR");
Poloniex::getBalances();
Poloniex::getVolume();
Poloniex::getTickers();
```
