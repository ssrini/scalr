<IfModule mod_ssl.c>
       <VirtualHost *:443>
               ServerName {$host}
               ServerAlias www.{$host} {$server_alias}
               ServerAdmin {$server_admin}
               DocumentRoot {$document_root}
               CustomLog {$logs_dir}/http-{$host}-access.log combined

               SSLEngine on
               SSLCertificateFile /etc/aws/keys/ssl/https.crt
               SSLCertificateKeyFile /etc/aws/keys/ssl/https.key
               ErrorLog {$logs_dir}/http-{$host}-ssl.log

               ScriptAlias /cgi-bin/ {$document_root}/cgi-bin/
               SetEnvIf User-Agent ".*MSIE.*" nokeepalive ssl-unclean-shutdown
       </VirtualHost>
</IfModule>