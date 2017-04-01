# matrix-synapse-invite
A invite system for matrix-synapse https://github.com/matrix-org/synapse
<br>
<br>
# Requirements
## Database requirements:

If the webserver with the invite code does **not** have access to the **PostgreSQL instance** of your **homeserver**,<br>
you need to copy the `users` table of that SQL instance with:
 * `pg_dump -S synapse -t users synapse > users.sql`
 * login to the webserver with a running postgresql instance and do:
 * `psql -U synapse synapse < users.sql`

Or, allow the webserver to get [remote-access the **PostgreSQL instance**](https://wiki.archlinux.org/index.php/PostgreSQL#Configure_PostgreSQL_to_be_accessible_from_remote_hosts) of your **homeserver**.<br>
**(I strongly recommend this remote allowance is done through a [VPN tunnel](https://www.stunnel.org/index.html) between the machines)** 

## Webserver requirements:
Place `./*.php` on just any webserver really.<br>

## Code configurations:

### generate_invite.php

    $ERROR_redirect = 'https://domain.com/generate_invite.php?error=1&logout=true';
    $CHAT_redirect = 'https://chat.domain.com';
    $INVITE_server = $CHAT_redirect; // I run the invite system in riot-web root directory, so it will be the same as the chat system.
    $HomeServer_domain = 'chat.domain.com'; // used to struct @user:chat.domain.com

### invite.php

    $ERROR_redirect = 'https://domain.com/?error=true';
    $CHAT_redirect = 'https://chat.domain.com/';
    $HomeServer = 'matrix.domain.com';

<br>

# Usage
Navigate yourself to `https://your-server.com/generate_invite.php`,<br>
once there, login with your `matrix-synapse` username and password,<br>
copy the link and give it to friends and family (enemies too if you really want to, I can't tell you what to do).

The user will get promted to enter a username and a password.
(There's also a version where the user gets a temporary password auto-generated, FYI)

# Known issues
* Facebook chat will preview *(effectively using up)* the link rendering it worthless
* Password input can err-redirect the users without info, usually it's the "valid password characters" that triggers.
