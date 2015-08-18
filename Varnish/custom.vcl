vcl 4.0;
import std;
import directors;

backend server1 {
    .host = "lebigusa.com";    # IP or Hostname of backend
    .port = "8080";           # Port Apache or whatever is listening
    .max_connections = 300; # That's it
    .probe = {
        .request =
            "HEAD / HTTP/1.1"
            "Host: localhost"
            "Connection: close";
            .interval  = 5s; # check the health of each backend every 5 seconds
            .timeout   = 1s; # timing out after 1 second.
            .window    = 5;  # If 3 out of the last 5 polls succeeded the backend is considered healthy, otherwise it will be marked as sick
            .threshold = 3;
    }
    .first_byte_timeout     = 300s;   # How long to wait before we receive a first byte from our backend?
    .connect_timeout        = 5s;     # How long to wait for a backend connection?
    .between_bytes_timeout  = 2s;     # How long to wait between bytes received from our backend?
}

acl purge {
    "localhost";
    "127.0.0.1";
    "::1";
}
sub vcl_init {
    new vdir = directors.round_robin();
    vdir.add_backend(server1);
}

sub vcl_recv {
    set req.backend_hint = vdir.backend(); # send all traffic to the vdir director
    
    if (req.restarts == 0) {
	if (req.http.X-Forwarded-For) { # set or append the client.ip to X-Forwarded-For header
            set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
	}
	else {
            set req.http.X-Forwarded-For = client.ip;
	}
    }

    ### Allow purging
    if (req.method == "PURGE") {
        # If not allowed then a error 405 is returned
	if (!client.ip ~ purge) {
            return(synth(405, "This IP is not allowed to send PURGE requests."));
	}	
	# If allowed, do a cache_lookup -> vlc_hit() or vlc_miss()
            return (purge);
    }
    
    ### Do not Cache: special cases
 
    # Do not Authorized requests.
    if (req.http.Authorization) {
        return(pass); // DO NOT CACHE
    }

    # Pass any requests with the "If-None-Match" header directly.
    if (req.http.If-None-Match) {
        return(pass); // DO NOT CACHE
    }

    # Do not cache AJAX requests.
    if (req.http.X-Requested-With == "XMLHttpRequest") {
        return(pass); // DO NOT CACHE
    }

    # Only cache GET or HEAD requests. This makes sure the POST (and OPTIONS) requests are always passed.
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass); // DO NOT CACHE
    }

    # WordPress: disable caching for some parts of the backend 
    if (req.url ~ "^/wp-(login|admin)" || req.url ~ "/wp-cron.php" || req.url ~ "preview=true" ||req.url ~ "/forums/.*") {
        # do not use the cache
        return(pass); // DO NOT CACHE
    }

    ### http header Cookie
    ###     Remove some cookies (if found).

    # Unset the header for static files
    if (req.url ~ "\.(css|flv|gif|ico|jpeg|jpg|js|mp3|mp4|pdf|png|swf|tif|tiff|xml)(\?.*|)$") {
        unset req.http.Cookie;
    }
    if (req.http.cookie) {
        # Google Analytics
        set req.http.Cookie = regsuball( req.http.Cookie, "(^|;\s*)(__utm[a-z]+)=([^;]*)", "");
        set req.http.Cookie = regsuball( req.http.Cookie, "(^|;\s*)(_ga)=([^;]*)", "");

        # __gad __gads
        set req.http.Cookie = regsuball( req.http.Cookie, "(^|;\s*)(__gad[a-z]+)=([^;]*)", "");

        # Remove the wp-settings-1 cookie
	set req.http.Cookie = regsuball(req.http.Cookie, "wp-settings-1=[^;]+(; )?", "");

	# Remove the wp-settings-time-1 cookie
	set req.http.Cookie = regsuball(req.http.Cookie, "wp-settings-time-1=[^;]+(; )?", "");

	# Remove the wp test cookie
	set req.http.Cookie = regsuball(req.http.Cookie, "wordpress_test_cookie=[^;]+(; )?", "");

        # Remove has_js and CloudFlare/Google Analytics __* cookies.
        set req.http.Cookie = regsuball(req.http.Cookie, "(^|;\s*)(_[_a-z]+|has_js)=[^;]*", "");
        # Remove a ";" prefix, if present.
        set req.http.Cookie = regsub(req.http.Cookie, "^;\s*", "");

        # PostAction: Remove (once and if found) a ";" prefix followed by 0..n whitespaces.
        # INFO \s* = 0..n whitespace characters
        set req.http.Cookie = regsub( req.http.Cookie, "^;\s*", "" );

        # PostAction: Unset the header if it is empty or 0..n whitespaces.
        if ( req.http.cookie ~ "^\s*$" ) {
            unset req.http.Cookie;
        }
       
	# Check the cookies for wordpress-specific items
	if (req.http.Cookie ~ "wordpress_" || req.http.Cookie ~ "comment_") {
            return (pass);
	}

        #unset req.http.cookie;
       
    }
    return(hash);
}

sub vcl_pipe {
    return (pipe);
}

sub vcl_pass {
    return (fetch);
}

sub vcl_miss {
    return (fetch);
}

sub vcl_backend_response {
    # Active ESI
    set beresp.do_esi = true;
    if(bereq.url~ "menu.php"){
        set beresp.uncacheable = true; 
        return(deliver); 
    }
    else {
        set beresp.ttl = 6h;
        set beresp.grace = 24h;
        #set beresp.http.cache-control = "max-age = 259200";
    }
    return (deliver);
}

sub vcl_hit {
    if (obj.ttl >= 0s) {
    # A pure unadultered hit, deliver it
        return (deliver);
    }
	
    #We have no fresh fish. Lets look at the stale ones.
    if (std.healthy(req.backend_hint)) {
        # Backend is healthy. Limit age to 10s.
        if (obj.ttl + 10s > 0s) {
            return (deliver);
        } 
        else {
            # No candidate for grace. Fetch a fresh object.
            return(fetch);
        }
    }
    else {
        # backend is sick - use full grace
        if (obj.ttl + obj.grace > 0s) {
            return (deliver);
        } 
        else {
            # no graced object.
            return (fetch);
        }
    }
}

sub vcl_hash {
    hash_data(req.url);

    if (req.http.host) {
        hash_data(req.http.host);
    }
    else {
        hash_data(server.ip);
    }
    
    # hash cookies for requests that have them
    #if (req.http.cookie ~ "lbu_member_id=") {
    #    hash_data( regsub( req.http.cookie, ".*lbu_member_id=([^;]+);.*", "\1" ) );
    #}
    if (req.http.Cookie) {
        hash_data(req.http.Cookie);
    }
    return (lookup);
}

sub vcl_deliver {
    if (obj.hits > 0) { # Add debug header to see if it's a HIT/MISS and the number of hits
        set resp.http.X-Cache = "HIT";
    }
    else {
        set resp.http.X-Cache = "MISS";
    }
    set resp.http.X-Cache-Hits = obj.hits;

    # Remove some headers: PHP version
    unset resp.http.X-Powered-By;
    # Remove some headers: Apache version & OS
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Link;
    unset resp.http.X-Generator;
    unset resp.http.X-Pingback;

    return (deliver);
}