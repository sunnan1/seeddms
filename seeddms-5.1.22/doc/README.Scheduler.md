Scheduler
==========

The scheduler in SeedDMS manages frequently run tasks. It is very similar
to regular unix cron jobs. A task in SeedDMS is an instanciation of a task
class which itself is defined by an extension or SeedDMS itself.
SeedDMS has some predefined classes e.g. core::expireddocs.

In order for tasks to be runnalbe, a user `cli_scheduler` must exists.

All tasks are executed by a single cronjob in the directory `utils`

> */5 * * * * /home/www-data/seeddms60x/seeddms/utils/seeddms-schedulercli --mode=run

Please keep in mind, that the php interpreter used for the cronjob is different
from the php interpreter used für the web application. Hence, two different
php.ini files might be used.php and the php extensions may differ as well.
This can cause some extensions to be disabled and consequently some task
classes are not defined.
