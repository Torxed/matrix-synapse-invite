# matrix-synapse-invite
A invite system for matrix-synapse https://github.com/matrix-org/synapse
<br>
<br>
# Requirements
## Database requirements:

If the webserver with the invite code does **not** have access to the **PostgreSQL instance** of your **homeserver**,<br>
you need to copy the `users` table of that SQL instance with:
 * `pg_dump -S synapse -t users synapse > users.sql`
 * login to the webserver **with a running postgresql instance** and do:
 * `psql -U synapse synapse < users.sql`

Or, allow the webserver to get [remote-access](https://wiki.archlinux.org/index.php/PostgreSQL#Configure_PostgreSQL_to_be_accessible_from_remote_hosts) to the **PostgreSQL instance** of your **homeserver**.<br>
**(I strongly recommend this remote allowance is done through a [VPN tunnel](https://www.stunnel.org/index.html) between the machines)** 

## Webserver requirements:
Place `./*.php` on just any webserver really.<br>

## Code configurations:

    $CHAT_redirect = 'https://chat.domain.com';
    $ERROR_redirect = $CHAT_redirect . '/invite.php?error=1&logout=true';
    $INVITE_server = $CHAT_redirect;
    $HomeServer_domain = 'matrix.domain.com';
    $HomeServer = 'matrix.domain.com';
    $SharedSecret = "<the secret from /etc/synapse/homeserver.yaml>";
    
    $dbhost = 'matrix.domain.com';
    $dbuser = 'synapse';
    $dbpass = '<db password>';
    $dbname = 'synapse';
    
You need to adapt all these in `invite_helpers.php` for this to work.<br>
I hope they're self-explanatory, if not someone might create a issue and i'll fix this.

<br>

# Usage
Navigate yourself to `https://your-server.com/invite.php`,<br>
once there, login with your `matrix-synapse` username and password,<br>
copy the link and give it to friends and family (enemies too if you really want to, I can't tell you what to do).

The user will get a generated password and promted to enter a username.<br>
They **need to remember the generated password**.<br>

# Known issues
* Facebook chat will preview *(effectively using up)* the link rendering it worthless
