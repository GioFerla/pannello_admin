#!/bin/sh
set -e

# Trust the host (avoids manual "yes" confirmation)
# Se SSH_HOST non è risolvibile qui, assicurati che il DNS funzioni o usa l'IP
ssh-keyscan -H "$SSH_HOST" >> /root/.ssh/known_hosts

echo "Starting tunnel to $SSH_HOST..."

# -L 0.0.0.0:3307:localhost:3306 significia:
# Ascolta su TUTTE le interfacce di questo container (0.0.0.0) sulla porta 3307
# e inoltra al DB remoto (localhost:3306 relativo al server remoto)

exec sshpass -p "$SSH_PASS" autossh -M 0 -N \
    -o "ServerAliveInterval 30" \
    -o "ServerAliveCountMax 3" \
    -o "ExitOnForwardFailure yes" \
    -o "StrictHostKeyChecking no" \
    -R 0.0.0.0:${REMOTE_PORT}:localhost:80 \
    -L 0.0.0.0:3306:localhost:3306 \
    ${SSH_USER}@${SSH_HOST}
