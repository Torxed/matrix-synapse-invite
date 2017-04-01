# matrix-synapse-invite
A invite system for matrix-synapse https://github.com/matrix-org/synapse

# Useage
Place `./*.php` on just any webserver really.
Make sure it has access to a PostgreSQL instance (usually running on the home-server)

Navigate yourself to `https://your-server.com/generate_invite.php`,
once there, copy the link and give it to friends and family (enemies too, I can't tell you what to do).

The user will get promted to enter a username and a password.
(There's also a version where the user gets a temporary password auto-generated, FYI)

# Known issues
* Facebook chat will preview (effectively using up the) link rendering it worthless
* The owner of the generated invite links are dummy for now, have a local copy of a working version.
* Password input can err-redirect the users without info, usually it's the "valid password characters" that triggers.
