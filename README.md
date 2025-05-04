It's just a playgroud to training my rabbitmq skills!
But if you want use, it's simple.

## Docker
use ```bash docker compose up -d `` to star containers
after start docker you need run composer install inside the docker ´´´ docker compose exec app composer install ´´

You have 2 directory to play

Direct and fanout
## Direct

We have example with resilience, thowing the message if not was not success after 3 trys to DLQ.
And many workers to act as a consumer too.

## Fanout

Wer have just 2 workers but is ok to see the basic usage of that type of exchanges
