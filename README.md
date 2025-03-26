## USAGE

* `make init` - Required for the first run. Init project, run install and migrations.
* `make start` - docker compose up -d
* `make stop` - stop containers
* `make exec` - run shell in container
* `make console` - run bin/console

# TEST

To request 4-digit code:
```
curl -X POST http://localhost:8080/api/auth/send-code \
-H "Content-Type: application/json" \
-d '{"username":"John Doe","phone_number":"89139123445"}'
```
Rsponse:
```
{"message":"Verification code sent: 7815","attempts_left":2,"expire_in":900}
```
To verify code:
```
curl -X POST http://localhost:8080/api/auth/verify-code \
-H "Content-Type: application/json" \
-d '{"code":"7815","phone_number":"89139123445"}'
```
Response:
```
{"message":"Authorization successful","user_id":1,"is_new_user":false}
```
