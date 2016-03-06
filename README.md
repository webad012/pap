# PAP - Pgsql Autodoc Perschema

php script that parses postgresql_autodoc output to display image for each requested schema with connections to other schemas

prerequisites: postgresql_autodoc

tested on: Ubuntu 14.04, php 5.5.9, postgresql 9.3

example usage: php pap.php -d database_name -a

 input: 
  -h host - default localhost
  -p port - default 5432
  -d dbname
  -u username - default postgres
  -s schema - or -a
  -a all schemas - or -s