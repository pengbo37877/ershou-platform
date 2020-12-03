<?php
namespace Deployer;

require 'recipe/laravel.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', 'git@domain.com:username/repository.git');

set('ssh_multiplexing', false);
// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);


// Hosts
// xsc
//host('root@119.23.241.48')
//    ->set('deploy_path', '/var/www');
// 006
host('root@120.25.207.77')
    ->set('deploy_path', '/var/www');
// 007
host('root@120.79.191.149')
    ->set('deploy_path', '/var/www');
// 008
host('root@47.106.185.192')
    ->set('deploy_path', '/var/www');

// Tasks

task('update', function () {
    run('cd /var/www/ershou-platform && git pull && chown -R nginx:nginx . && chmod -R 777 storage && echo "" > storage/logs/laravel.log && rm -f public/storage/*.jpg');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');

