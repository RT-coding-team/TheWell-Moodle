# Module Icons

This is a Moodle custom field that allows the user to select which icon to display on the course page.

## Icons

All icons should be placed in your theme directory under `pix_core/mi`.  If no images are found, you will not be able to select an image.

## Build

In order to use the javascript, you may need to build it.  From the root directory:

- Using NVM, set node version to 14
- Install node packages with `npm install`
- Install the grun cli with `npm install -g grunt-cli`
- CD into /local/module_icons/amd/
- Run the grunt cli with force `grunt --force`

To learn more, checkout [this Moodle Doc](https://docs.moodle.org/dev/Javascript_Modules).

## Issues

In order for this to work, I needed to add a new function callback named **PLUGINNAME**_modify_coursemodule_info.  I then modified `course/lib.php` to call this callback.  You will find the code on the following lines: 478 & 558-574.
