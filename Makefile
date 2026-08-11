.PHONY: start stop restart refresh

start:
	docker compose up -d

stop:
	docker compose down

restart:
	docker compose restart

refresh:
	docker compose down
	rm -f data/app.sqlite
	docker compose up -d
