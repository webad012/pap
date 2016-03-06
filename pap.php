<?php

function exit_usage()
{
    $message = "invalid arguments:\n"
        ."\t-h host - default localhost\n"
        ."\t-p port - default 5432\n"
        ."\t-u username - default postgres\n"
        ."\t-f format - default svg, supported formats: \n"
        ."\t\tcanon cmap cmapx cmapx_np dot eps fig gd gd2 gif gv imap imap_np ismap\n"
        ."\t\tjpe jpeg jpg pdf pic plain plain-ext png pov ps ps2 svg svgz tk\n"
        ."\t\tvml vmlz vrml wbmp x11 xdot xdot1.2 xdot1.4 xlib\n"
        ."\t-d dbname\n"
        ."\t-s schema - or -a\n"
        ."\t-a all schemas - or -s\n";
    exit($message);
}

function create_schema_svg($dbname, $schema, $output_format)
{
    echo "parsing schema '$schema'\n";
    
    $schema_line_starting_part = '"'.$schema.'.';

    $file_to_parse = $dbname.'.dot';
    $output_file = $dbname.'_'.$schema.'_parsed.dot';

    $handle_read = fopen($file_to_parse, "r") or die("Unable to open file to read!");

    $started_schemas = false;
    $finished_tables = false;
    $previous_line = null;
    
    $pre_tables_lines = [];

    $schema_table_macros_used = [];

    $this_schema_tables_to_print = [];
    $connectors_to_use = [];

    while (($line = fgets($handle_read)) !== false) 
    {
        if($started_schemas === false)
        {
            if(!empty($line) && $line[0]==='"')
            {
                $started_schemas = true;
            }
            else
            {
                $pre_tables_lines[] = $line;
            }
        }

        if($finished_tables === false)
        {
            if($started_schemas === true)
            {
                if(!empty($line) && substr($line, 0, strlen($schema_line_starting_part)) === $schema_line_starting_part)
                {
                    $this_schema_tables_to_print[] =  $line;
                }
                else if($previous_line === $line)
                {
                    $this_schema_tables_to_print[] =  $line;

                    if($finished_tables === false)
                    {
                        $finished_tables = true;
                    }
                }
            }
        }
        else
        {        
            $first_match = null;
            preg_match('/".+":rtcol/', $line, $first_match);

            $second_match = null;
            preg_match('/ ".+":ltcol/', $line, $second_match);

            $from_match = null;
            $from_is_input_schema = false;
            if(!empty($first_match))
            {
                $from_match = substr($first_match[0], 1, strpos($first_match[0], '":')-1);

                if(substr($from_match, 0, strlen($schema.'.')) === $schema.'.')
                {
                    $from_is_input_schema = true;
                }
            }

            $to_match = null;
            $to_is_input_schema = false;
            if(!empty($second_match))
            {
                $to_match =  substr(trim($second_match[0]), 1, strpos(trim($second_match[0]), '":')-1);

                if(substr($to_match, 0, strlen($schema.'.')) === $schema.'.')
                {
                    $to_is_input_schema = true;
                }
            }

            if($from_is_input_schema && $to_is_input_schema)
            {
                $connectors_to_use[] = $line;
            }
            else
            {
                if($from_is_input_schema)
                {
                    if(!is_null($to_match))
                    {
                        $to_schema =substr($to_match, 0, strpos($to_match, '.'));
                        if(!in_array($to_schema, $schema_table_macros_used))
                        {
                            $schema_table_macros_used[] = $to_schema;
                        }

                        $used_connector = preg_replace('/'.$to_match.'":ltcol\d+/', $to_schema.'":ltcol1', $line);
                        $connectors_to_use[] = $used_connector;
                    }
                }
                else if($to_is_input_schema)
                {
                    if(!is_null($from_match))
                    {
                        $from_schema =substr($from_match, 0, strpos($from_match, '.'));
                        if(!in_array($from_schema, $schema_table_macros_used))
                        {
                            $schema_table_macros_used[] = $from_schema;
                        }
                        $used_connector = preg_replace('/'.$from_match.'":rtcol\d+/', $from_schema.'":rtcol1', $line);
                        $connectors_to_use[] = $used_connector;
                    }
                }
            }
        }

        $previous_line = $line;
    }

    $handle_write = fopen($output_file, "w") or die("Unable to open file to write!");
    
    foreach($pre_tables_lines as $pre_tables_line)
    {
        fwrite($handle_write, $pre_tables_line);
    }
    
    $table_template = '"{SCHEMA_NAME}" [shape = plaintext, label = < '
            . '<TABLE BORDER="1" CELLBORDER="0" CELLSPACING="0"> '
                . '<TR >'
                    . '<TD PORT="ltcol0"> </TD> '
                    . '<TD bgcolor="DarkSeaGreen" border="1" COLSPAN="4"> \N </TD> '
                    . '<TD PORT="rtcol0"></TD></TR>  '
                . '<TR>'
                    . '<TD PORT="ltcol1" ></TD>'
                    . '<TD align="left" > id </TD>'
                    . '<TD align="left" > virt </TD>'
                    . '<TD align="left" > col </TD>'
                    . '<TD align="left" >  </TD>'
                    . '<TD align="left" PORT="rtcol1"> </TD>'
                . '</TR> '
            . '</TABLE>> ];';
    foreach($schema_table_macros_used as $schema_table_macro_used)
    {
        $used_macro = str_replace('{SCHEMA_NAME}', $schema_table_macro_used, $table_template);
        fwrite($handle_write, $used_macro."\n");
    }
    
    foreach($this_schema_tables_to_print as $this_schema_table_to_print)
    {
        fwrite($handle_write, $this_schema_table_to_print."\n");
    }

    foreach($connectors_to_use as $connector_to_use)
    {
        fwrite($handle_write, $connector_to_use."\n");
    }

    fwrite($handle_write, "}\n");

    fclose($handle_read);
    fclose($handle_write);
    
    $output_dir = 'output';
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0777, true);
    }

    $svg_command = 'dot -T'.$output_format.' -o '.$output_dir.'/output_'.$dbname.'_'.$schema.'.'.$output_format.' '.$output_file;
    system($svg_command);
}


