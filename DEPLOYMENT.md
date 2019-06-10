# Deployment set up guideline
1) Locally check if the `GoConfig.php` server configurations are correct.
2) If it's not correct, set the configurations and run `robo reconf deploy && robo reconf drush && robo reconf docker_deploy` and push your changes to the repo.
3) Add developer's public keys to the authorized keys of the "deploy" user on the server.
4) Set up GITLAB repository.
5) Configure traefik as a standalone reverse proxy. [Example.](https://snippets.cacher.io/snippet/c09f20fce24972d4f57e)
6) [Register](https://docs.gitlab.com/runner/register/) GITLAB RUNNER for the repository.
7) [Install Docker](https://docs.docker.com/install/linux/docker-ce/ubuntu/) on the server.
8) [Install Docker Compose](https://docs.docker.com/compose/install/) on the server.
9) Generate id_rsa and id_rsa.pub without password on the server.
10) Add `public_key` to authorized_keys on the server where it was generated.
11) Add `<BRANCH_NAME>_SERVER_PRIVATE_KEY` to the GITLAB CI variables, as a value use the `private_key` taken from the server.
12) Prepare the folders and project files:
   * Create folder on the server according to the pattern: /home/`<user_name>`/`<project_machine_name>`.
   * Enter to the created folder.
   * Clone project repo `git clone <project_address> <project_machine_name>-<branch_name> && cd <project_machine_name>-<branch_name> && rm -r .git`
     * `<project_address>` - the address of the repo.
     * `<project_machine_name>` - the configuration in `GoConfig.php`
     * `<branch_name>` - git branch name (`dev`, `stage`, `prod`)
13) Take the file `deploy/docker-compose.yml` as a pattern and copy it to the project root.
    * According to the folder/environment, change the `traefik.frontend.rule` for apache container.
14) Run docker `make go_lin` in the project root folder.
    * Configure the `.env` file according to the environment. The variable `GO_ENV` can be: `local`, `dev`, `stage` or `prod`.
15) Run docker `make go_up` in the project root folder.
16) Get to the php container `make go_sh`.
    * Run `composer install` in the project root folder.
    * Run `robo scp` in the project root folder in order to apply correct permissions to folders and files.
17) Locally run `gor pdb dev` to export your local DB to DEV environment. Or place the dump to the folder `./db`, get to the container and run `robo dbi <dump-file-name>`.
18) Locally run `gor pf dev` to export your local `files` folder to DEV environment.
19) Perform the deployment.

# How to effectively interact with the environment from your local machine.
* Locally run `robo gdb dev` to import DEV DB to your local environment.
* Locally run `robo gf dev` to import DEV files` folder` to your local environment.
* Locally run `drush @<alias> ws` to see the logs on the DEV. [See all Drush commands.](https://drushcommands.com)
