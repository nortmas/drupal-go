# DEPLOYMENT SET UP FLOW
1) Add developer's public keys to the authorized keys of the deploy user on the server.
2) Add `SERVER_PRIVATE_KEY` to the GITLAB CI variables with the server private_key value.
3) [Register](https://docs.gitlab.com/runner/register/) GITLAB RUNNER for the repository.
4) Create folder on the server according to the pattern: /home/deploy/`project_dir`/`project_dir`-`branch_name` 
    * `project_dir` - the configuration in `GoConfig.php`
    * `branch_name` - git branch name (`dev`, `stage`, `prod`)
5) Take the file `deploy/docker-compose.yml` as a pattern and copy it to each created folder on the server.
    * According to the folder/environment, change the `traefik.frontend.rule` for apache container.
    * According to the folder/environment, change the `GIT_USER_NAME` for php container.
6) Run docker `compose up -d` in the created folders.
7) Create the DB dump locally using the command: `gor dbe`.
8) Run the `scp db/<file-name>.zip deploy@178.128.83.195:/home/deploy/<project-name>/<project-name>-<branch>/db`.
9) Run the `ssh -ttq deploy@178.128.83.195 "export TERM='xterm' && cd /home/deploy/<project-name>/<project-name>-<branch> && gor dbi"`.