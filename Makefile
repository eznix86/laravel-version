lint:
	./vendor/bin/pest
	./vendor/bin/rector process
	 herd coverage ./vendor/bin/pest --coverage
	./vendor/bin/phpstan analyse --memory-limit=512M
	./vendor/bin/pest --type-coverage
