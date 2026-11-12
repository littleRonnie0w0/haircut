#!/bin/sh
COOKIE_JAR=/tmp/cj.txt
rm -f "$COOKIE_JAR"
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" http://localhost/login -o /tmp/login.html
TOKEN=$(grep -o 'name="_token" value="[^"]*"' /tmp/login.html | head -1 | sed 's/.*value="//;s/"$//')
echo "TOKEN_LEN=${#TOKEN}"
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST http://localhost/login \
  --data-urlencode "_token=$TOKEN" \
  --data-urlencode "email=admin@admin.cl" \
  --data-urlencode "password=admin123" \
  -o /dev/null -w "LOGIN=%{http_code} %{redirect_url}\n"
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST http://localhost/logout \
  --data-urlencode "_token=INVALID" \
  -D /tmp/hdrs.txt -o /tmp/out.html -w "LOGOUT=%{http_code} %{redirect_url}\n"
echo "---- headers ----"
head -20 /tmp/hdrs.txt
echo "---- body snip ----"
grep -E "sesión expiró|PAGE EXPIRED|419|Entrar|La sesión" /tmp/out.html | head -5
