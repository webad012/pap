# PAP - Pgsql Autodoc Perschema

php script that parses postgresql_autodoc output to display image for each requested schema with connections to other schemas

prerequisites: postgresql_autodoc, mupdf-tools

tested on: Ubuntu 14.04, php 5.5.9, postgresql 9.3, postgresql_autodoc 1.40, graphviz 2.36, mupdf-tools 1.3-2

example usage: 
```
php pap.php -d database_name -a
```

input: 
-  -h host - default localhost
-  -p port - default 5432
-  -u username - default postgres
-  -f format - default svg
-  -d dbname
-  -s schema - or -a
-  -a all schemas - or -s

supported formats (depending on graphviz dot):

canon cmap cmapx cmapx_np dot eps fig gd gd2 gif gv imap imap_np ismap jpe jpeg jpg pdf pic plain plain-ext png pov ps ps2 svg svgz tk vml vmlz vrml wbmp x11 xdot xdot1.2 xdot1.4 xlib