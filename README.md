# matrix-synapse-invite
A invite system for matrix-synapse https://github.com/matrix-org/synapse

# Usage
Database requirements:
-----
If the webserver with the invite code does **not** have access to the **PostgreSQL instance** of your **homeserver**,<br>
you need to copy the `users` table of that SQL instance with:
 * `pg_dump -S synapse -t users synapse > users.sql`
 * login to the webserver with a running postgresql instance and do:
 * `psql -U synapse synapse < users.sql`

Or, allow the webserver to access the **PostgreSQL instance** of your **homeserver**.

Place `./*.php` on just any webserver really.<br>

Navigate yourself to `https://your-server.com/generate_invite.php`,<br>
once there, login with your `matrix-synapse` username and password,<br>
copy the link and give it to friends and family (enemies too if you really want to, I can't tell you what to do).

The user will get promted to enter a username and a password.
(There's also a version where the user gets a temporary password auto-generated, FYI)

# Known issues
* Facebook chat will preview *(effectively using up)* the link rendering it worthless
* Password input can err-redirect the users without info, usually it's the "valid password characters" that triggers.
