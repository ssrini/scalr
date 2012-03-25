{literal}server { {/literal}
	listen       443;
	server_name  {$host} www.{$host} {$server_alias};
	
	ssl                  on;
	ssl_certificate      /etc/aws/keys/ssl/https.crt;
	ssl_certificate_key  /etc/aws/keys/ssl/https.key;

	ssl_session_timeout  10m;
	ssl_session_cache    shared:SSL:10m;

	ssl_protocols  SSLv2 SSLv3 TLSv1;
	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;
	ssl_prefer_server_ciphers   on;
{literal}
	location / {
		proxy_pass         http://backend;
		proxy_set_header   Host             $host;
		proxy_set_header   X-Real-IP        $remote_addr;
		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;

		client_max_body_size       10m;
		client_body_buffer_size    128k;
  
		proxy_buffering on;
		proxy_connect_timeout 15;
		proxy_intercept_errors on;  
    }
} {/literal}