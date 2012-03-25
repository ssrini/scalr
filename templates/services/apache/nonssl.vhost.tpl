<VirtualHost *:80>
       ServerAlias www.{$host} {$server_alias}
       ServerAdmin {$server_admin}
       DocumentRoot {$document_root}
       ServerName {$host}
       CustomLog {$logs_dir}/http-{$host}-access.log combined
       ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/
</VirtualHost>