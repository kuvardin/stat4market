Выполнить в папке с проектом

```
docker compose up -d --build
docker exec -ti stat4market_php bash
composer install
phinx migrate
engine init
```

Документация к API будет доступна по адресу: https://localhost:6443/api/v1_doc

Генерация библиотек для API
1. На ЯП Dart (в файл dart/api.dart):
```shell
engine dart:generate-api-library
```

2. На ЯП JS (в файл js/api.js):
```shell
engine js:generate-api-library
```