$host = 'localhost';
$port = 5432;
$username = 'postgres';
$all_schemas = false;
$format = 'svg';

echo "parsing input\n";

$options = getopt("h:p:d:u:s:ap:f:");

if(!isset($options['d']))
{
    exit_usage();
}
if(!isset($options['s']) && !isset($options['a']))
{
    exit_usage();
}
if(isset($options['h']))
{
    $host = $options['h'];
}
if(isset($options['p']))
{
    $port = $options['p'];
}
if(isset($options['u']))
{
    $username = $options['u'];
}
if(isset($options['s']))
{
    $schema = $options['s'];
}
if(isset($options['a']))
{
    $all_schemas = true;
}
if(isset($options['f']))
{
    $format = $options['f'];
}
$dbname = $options['d'];

echo "dumping database\n";

$command = 'postgresql_autodoc -h '.$host.' -p '.$port.' -d '.$dbname.' -U '.$username;
exec($command);

$schemas_to_dump = [];
if($all_schemas)
{
        $all_schemas_command = "psql -h $host -p $port -U $username -d $dbname << EOF 
select schema_name 
from information_schema.schemata
where schema_name not like 'pg_%' and schema_name!='information_schema'
order by schema_name asc
EOF";
    
    $output = null;
    
    exec($all_schemas_command, $output);
    
    for($i=2; $i<count($output)-2; $i++)
    {
        $schemas_to_dump[] = trim($output[$i]);
    }
}
else
{
    $schemas_to_dump[] = $schema;
}

foreach($schemas_to_dump as $schema_to_dump)
{
    create_schema_svg($dbname, $schema_to_dump, $format);
}

echo "clanup\n";

system('rm -rf '.$dbname.'*.dot');
system('rm -rf '.$dbname.'*.dia');
system('rm -rf '.$dbname.'*.html');
system('rm -rf '.$dbname.'*.neato');
system('rm -rf '.$dbname.'*.xml');

echo "done\n";

