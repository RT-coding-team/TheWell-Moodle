# Moodle Branch For The Well

This is the Moodle branch to be deployed for The Well or Raspberry Pi but can also run on AWS, Azure or a server.

# Default Database and Filestore
* Default database that is loaded with the installation of Connectbox is stored here: https://github.com/ConnectBox/connectbox-pi/blob/master/ansible/roles/ansible-postgresql/templates/moodle_database_template.dump
* Default filestore that is loaded with the installation of Connectbox is stored here: https://github.com/ConnectBox/connectbox-pi/blob/master/ansible/roles/moodle/templates/filedir.tar.gz
* Loom video for how to update the database and filestore in Ansible for new builds: https://www.loom.com/share/826e5d11101d4f35ae12ea467e7c08b3

# Key Modifications  

# Moodle User Accounts
* A newly set up Well Connectbox will have the following two user accounts set up (both have the password !1TheWell):
  * admin
  * user
* The default passwords should be changed for production use.

# Enhanced Messaging
* This Moodle Repo has some added functionality to permit rich messaging through the Well Moodle App (https://github.com/RT-coding-team/moodleapp) which supports text, audio chat, video and attachments (only through the App on Android).  
  * Chat messages store attachments in the file store for Moodle
  * Messages have markup in the message body: <attachment type="audio" type="audio/mp3" id="moodleFileID">
  * Local Plugin (https://github.com/RT-coding-team/the-well-moodle310/tree/master/local/chat_attachments) handles syncing of the messaging with external chathost (https://github.com/RT-coding-team/chathost)
  * Requires column added to mdl_message table: from_rocketchat smallint DEFAULT 0

# Course Restoration Enhancements
* Courses may be restored by inserting a USB (USB port 0) stick with .mbz course restore files.  All files in the root directory of the USB stick will be automatically restored.  
* URL (todo)
