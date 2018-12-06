# DEPLOYMENT SET UP GUIDELINE
1) Add developer's public keys to the authorized keys of the deploy user on the server.
2) Set up GITLAB repository.
3) Add `SERVER_PRIVATE_KEY` to the GITLAB CI variables with the server private_key value.
4) [Register](https://docs.gitlab.com/runner/register/) GITLAB RUNNER for the repository.
5) Create folder on the server according to the pattern: /home/deploy/`<project_dir>`/`<project_dir>`-`<branch_name>` 
    * `<project_dir>` - the configuration in `GoConfig.php`
    * `<branch_name>` - git branch name (`dev`, `stage`, `prod`)
6) Take the file `deploy/docker-compose.yml` as a pattern and copy it to each created folder on the server.
    * According to the folder/environment, change the `traefik.frontend.rule` for apache container.
    * According to the folder/environment, change the `GIT_USER_NAME` for php container.
7) Run docker `make go_up` in the created folders.
8) Locally run `gor pdb dev` to export your local DB to DEV environment.
10) Locally run `gor pf dev` to export your local `files` folder to DEV environment.
