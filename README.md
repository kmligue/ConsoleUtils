# ConsoleUtils

# Installation
```
composer require "practice/console-utils:dev-master"
php bin/magento setup:upgrade
php bin/magento cache:flush
php bin/magento indexer:reindex
```

# Uninstall
```
composer remove "practice/console-utils"
php bin/magento setup:upgrade
php bin/magento cache:flush
php bin/magento indexer:reindex
```

# Command
```
bin/magento practice:product:inventory-loader <path to csv>
```
