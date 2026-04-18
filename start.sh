#!/bin/sh
PORT="${PORT:-80}"
echo "Starting PHP on port $PORT"
exec php -S 0.0.0.0:$PORT router.php
