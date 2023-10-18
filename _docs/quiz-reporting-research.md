# Quiz Reporting

Detail research on how the stats are generated for Moodle's Quiz mod.  The form does a GET request and reloads the page.

- [Question Engine Details](https://docs.moodle.org/dev/Overview_of_the_Moodle_question_engine#Detailed_data_about_an_attempt).
- [Question Database Structure](https://docs.moodle.org/dev/Question_database_structure).
- [Quiz Database Structure](https://docs.moodle.org/dev/Quiz_database_structure).

I was able to find the Query by following these steps:

1. Open the report.php where the download is triggered.
2. Locate the function call `$table->out`.
3. Right before it, type `print_r($table->sql);` and refresh the page.

This is how you find the parts of the query that is being fired.

## URL Parameters

The Overview and Response reports use the following parameters when exporting data:

| Parameter | Description |
| --------- | ----------- |
| download | The file type to download. **options:** json, csv, excel, html, ods, pdf. |
| id | The quiz id. |
| mode | The report mode. **options:** overview (General Overview), responses (Students Answers), statistics (Overall Stats). |
| attempts | The type of attempts to display. **options:** enrolled_with (enrolled users who have attempted the quiz), enrolled_without (enrolled users who have not attempted the quiz), enrolled_any (enrolled users who have, or have not, attempted the quiz), all_with (all users who have attempted the quiz). |
| states | Specific states of the quiz to display. Add a dash between each state to include. **options:** overdue, finished, abandoned, inprogress. |
| onlygraded | Show at most one finished attempt per user. |
| sesskey | The session key of the logged in user. **required** |

## Grades (Overview) Report

Get the overview report with grades per question.

### URL
http://learn.dev-staging.thewellcloud.cloud/mod/quiz/report.php?sesskey=2P4X1p01ia&download=json&id=543&mode=overview&attempts=enrolled_with&onlygraded=&onlyregraded=&slotmarks=1

#### Custom Parameters

| Parameter | Description |
| --------- | ----------- |
| slotmarks | Do you want to see the grades for each question? (0-1) |


## Reponse Report

Get information on how a user responded to a quiz.

### URL

http://learn.dev-staging.thewellcloud.cloud/mod/quiz/report.php?sesskey=JFe9BERcrV&download=json&id=543&mode=responses&attempts=enrolled_with&onlygraded=&qtext=1&resp=1&right=1 

#### Custom Parameters

| Parameter | Description |
| --------- | ----------- |
| qtext | Do you want to display the question text? (0 or 1) |
| resp | Do you want to display their response? (0 or 1) |
| right | Do you want to display the right answer? (0 or 1) |


## Code Breakdown

When requesting downloading content:

- calls mod/quiz/report.php.
- Looks for a class called **report/$mode/report.php**, and then it includes and sets up the class.
- It calls **display()** on that class.
    - Somewhere in **display()** they are triggering the download.
- Outputs the view.

## Various Notes

- login a user

```
// login the user
$user = authenticate_user_login('admin', $password);
if (!$user) {
    echo "We are unable to login.  Please check the credentials for the admin user.\r\n";
    exit;
}
complete_user_login($user);
$sessionKey = $_SESSION['USER']->sesskey;
if (!$sessionKey) {
    echo "We did not receive a session key.\r\n";
    exit;
}
```