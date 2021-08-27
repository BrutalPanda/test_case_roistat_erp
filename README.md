# Тестовое задание
Принцип работы
- Убедиться, что установлен PHP версии не ниже 7.1
- Запускать следующим образом:

```shell script
php parser.php example_access.log
```
- В случае возникновении ошибки вернётся JSON массив с `status = error` и в поле `message` будет содержаться текст ошибки:
```json
{"status":"error","message":"Error text"}
```
- После выполнения так же будет выведен JSON массив с `status = success` и в поле `message` будет выведен результат парсинга в JSON формате:
```json
{"status":"success","message":"{\"total\":16,\"parse_fails\":0,\"views\":14,\"traffic\":212816,\"urls\":5,\"status_codes\":{\"200\":14,\"301\":2},\"crawlers\":{\"Google\":2}}"}
```