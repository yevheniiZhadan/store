# store
in root folder start with: `docker-compose up -d --build`
run ``composer install``
go to product_service folder and run `symfony server:start -d`

run migration command ``php bin/console doctrine:migrations:migrate``

do the same steps for order_service

go to order_service folder run ``composer install``
and run `symfony server:start -d`

run migration command ``php bin/console doctrine:migrations:migrate``

In Order Service run ``php bin/console rabbitmq:consumer order ``
and ``php bin/console rabbitmq:consumer order_complete ``

In Product Service run: ``php bin/console rabbitmq:consumer product ``

Open Postman or other app to send request to API

API LINK: http://127.0.0.1:8000/api/
port use from symfony server start


Application use sharedMessages repository: ``https://github.com/yevheniiZhadan/sharedMessages``
