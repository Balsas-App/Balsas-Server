#!/bin/sh

# Inicia o servidor PHP em background
php -S 0.0.0.0:8080 -t public &

# Aguarda alguns segundos para o servidor iniciar
sleep 2

# Inicia o ngrok apontando para a porta 8080
ngrok config add-authtoken 2zpE2WvnLcSsGdssKflSi5xyR0b_3LpGox5fngaYE5ouHsYGh
ngrok http --url=central-gopher-mildly.ngrok-free.app 8080
