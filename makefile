up:
	docker compose up -d

down:
	docker compose down

producer:
	docker compose exec app php send.php

receive:
	docker compose exec app php receive.php

sniffer:
	docker compose exec app php consumer-sniffer.php

logs:
	docker compose logs -f

restart:
	docker compose down && docker compose up -d --build
