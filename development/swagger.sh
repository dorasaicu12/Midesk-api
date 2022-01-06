#!/bin/bash

# mkdir ../public/swagger
php ../vendor/bin/swagger --bootstrap ./swagger-constants.php --output ../public/docs ./swagger-v1.php ../app/Http/Controllers
