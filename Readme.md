## Data collection and analysis system

The system parses JSON data from the bookmaker [1xstavka](https://1xstavka.ru/), then it's analyzes ones and calculate a value bet for soccer. This project contains only domain models with logic (app/domain) and DB models (app/models). HTTP requests for receiving JSON data, main cycles and other additional methods are missing. Algorithms of the deviation are used.

[Yii 2](http://www.yiiframework.com/) Basic Project Template is a skeleton this application.