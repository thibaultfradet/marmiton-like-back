<?php

require dirname(__DIR__) . '/vendor/autoload.php';

(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

if (($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '') !== ($_ENV['DEPLOY_HOOK_TOKEN'] ?? '')) {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);
$logFile = $root . '/var/log/deploy.log';

$script = implode(' && ', [
    "cd {$root}",
    "composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts",
    "php bin/console cache:clear --env=prod --no-debug",
    "php bin/console doctrine:migrations:migrate --no-interaction --env=prod",
]);

exec("({$script}) >> {$logFile} 2>&1 &");

http_response_code(200);
echo 'Deploy started';
