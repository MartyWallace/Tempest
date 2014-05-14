# Tempest

Currently being redeveloped - [older, stable version found here](https://github.com/MartyWallace/Tempest/tree/7c42c8cbed3b049f107b2f266419e7ddc1a9c8c1).


## Sublime Text

The <code>Sublime</code> folder contains various snippets useful for faster development using Tempest. Below are the tab-triggers available.

* <code>tempest::class</code> - A blank class definition.
* <code>tempest::response</code> - A Tempest response class.


## Globals

Tempest defines a collection of global constants and methods. Constants are defined within <code>index.php</code> and methods in <code>/server/common/functions.php</code>.

#### Constants

* <code>GET</code> - Used where the string <code>'get'</code> could otherwise be used.
* <code>POST</code> - Used where the string <code>'post'</code> could otherwise be used.
* <code>NAMED</code> - Used where the string <code>'named'</code> could otherwise be used.
* <code>RGX_PATH_DELIMITER</code> - Regex pattern for matching one or more forward or black slashes.
* <code>APP_ROOT</code> - The application root on the server e.g. <code>C:\\...\\MySite\\</code>.
* <code>PUBLIC_ROOT</code> - The public application root e.g. <code>/MySite/</code>.
* <code>DIR_SERVER</code> - The <code>server</code> directory within the application e.g. <code>C:\\...\MySite\\server\\</code>.
* <code>DIR_STATIC</code> - The <code>static</code> directory within the application e.g. <code>/MySite/static/</code>.
* <code>REQUEST_CLEAN</code> - The request URI with querystring and hash values trimmed off.
* <code>REQUEST_URI</code> - The full request URI e.g. <code>/MySite/some/route</code>.
* <code>APP_REQUEST_URI</code> - The request URI relative to the application root e.g. <code>/some/route</code>.

#### Methods

* <code>path_normalize()</code> - Helper for normalizing a path.
* <code>path_split()</code> - Helper for splitting a path into chunks.