# Chat Attachments

A Moodle local plugin designed to work with a modified version of the mobile app.  It provides phone home sync and enables Moodle messages displaying images, video and files in a chat message.  See the repo for chathost APIs at https://github.com/RT-coding-team/chathost

Sync contains:
- Log files 
- Settings changes (from server to remote)
- Courses and participant rosters
- Chats and attachments from server
- Chats and attachments to server

## File Server

This code also provides a file server.  Any files that have been moved into our chat attachment plugin (See the API below), are accessible through this plugin. The URL for the files should be constructed this way:

**SITE_DOMAIN/pluginfile.php/1/local_chat_attachments/chat_attachment/ITEM_ID/PATH_TO_FILE/FILE_NAME**

## API

This plugin provides an API for managing the chat attachments.  All API requests are made to the following URL: `**SITE_DOMAIN**/local/chat_attachments/api.php`.  Here are the available endpoints:

### Add File [POST]

This endpoint moves a file from the user's draft section to our chat attachment plugin.

#### Parameters

You need to make a POST request with the following parameters:

| Parameter | Required | Description |
| :-------: | :------: | :---------: |
| item_id | Yes | The item id for the file provided by Moodle's File API |
| method | Yes | The method you want to call on the API. This method is **add_file** |
| token | Yes | This is Moodle's user token in order to authorize the user preforming the action.  **This must be the owner of the file.** |

#### Example Query

This example uses Angular's HTTP service.

```javascript
const url = `${siteUrl}/local/chat_attachments/api.php`;
const headers = {
    headers: {
        'content-type': 'application/json'
    }
};
const params = {
    token: token,
    method: 'add_file',
    item_id: itemId,
};
this.http.post(url, params, headers).pipe(take(1)).subscribe(() => {
    // Do your work here.
});
```

#### Returns

```json
{
    "success": true
}
```

## Push Messages

To push messages to the API, you have 3 options:

1. Visit `${siteUrl}/tasks/` and use the provided GUI.
2. Visit `${siteUrl}/local/chat_attachments/push_messages.php?logging=display`. (If you remove the logging param, it will log to the report.json file.)
3. On terminal run the following command `/local/chat_attachments/push_messages.php true`. (If you add an additional true, it will log to the report.json file instead of stdout.)

This script will handle all the message syncing.

## Command Line Scripts

We also provide a few command line scripts for managing the chat attachments.

### Clean Up Attachments

This script will remove files from messages older than the provided number of days, and replace it with a missing symbol.

```
php clean_up.php <NUMBER_OF_DAYS>
```

### Compress Attachments

This script requires [FFmpeg](https://www.ffmpeg.org/) to be installed on your server.  It will compress the attachment in order to reduce the required server space. Use the following command to run the script:

```
php compress_attachment.php <ITEMID>
```

The ITEMID is the itemid that identifies which attachment to compress.

### Push Messages

It is better to use the visual tool listed above, but you can also use the CLI.  Simply run the following command:

```
php push_messages.php true <boolean:LOG_TO_FILE>
```

The first true arguments tells the script that you are using the command line.  The second boolean indicates if you want to log to the JSON file (true) or to the terminal (false).

### Messaging Flow Between Moodle and Chathost
<img src="https://www.websequencediagrams.com/cgi-bin/cdraw?lz=dGl0bGUgU3luYyBNb29kbGUgLyBDaGF0aG9zdCAocHVzaF9tZXNzYWdlcy5waHApIC0gUmV2aXNlZCAyMDIyMDIxNAoKClN0dWRlbnQtPldlbGwAQgY6IAAOByBzZW5kcyAAPwcgdG8gdGVhY2hlciBcbihPbmUAEQlwZXIgaXRlbTogdGV4dCwgYXR0YWNobWVudCkKVGhlV2VsbC0-SW50ZXJuZXQ6IExUQSB0ZXRoZXJzIAAYBwAdCkNoYXRIb3N0OiBDaGVjawCBSQpDb25uZWN0aXZpdHkgdG8gL2MAgWYHL2hlYWx0aAAqBT9ib3hpZD17AAIFfQoARQgtPgCBAAc6IFJlc3BvbmRzIGlmIGFibGUuAGEaQXV0aG9yaXphdGlvbiBLZXkAaQtjaGVjawBKFnBseSB3aXRoIDIwMCBvciA0MDEgKFVuYQBHB2VkAIIKCwCBZApTZW5kAINBCExvZ3MAgVULbG9ncy9tAINfBQBQIiwgNHh4IG9yIDV4eABPGVN5c3RlbQBTFXMAFgUAJkJSZXRyaWV2ZSBTZXR0aW5ncyBjaGFuZ2UAgU0McwAUBwCBKC4gLS0gcgCDQQVzZSBpcyBhbiBhcnJheSBvZiBvYmplY3RzCm5vdGUgb3ZlcgCEWQg6IFByb2Nlc3MgZWFjaCAAcwcsIHRoZW4AhWMFIGEgZGVsZXQAhUwKd2hlbiBjb21wbGV0ZQoKAIUPFEdFVCAvYXBpLwCGYwdTdGF0dXMvAIN5H3RpbWVzdGFtcCBvZiBsYXN0AIZaCXNlbnQgdG8gAIVTCACEOgUAgzIdUE9TAHwHY291cnNlUm9zdGVycy8gSlNPTiBvZiAAEQZzIGFuZCB1c2VycyBlbnJvbGxlZACEHS8AgkATAIh4BXdpdGhvdXQAiH0Id2lsbACCSwYAgQQSIHRvAIJmBmJveCBpbmZvAIMUFACEMAkAiU8HAIk9CCBzaW5jZQCCMgsAgXEeAIl0CC8gKACCBgUAhBoJAIoNCCkAhyYYaWVzAIcyCQCCVScAiWcKLzoAiXMKSUQgKG9uY2UgZm9yAIRxBgCKDwoAg2YFAId0FQCERBBzLzoAgXUFAIETHACIagUAg2oIAIInCgCLPwUAgicPAIQxIQCFSwkAgSIzdG8gcmVjZWl2ZQCLbgsAjE4MV3JpdGUAhn0GAIxMCwCNOQd1c2luZyBQSFAgTQCMbAdBUEkAJyEAcQ4AOhFGaWwAQgYAjVcKLT4AjW8HOiBBcHAgZGlzcGxheXMAjTYHaW1hZ2UsIHZpZGVvIG9yIGF1ZGlvIHBsYXllcgoK&s=default">
