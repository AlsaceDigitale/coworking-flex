# Strasbourg_0918_PHP_Co_working_Flex
Strasbourg_0918_PHP_Co_working_Flex

## Start the project in dev

1. Build the containers

```
docker-compose build
```

2. Start the stack

```
docker-compose up
```

3. Install dependencies

```
docker-compose run web php composer.phar install
```

4. Load schema

```
docker-compose run web php bin/console doctrine:migrations:migrate
```

That's all. You can access your app at: [http://localhost:8100](http://localhost:8100).

The docker-compose will launch a fake SMTP server that you can access at: [http://localhost:1080](http://localhost:1080).
